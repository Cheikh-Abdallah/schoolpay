<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\FlashHelper;

/**
 * Contrôleur d'authentification pour les établissements et le SuperAdmin
 */
class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion principal (Établissements)
     */
    public function showLoginForm(Request $request): void
    {
        if (Auth::check()) {
            if (Auth::isSuperAdmin()) {
                Response::redirect('/superadmin/dashboard');
            }
            Response::redirect('/dashboard');
        }

        $this->render('auth.login', [
            'title' => 'Connexion Établissement',
            'isSuperAdmin' => false
        ], 'auth');
    }

    /**
     * Traite la connexion d'un utilisateur d'établissement
     */
    public function login(Request $request): void
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $service = new \App\Services\AuthenticationService();
        $user = $service->authenticateEstablishmentUser($data['email'], $data['password']);

        if (!$user) {
            FlashHelper::error('Identifiants incorrects ou compte inactif.');
            $this->redirect('/login');
        }

        $establishment = $service->getEstablishmentForUser((int) $user['id']);
        if (!$establishment) {
            FlashHelper::error('Aucun établissement actif associé.');
            $this->redirect('/login');
        }

        Auth::loginEstablishmentUser($user, $establishment);

        FlashHelper::success('Bienvenue sur SchoolPay, ' . htmlspecialchars($user['full_name']) . ' !');
        $this->redirect('/dashboard');
    }

    /**
     * Affiche le formulaire de connexion Super Admin HOME SCHOOL
     */
    public function showSuperAdminLoginForm(Request $request): void
    {
        if (Auth::check() && Auth::isSuperAdmin()) {
            Response::redirect('/superadmin/dashboard');
        }

        $this->render('auth.login', [
            'title' => 'Connexion HOME SCHOOL - Administration Centrale',
            'isSuperAdmin' => true
        ], 'auth');
    }

    /**
     * Traite la connexion d'un utilisateur de la plateforme
     */
    public function superAdminLogin(Request $request): void
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $service = new \App\Services\AuthenticationService();
        $user = $service->authenticatePlatformUser($data['email'], $data['password']);

        if (!$user) {
            FlashHelper::error('Identifiants administrateur incorrects ou compte suspendu.');
            $this->redirect('/superadmin/login');
        }

        Auth::loginPlatformUser($user);

        FlashHelper::success('Connexion Super Admin établie.');
        $this->redirect('/superadmin/dashboard');
    }

    /**
     * Déconnexion sécurisée
     */
    public function logout(Request $request): void
    {
        $isSuperAdmin = Auth::isSuperAdmin();
        Auth::logout();

        FlashHelper::info('Vous avez été déconnecté avec succès.');
        if ($isSuperAdmin) {
            Response::redirect('/superadmin/login');
        }
        Response::redirect('/login');
    }
}
