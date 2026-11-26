<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\FlashHelper;
use App\Repositories\PlanRepository;

/**
 * Contrôleur de gestion des forfaits et tarifs SaaS
 */
class PlanController extends Controller
{
    private PlanRepository $planRepo;

    public function __construct()
    {
        $this->planRepo = new PlanRepository();
    }

    public function index(Request $request): void
    {
        $plans = $this->planRepo->getAll();

        $this->render('superadmin.plans.index', [
            'title' => 'Gestion des Forfaits & Tarifs SaaS',
            'plans' => $plans
        ], 'superadmin');
    }

    public function update(Request $request, string $id): void
    {
        $planId = (int) $id;
        $plan = $this->planRepo->findById($planId);

        if (!$plan) {
            FlashHelper::error('Forfait introuvable.');
            $this->redirect('/superadmin/plans');
        }

        $data = $this->validate($request, [
            'monthly_price' => 'required|positive',
            'monthly_sms_quota' => 'required|numeric'
        ]);

        $this->planRepo->update($planId, $data);

        FlashHelper::success("Le forfait « {$plan['name']} » a été mis à jour avec succès.");
        $this->redirect('/superadmin/plans');
    }
}
