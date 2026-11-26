<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AuditRepository;

/**
 * Contrôleur de consultation des journaux d'audit et cybersécurité
 */
class AuditController extends Controller
{
    private AuditRepository $auditRepo;

    public function __construct()
    {
        $this->auditRepo = new AuditRepository();
    }

    public function index(Request $request): void
    {
        $page = (int) $request->query('page', 1);
        $actorType = $request->query('actor_type', 'all');
        $search = $request->query('q', '');

        $result = $this->auditRepo->getPaginated($page, 25, $actorType, $search);

        $this->render('superadmin.audit.index', [
            'title' => 'Journaux d\'Audit & Cybersécurité',
            'logs' => $result['items'],
            'pagination' => $result,
            'currentActor' => $actorType,
            'search' => $search
        ], 'superadmin');
    }
}
