<?php

declare(strict_types=1);

namespace App\Controllers\Establishment;

use App\Core\Controller;
use App\Core\Request;
use App\Core\TenantContext;
use App\Repositories\GuardianRepository;

class GuardianController extends Controller
{
    private GuardianRepository $guardians;

    public function __construct()
    {
        $this->guardians = new GuardianRepository();
    }

    public function index(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $page   = max(1, (int) $request->query('page', '1'));
        $search = trim((string) $request->query('search', ''));

        $result = $this->guardians->getPaginated($establishmentId, $page, 20, $search);

        $this->render('guardians.index', [
            'title'  => 'Parents & Tuteurs',
            'result' => $result,
            'search' => $search,
        ], 'main');
    }

    public function create(Request $request): void
    {
        TenantContext::requireTenantId();

        $this->render('guardians.create', [
            'title' => 'Ajouter un parent / tuteur',
        ], 'main');
    }

    public function store(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $data   = $request->post();
        $errors = $this->validate($data, [
            'first_name'   => 'required|max:100',
            'last_name'    => 'required|max:100',
            'phone'        => 'required|phone_sn',
            'email'        => 'nullable|email',
            'relationship' => 'required|in:parent,guardian,other',
        ]);

        if (!empty($errors)) {
            flash_error('Veuillez corriger les erreurs.');
            $this->render('guardians.create', [
                'title'  => 'Ajouter un parent / tuteur',
                'errors' => $errors,
                'old'    => $data,
            ], 'main');
            return;
        }

        try {
            $guardianId = $this->guardians->create($establishmentId, $data);
            flash_success('Tuteur ajouté avec succès.');
            $this->redirect('/guardians/' . $guardianId);
        } catch (\Exception $e) {
            flash_error('Erreur : ' . $e->getMessage());
            $this->render('guardians.create', [
                'title' => 'Ajouter un parent / tuteur',
                'old'   => $data,
            ], 'main');
        }
    }

    public function show(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $guardian = $this->guardians->findWithDetails((int) $id, $establishmentId);

        if (!$guardian) {
            flash_error('Tuteur introuvable.');
            $this->redirect('/guardians');
            return;
        }

        $this->render('guardians.show', [
            'title'    => $guardian['first_name'] . ' ' . $guardian['last_name'],
            'guardian' => $guardian,
        ], 'main');
    }

    public function edit(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $guardian = $this->guardians->findById((int) $id, $establishmentId);

        if (!$guardian) {
            flash_error('Tuteur introuvable.');
            $this->redirect('/guardians');
            return;
        }

        $this->render('guardians.edit', [
            'title'    => 'Modifier le tuteur',
            'guardian' => $guardian,
        ], 'main');
    }

    public function update(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $guardian = $this->guardians->findById((int) $id, $establishmentId);
        if (!$guardian) {
            flash_error('Tuteur introuvable.');
            $this->redirect('/guardians');
            return;
        }

        $data   = $request->post();
        $errors = $this->validate($data, [
            'first_name'   => 'required|max:100',
            'last_name'    => 'required|max:100',
            'phone'        => 'required|phone_sn',
            'email'        => 'nullable|email',
            'relationship' => 'required|in:parent,guardian,other',
        ]);

        if (!empty($errors)) {
            flash_error('Veuillez corriger les erreurs.');
            $this->render('guardians.edit', [
                'title'    => 'Modifier le tuteur',
                'guardian' => array_merge($guardian, $data),
                'errors'   => $errors,
            ], 'main');
            return;
        }

        try {
            $this->guardians->update((int) $id, $establishmentId, $data);
            flash_success('Tuteur mis à jour.');
            $this->redirect('/guardians/' . $id);
        } catch (\Exception $e) {
            flash_error('Erreur : ' . $e->getMessage());
            $this->redirect('/guardians/' . $id . '/edit');
        }
    }

    public function destroy(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        try {
            $this->guardians->softDelete((int) $id, $establishmentId);
            flash_success('Tuteur archivé.');
        } catch (\Exception $e) {
            flash_error($e->getMessage());
        }

        $this->redirect('/guardians');
    }

    // JSON autocomplete for student linking forms
    public function search(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();
        $term = trim((string) $request->query('q', ''));

        $results = $term !== ''
            ? $this->guardians->search($establishmentId, $term)
            : [];

        $this->json(['results' => $results]);
    }
}
