<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\SubscriptionRepository;

/**
 * Contrôleur de suivi des abonnements SaaS et encaissements HOME SCHOOL
 */
class SubscriptionController extends Controller
{
    private SubscriptionRepository $subscriptionRepo;

    public function __construct()
    {
        $this->subscriptionRepo = new SubscriptionRepository();
    }

    public function index(Request $request): void
    {
        $page = (int) $request->query('page', 1);
        $result = $this->subscriptionRepo->getPaginated($page, 15);
        $recentTransactions = $this->subscriptionRepo->getRecentTransactions(10);

        $this->render('superadmin.subscriptions.index', [
            'title' => 'Suivi des Abonnements & Paiements SaaS',
            'subscriptions' => $result['items'],
            'pagination' => $result,
            'recentTransactions' => $recentTransactions
        ], 'superadmin');
    }
}
