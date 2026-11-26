<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;
use App\Services\InvitationService;
use App\Services\TrialService;
use RuntimeException;

/**
 * Contrôleur d'onboarding sur invitation pour les établissements scolaires pilotes
 */
class OnboardingController extends Controller
{
    private InvitationService $invitationService;
    private TrialService $trialService;

    public function __construct()
    {
        $this->invitationService = new InvitationService();
        $this->trialService = new TrialService();
    }

    /**
     * Affiche le formulaire d'acceptation de l'invitation
     */
    public function showAcceptForm(Request $request): void
    {
        $token = $request->query('token', '');
        $invitation = $this->invitationService->validateToken((string) $token);

        if (!$invitation) {
            FlashHelper::error('Ce lien d\'invitation est invalide, expiré ou a déjà été utilisé.');
            Response::redirect('/login');
        }

        $this->render('auth.accept-invitation', [
            'title' => 'Création de votre Établissement',
            'invitation' => $invitation,
            'token' => $token
        ], 'auth');
    }

    /**
     * Traite la création de l'établissement et du compte administrateur fondateur
     */
    public function acceptInvitation(Request $request): void
    {
        $token = (string) $request->input('token', '');
        $invitation = $this->invitationService->validateToken($token);

        if (!$invitation) {
            FlashHelper::error('Invitation invalide ou expirée.');
            Response::redirect('/login');
        }

        // Validation stricte des données du formulaire
        $data = $this->validate($request, [
            'token' => 'required',
            'school_name' => 'required|min:3|max:180',
            'phone' => 'required|phone',
            'address' => 'required|max:255',
            'city' => 'required|max:100',
            'admin_name' => 'required|min:3|max:150',
            'password' => 'required|min:8'
        ]);

        $passwordConfirmation = (string) $request->input('password_confirmation', '');
        if ($data['password'] !== $passwordConfirmation) {
            FlashHelper::error('Les mots de passe saisis ne sont pas identiques.');
            Response::redirect('/invitation/accept?token=' . urlencode($token));
        }

        $email = $invitation['invited_email'];

        try {
            $createdData = Database::transaction(function () use ($data, $email, $invitation) {
                // 1. Génération d'un UUID v4 unique
                $uuid = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );

                // 2. Génération d'un slug d'URL propre
                $rawSlug = preg_replace('~[^\pL\d]+~u', '-', mb_strtolower($data['school_name']));
                $slug = trim(preg_replace('~[^-\w]+~', '', $rawSlug), '-');
                if (empty($slug)) {
                    $slug = 'ecole-' . mt_rand(100, 999);
                }

                // Vérification unicité du slug
                $existingSlug = Database::fetchOne("SELECT id FROM establishments WHERE slug = :slug", [':slug' => $slug]);
                if ($existingSlug) {
                    $slug .= '-' . mt_rand(10, 99);
                }

                // 3. Création de l'établissement
                Database::query(
                    "INSERT INTO establishments 
                     (uuid, name, slug, legal_name, ninea, rccm, email, phone, address, city, country_code, status, timezone, currency, created_at)
                     VALUES (:uuid, :name, :slug, :legal, :ninea, :rccm, :email, :phone, :address, :city, 'SN', 'trial', 'Africa/Dakar', 'XOF', CURRENT_TIMESTAMP)",
                    [
                        ':uuid' => $uuid,
                        ':name' => $data['school_name'],
                        ':slug' => $slug,
                        ':legal' => $data['school_name'],
                        ':ninea' => $data['ninea'] ?? null,
                        ':rccm' => $data['rccm'] ?? null,
                        ':email' => $email,
                        ':phone' => $data['phone'],
                        ':address' => $data['address'],
                        ':city' => $data['city']
                    ]
                );
                $establishmentId = (int) Database::lastInsertId();

                // 4. Initialisation des paramètres de l'établissement
                Database::query(
                    "INSERT INTO establishment_settings 
                     (establishment_id, receipt_prefix, invoice_prefix, sms_sender_name, sms_notifications_enabled, created_at)
                     VALUES (:est_id, 'REC', 'FAC', 'SCHOOLPAY', 1, CURRENT_TIMESTAMP)",
                    [':est_id' => $establishmentId]
                );

                // 5. Initialisation de l'année académique courante (2026-2027)
                Database::query(
                    "INSERT INTO academic_years 
                     (establishment_id, label, starts_on, ends_on, is_current, created_at)
                     VALUES (:est_id, '2026-2027', '2026-10-01', '2027-07-31', 1, CURRENT_TIMESTAMP)",
                    [':est_id' => $establishmentId]
                );

                // 6. Initialisation des types de frais standards du Sénégal
                $defaultFeeTypes = [
                    ['name' => "Frais d'inscription", 'amount' => 25000, 'recurring' => 0],
                    ['name' => "Mensualité de scolarité", 'amount' => 20000, 'recurring' => 1],
                    ['name' => "Frais d'examen & dossier", 'amount' => 10000, 'recurring' => 0],
                ];

                foreach ($defaultFeeTypes as $ft) {
                    Database::query(
                        "INSERT INTO fee_types (establishment_id, name, default_amount, is_recurring, is_active, created_at)
                         VALUES (:est_id, :name, :amount, :recurring, 1, CURRENT_TIMESTAMP)",
                        [
                            ':est_id' => $establishmentId,
                            ':name' => $ft['name'],
                            ':amount' => $ft['amount'],
                            ':recurring' => $ft['recurring']
                        ]
                    );
                }

                // 7. Création de l'utilisateur administrateur fondateur (role: owner)
                $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
                Database::query(
                    "INSERT INTO establishment_users 
                     (establishment_id, full_name, email, phone, password_hash, role, is_active, must_change_password, created_at)
                     VALUES (:est_id, :name, :email, :phone, :hash, 'owner', 1, 0, CURRENT_TIMESTAMP)",
                    [
                        ':est_id' => $establishmentId,
                        ':name' => $data['admin_name'],
                        ':email' => $email,
                        ':phone' => $data['phone'],
                        ':hash' => $passwordHash
                    ]
                );
                $userId = (int) Database::lastInsertId();

                // 8. Validation et clôture de l'invitation
                $this->invitationService->acceptInvitation((int) $invitation['id'], $establishmentId);

                // 9. Démarrage automatique de l'essai gratuit de 72h avec 10 SMS offerts
                $this->trialService->startTrial($establishmentId, 72, 10);

                // Récupération des données complètes pour connexion immédiate
                $establishment = Database::fetchOne("SELECT * FROM establishments WHERE id = :id", [':id' => $establishmentId]);
                $user = Database::fetchOne("SELECT * FROM establishment_users WHERE id = :id", [':id' => $userId]);

                return ['user' => $user, 'establishment' => $establishment];
            });

            // Connexion immédiate du nouvel administrateur
            Auth::loginEstablishmentUser($createdData['user'], $createdData['establishment']);

            FlashHelper::success(
                'Félicitations ! Votre établissement est prêt. Vous bénéficiez de 72 heures d\'essai et de 10 SMS offerts.'
            );

            Response::redirect('/dashboard');

        } catch (\Throwable $e) {
            Logger::error('Erreur lors de la création de l\'établissement : ' . $e->getMessage());
            FlashHelper::error('Une erreur est survenue lors de l\'enregistrement. Veuillez vérifier les informations ou contacter HOME SCHOOL.');
            Response::redirect('/invitation/accept?token=' . urlencode($token));
        }
    }
}
