<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\EstablishmentRepository;
use App\Repositories\PlanRepository;
use App\Services\InvitationService;
use App\Services\SendTextService;

/**
 * Contrôleur du Tableau de Bord Super Admin HOME SCHOOL
 */
class DashboardController extends Controller
{
    private EstablishmentRepository $establishmentRepo;
    private PlanRepository $planRepo;
    private InvitationService $invitationService;
    private SendTextService $sendTextService;

    public function __construct()
    {
        $this->establishmentRepo = new EstablishmentRepository();
        $this->planRepo = new PlanRepository();
        $this->invitationService = new InvitationService();
        $this->sendTextService = new SendTextService();
    }

    public function index(Request $request): void
    {
        $stats = $this->establishmentRepo->getGlobalStats();
        $recentEstablishments = $this->establishmentRepo->getPaginated(1, 5)['items'];
        $recentInvitations = array_slice($this->invitationService->getAllInvitations(), 0, 5);
        $plans = $this->planRepo->getAll();

        // Récupération du solde SendText si configuré
        $sendTextBalance = $this->sendTextService->isConfigured() ? $this->sendTextService->getBalance() : null;

        $this->render('superadmin.dashboard', [
            'title' => 'Tableau de Bord Global HOME SCHOOL',
            'stats' => $stats,
            'recentEstablishments' => $recentEstablishments,
            'recentInvitations' => $recentInvitations,
            'plans' => $plans,
            'sendTextBalance' => $sendTextBalance,
            'isSendTextConfigured' => $this->sendTextService->isConfigured()
        ], 'superadmin');
    }
}
