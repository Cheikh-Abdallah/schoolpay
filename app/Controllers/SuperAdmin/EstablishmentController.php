<?php

declare(strict_types=1);

namespace App\Controllers\SuperAdmin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Helpers\FlashHelper;
use App\Repositories\EstablishmentRepository;

/**
 * Contrôleur de gestion des établissements scolaires pour HOME SCHOOL
 */
class EstablishmentController extends Controller
{
    private EstablishmentRepository $establishmentRepo;

    public function __construct()
    {
        $this->establishmentRepo = new EstablishmentRepository();
    }

    public function index(Request $request): void
    {
        $page = (int) $request->query('page', 1);
        $status = $request->query('status', 'all');
        $search = $request->query('q', '');

        $result = $this->establishmentRepo->getPaginated($page, 15, $status, $search);

        $this->render('superadmin.establishments.index', [
            'title' => 'Gestion des Établissements',
            'establishments' => $result['items'],
            'pagination' => $result,
            'currentStatus' => $status,
            'search' => $search
        ], 'superadmin');
    }

    public function show(Request $request, string $id): void
    {
        $estId = (int) $id;
        $establishment = $this->establishmentRepo->findWithDetails($estId);

        if (!$establishment) {
            FlashHelper::error('Établissement introuvable.');
            $this->redirect('/superadmin/establishments');
        }

        // Récupération des utilisateurs de l'école
        $users = Database::fetchAll(
            "SELECT * FROM establishment_users WHERE establishment_id = :id ORDER BY created_at ASC",
            [':id' => $estId]
        );

        // Récupération des années académiques
        $academicYears = Database::fetchAll(
            "SELECT * FROM academic_years WHERE establishment_id = :id ORDER BY starts_on DESC",
            [':id' => $estId]
        );

        $this->render('superadmin.establishments.show', [
            'title' => 'Fiche Établissement : ' . $establishment['name'],
            'est' => $establishment,
            'users' => $users,
            'academicYears' => $academicYears
        ], 'superadmin');
    }

    public function toggleStatus(Request $request, string $id): void
    {
        $estId = (int) $id;
        $est = $this->establishmentRepo->findWithDetails($estId);

        if (!$est) {
            FlashHelper::error('Établissement introuvable.');
            $this->redirect('/superadmin/establishments');
        }

        $newStatus = ($est['status'] === 'suspended') ? 'active' : 'suspended';
        $this->establishmentRepo->setStatus($estId, $newStatus);

        $actionLabel = ($newStatus === 'suspended') ? 'suspendu' : 'réactivé';
        Logger::info("Établissement ID {$estId} ({$est['name']}) {$actionLabel} par Super Admin.");

        FlashHelper::success("L'établissement « {$est['name']} » a été {$actionLabel} avec succès.");
        $this->redirect('/superadmin/establishments/' . $estId);
    }
}
