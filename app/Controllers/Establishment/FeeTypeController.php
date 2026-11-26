<?php

declare(strict_types=1);

namespace App\Controllers\Establishment;

use App\Core\Controller;
use App\Core\Request;
use App\Core\TenantContext;
use App\Repositories\FeeTypeRepository;

class FeeTypeController extends Controller
{
    private FeeTypeRepository $feeTypes;

    public function __construct()
    {
        $this->feeTypes = new FeeTypeRepository();
    }

    public function index(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();
        $stats = $this->feeTypes->getUsageStats($establishmentId);

        $this->render('fee-types.index', [
            'title'    => 'Types de frais',
            'feeTypes' => $stats,
        ], 'main');
    }

    public function store(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $data   = $request->post();
        $errors = $this->validate($data, [
            'name'              => 'required|max:100',
            'default_amount'    => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:annual,trimester,monthly,once',
        ]);

        if (!empty($errors)) {
            flash_error('Données invalides : ' . implode(', ', $errors));
            $this->redirect('/fee-types');
            return;
        }

        try {
            $this->feeTypes->create($establishmentId, $data);
            flash_success('Type de frais créé.');
        } catch (\Exception $e) {
            flash_error('Erreur : ' . $e->getMessage());
        }

        $this->redirect('/fee-types');
    }

    public function update(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $data   = $request->post();
        $errors = $this->validate($data, [
            'name'              => 'required|max:100',
            'default_amount'    => 'required|numeric|min:0',
            'payment_frequency' => 'required|in:annual,trimester,monthly,once',
        ]);

        if (!empty($errors)) {
            flash_error('Données invalides.');
            $this->redirect('/fee-types');
            return;
        }

        try {
            $this->feeTypes->update((int) $id, $establishmentId, $data);
            flash_success('Type de frais mis à jour.');
        } catch (\Exception $e) {
            flash_error('Erreur : ' . $e->getMessage());
        }

        $this->redirect('/fee-types');
    }

    public function toggle(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $this->feeTypes->toggleActive((int) $id, $establishmentId);
        flash_success('Statut modifié.');
        $this->redirect('/fee-types');
    }

    public function destroy(Request $request, string $id): void
    {
        $establishmentId = TenantContext::requireTenantId();

        try {
            $this->feeTypes->softDelete((int) $id, $establishmentId);
            flash_success('Type de frais supprimé.');
        } catch (\Exception $e) {
            flash_error($e->getMessage());
        }

        $this->redirect('/fee-types');
    }

    public function reorder(Request $request): void
    {
        $establishmentId = TenantContext::requireTenantId();

        $ids = $request->post('order', []);

        if (!is_array($ids)) {
            $this->json(['success' => false]);
            return;
        }

        $this->feeTypes->updateOrder($establishmentId, array_map('intval', $ids));
        $this->json(['success' => true]);
    }
}
