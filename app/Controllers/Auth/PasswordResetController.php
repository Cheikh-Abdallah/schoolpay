<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Helpers\FlashHelper;

/**
 * Contrôleur de réinitialisation sécurisée de mot de passe
 */
class PasswordResetController extends Controller
{
    public function showRequestForm(Request $request): void
    {
        $this->render('auth.forgot-password', [
            'title' => 'Mot de passe oublié'
        ], 'auth');
    }

    public function sendResetLink(Request $request): void
    {
        $data = $this->validate($request, [
            'email' => 'required|email'
        ]);

        $email = strtolower(trim($data['email']));

        $user = Database::fetchOne(
            "SELECT id, full_name, email FROM establishment_users WHERE email = :email AND is_active = 1 LIMIT 1",
            [':email' => $email]
        );

        // Par mesure de sécurité anti-énumération, on affiche le même message que le compte existe ou non
        if ($user) {
            Logger::info("Demande de réinitialisation de mot de passe reçue pour : {$email}");
            // Si le serveur SMTP est configuré ultérieurement, le lien par mail sera expédié ici
        }

        FlashHelper::success(
            "Si un compte correspond à {$email}, vous recevrez un lien de réinitialisation ou pouvez contacter votre administrateur d'école."
        );

        $this->redirect('/login');
    }
}
