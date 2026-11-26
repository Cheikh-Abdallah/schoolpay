<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Helpers\FlashHelper;
use App\Services\InvitationService;

/**
 * Contrôleur de gestion des invitations pilotes par HOME SCHOOL
 */
class InvitationController extends Controller
{
    private InvitationService $invitationService;

    public function __construct()
    {
        $this->invitationService = new InvitationService();
    }

    public function index(Request $request): void
    {
        $invitations = $this->invitationService->getAllInvitations();

        $this->render('superadmin.invitations.index', [
            'title' => 'Invitations Établissements Pilotes',
            'invitations' => $invitations
        ], 'superadmin');
    }

    public function create(Request $request): void
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'valid_days' => 'numeric'
        ]);

        $validDays = !empty($data['valid_days']) ? (int) $data['valid_days'] : 7;
        $adminId = Auth::id() ?? 1;

        try {
            $inv = $this->invitationService->createInvitation($adminId, $data['email'], $validDays);

            FlashHelper::success(
                "Lien d'invitation généré pour {$inv['email']} : <br><input type='text' value='{$inv['invite_url']}' class='form-control' readonly style='margin-top:6px;font-family:monospace;background:#fff;'>"
            );
        } catch (\Throwable $e) {
            FlashHelper::error($e->getMessage());
        }

        $this->redirect('/superadmin/invitations');
    }

    public function revoke(Request $request, string $id): void
    {
        $invId = (int) $id;
        $this->invitationService->revokeInvitation($invId);

        FlashHelper::success('L\'invitation a été révoquée avec succès.');
        $this->redirect('/superadmin/invitations');
    }
}
