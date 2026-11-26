<?php

declare(strict_types=1);

namespace App\Controllers\Establishment;

use App\Core\Controller;
use App\Core\Request;
use App\Core\TenantContext;
use App\Core\Auth;
use App\Repositories\StudentRepository;
use App\Repositories\GuardianRepository;

class StudentController extends Controller
{
    private StudentRepository  $students;
    private GuardianRepository $guardians;

    public function __construct()
    {
        $this->students  = new StudentRepository();
        $this->guardians = new GuardianRepository();
    }

    // ── Liste ────────────────────────────────────────────────────────────────

    public function index(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $page    = max(1, (int) $request->query('page', '1'));
        $search  = trim((string) $request->query('search', ''));
        $class   = trim((string) $request->query('class', ''));
        $status  = trim((string) $request->query('status', ''));

        $result  = $this->students->getPaginated($establishmentId, $page, 20, $search, $class, $status);
        $classes = $this->students->getDistinctClasses($establishmentId);

        $this->render('students.index', [
            'title'   => 'Élèves',
            'result'  => $result,
            'classes' => $classes,
            'search'  => $search,
            'class'   => $class,
            'status'  => $status,
        ], 'main');
    }

    // ── Formulaire création ──────────────────────────────────────────────────

    public function create(Request $request): void
    {
        TenantContext::requireTenantId();

        $this->render('students.create', [
            'title' => 'Ajouter un élève',
        ], 'main');
    }

    // ── Enregistrer ──────────────────────────────────────────────────────────

    public function store(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $data = $request->post();

        $errors = $this->validate($data, [
            'first_name'   => 'required|max:100',
            'last_name'    => 'required|max:100',
            'class_name'   => 'required|max:50',
            'date_of_birth'=> 'nullable|date',
            'gender'       => 'in:M,F',
        ]);

        if (!empty($errors)) {
            flash_error('Veuillez corriger les erreurs dans le formulaire.');
            $this->render('students.create', [
                'title'  => 'Ajouter un élève',
                'errors' => $errors,
                'old'    => $data,
            ], 'main');
            return;
        }

        try {
            $studentId = $this->students->create($establishmentId, $data);
            flash_success('Élève ajouté avec succès.');
            $this->redirect('/students/' . $studentId);
        } catch (\Exception $e) {
            flash_error('Erreur lors de l\'enregistrement : ' . $e->getMessage());
            $this->render('students.create', [
                'title' => 'Ajouter un élève',
                'old'   => $data,
            ], 'main');
        }
    }

    // ── Fiche élève ──────────────────────────────────────────────────────────

    public function show(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $student = $this->students->findWithDetails($establishmentId, (int) $id);

        if (!$student) {
            $this->notFound('Élève introuvable.');
            return;
        }

        // Guardians not yet linked — for linking form
        $allGuardians = $this->guardians->getPaginated($establishmentId, 1, 100);

        $this->render('students.show', [
            'title'       => $student['first_name'] . ' ' . $student['last_name'],
            'student'     => $student,
            'allGuardians'=> $allGuardians['data'],
        ], 'main');
    }

    // ── Formulaire édition ───────────────────────────────────────────────────

    public function edit(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $student = $this->students->findById((int) $id, $establishmentId);

        if (!$student) {
            $this->notFound('Élève introuvable.');
            return;
        }

        $this->render('students.edit', [
            'title'   => 'Modifier ' . $student['first_name'] . ' ' . $student['last_name'],
            'student' => $student,
        ], 'main');
    }

    // ── Mettre à jour ────────────────────────────────────────────────────────

    public function update(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $student = $this->students->findById((int) $id, $establishmentId);
        if (!$student) {
            $this->notFound('Élève introuvable.');
            return;
        }

        $data   = $request->post();
        $errors = $this->validate($data, [
            'first_name'   => 'required|max:100',
            'last_name'    => 'required|max:100',
            'class_name'   => 'required|max:50',
            'date_of_birth'=> 'nullable|date',
            'gender'       => 'in:M,F',
        ]);

        if (!empty($errors)) {
            flash_error('Veuillez corriger les erreurs.');
            $this->render('students.edit', [
                'title'   => 'Modifier l\'élève',
                'student' => array_merge($student, $data),
                'errors'  => $errors,
            ], 'main');
            return;
        }

        try {
            $this->students->update((int) $id, $establishmentId, $data);
            flash_success('Élève mis à jour avec succès.');
            $this->redirect('/students/' . $id);
        } catch (\Exception $e) {
            flash_error('Erreur : ' . $e->getMessage());
            $this->redirect('/students/' . $id . '/edit');
        }
    }

    // ── Changer statut ───────────────────────────────────────────────────────

    public function setStatus(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $newStatus = $request->post('status', 'inactive');
        $allowed   = ['active', 'inactive', 'transferred', 'expelled'];

        if (!in_array($newStatus, $allowed, true)) {
            flash_error('Statut invalide.');
            $this->redirect('/students/' . $id);
            return;
        }

        $this->students->setStatus((int) $id, $establishmentId, $newStatus);
        flash_success('Statut mis à jour.');
        $this->redirect('/students/' . $id);
    }

    // ── Rattacher un tuteur ──────────────────────────────────────────────────

    public function attachGuardian(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $guardianId = (int) $request->post('guardian_id', '0');
        $isPrimary  = (bool) $request->post('is_primary', false);

        try {
            $this->students->attachGuardian((int) $id, $guardianId, $establishmentId, $isPrimary);
            flash_success('Tuteur rattaché avec succès.');
        } catch (\Exception $e) {
            flash_error($e->getMessage());
        }

        $this->redirect('/students/' . $id);
    }

    // ── Supprimer (soft) ─────────────────────────────────────────────────────

    public function destroy(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        try {
            $this->students->softDelete((int) $id, $establishmentId);
            flash_success('Élève archivé.');
            $this->redirect('/students');
        } catch (\Exception $e) {
            flash_error($e->getMessage());
            $this->redirect('/students/' . $id);
        }
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function notFound(string $msg = 'Introuvable.'): void
    {
        flash_error($msg);
        $this->redirect('/students');
    }
}
