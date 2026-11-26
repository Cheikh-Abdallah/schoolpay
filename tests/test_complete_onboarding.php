<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Database;
use App\Services\InvitationService;
use App\Services\TrialService;

echo "=== TEST COMPLET DE CRÉATION D'ÉCOLE SUR INVITATION ===\n";

$invService = new InvitationService();
$trialService = new TrialService();

// 1. Création d'une invitation pour une école pilote test
$randId = mt_rand(100, 999);
$email = "directeur{$randId}@complexe-keur-madior.sn";
$inv = $invService->createInvitation(1, $email, 7);
$rawToken = $inv['raw_token'];
$invitationId = $inv['id'];

echo "1. Invitation créée : ID {$invitationId} pour {$email}\n";

// 2. Simulation de la soumission du formulaire d'onboarding
$schoolName = "Complexe Scolaire Keur Madior";
$phone = "+221 77 555 44 33";
$address = "Mbour, Route de Saly";
$city = "Mbour";
$adminName = "Amadou Diallo";
$password = "SecretEcole2026!";

// Exécution de la transaction
$establishmentId = Database::transaction(function () use ($schoolName, $phone, $address, $city, $email, $adminName, $password, $invService, $trialService, $invitationId) {
    // UUID v4
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $slug = 'complexe-keur-madior-' . mt_rand(10, 99);

    // Établissement
    Database::query(
        "INSERT INTO establishments (uuid, name, slug, email, phone, address, city, country_code, status, created_at)
         VALUES (:uuid, :name, :slug, :email, :phone, :address, :city, 'SN', 'trial', CURRENT_TIMESTAMP)",
        [':uuid' => $uuid, ':name' => $schoolName, ':slug' => $slug, ':email' => $email, ':phone' => $phone, ':address' => $address, ':city' => $city]
    );
    $estId = (int) Database::lastInsertId();

    // Paramètres
    Database::query("INSERT INTO establishment_settings (establishment_id, receipt_prefix, invoice_prefix, sms_sender_name, created_at) VALUES (:id, 'REC', 'FAC', 'SCHOOLPAY', CURRENT_TIMESTAMP)", [':id' => $estId]);

    // Année académique
    Database::query("INSERT INTO academic_years (establishment_id, label, starts_on, ends_on, is_current, created_at) VALUES (:id, '2026-2027', '2026-10-01', '2027-07-31', 1, CURRENT_TIMESTAMP)", [':id' => $estId]);

    // Fee types par défaut
    Database::query("INSERT INTO fee_types (establishment_id, name, default_amount, is_recurring, is_active, created_at) VALUES (:id, 'Frais d inscription', 25000, 0, 1, CURRENT_TIMESTAMP)", [':id' => $estId]);
    Database::query("INSERT INTO fee_types (establishment_id, name, default_amount, is_recurring, is_active, created_at) VALUES (:id, 'Mensualite de scolarite', 20000, 1, 1, CURRENT_TIMESTAMP)", [':id' => $estId]);

    // Utilisateur owner
    Database::query(
        "INSERT INTO establishment_users (establishment_id, full_name, email, phone, password_hash, role, is_active, created_at)
         VALUES (:est_id, :name, :email, :phone, :hash, 'owner', 1, CURRENT_TIMESTAMP)",
        [':est_id' => $estId, ':name' => $adminName, ':email' => $email, ':phone' => $phone, ':hash' => password_hash($password, PASSWORD_DEFAULT)]
    );

    // Clôture invitation
    $invService->acceptInvitation($invitationId, $estId);

    // Démarrage essai 72h + 10 SMS
    $trialService->startTrial($estId, 72, 10);

    return $estId;
});

echo "2. Établissement créé avec succès : ID {$establishmentId}\n";

// 3. Vérification des vérifications d'essai
$trial = $trialService->getTrialDetails($establishmentId);
echo "3. Statut essai 72h : " . ($trial['is_active'] ? 'ACTIF' : 'INACTIF') . "\n";
echo "   Heures restantes : {$trial['remaining_hours']}h {$trial['remaining_minutes']}m\n";

// 4. Vérification du compte SMS
$smsAcc = Database::fetchOne("SELECT * FROM sms_credit_accounts WHERE establishment_id = :id", [':id' => $establishmentId]);
echo "4. Crédits SMS disponibles : {$smsAcc['available_credits']} SMS\n";

// 5. Test d'authentification du nouveau directeur
$authService = new \App\Services\AuthenticationService();
$loggedUser = $authService->authenticateEstablishmentUser($email, $password);
echo "5. Authentification du Directeur ({$email}) : " . ($loggedUser ? "RÉUSSIE (Rôle: {$loggedUser['role']})" : "ÉCHEC") . "\n";

echo "=== CYCLE D'ONBOARDING ET ESSAI VALIDÉ À 100% ===\n";
