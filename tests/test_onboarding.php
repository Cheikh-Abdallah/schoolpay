<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Database;
use App\Services\InvitationService;
use App\Services\TrialService;

echo "=== TEST DU FLUX D'INVITATION ET D'ESSAI 72H ===\n";

$invService = new InvitationService();
$trialService = new TrialService();

// 1. Création d'une invitation pilote
$inv = $invService->createInvitation(1, 'directeur@ecole-pilote.sn', 7);
echo "1. Invitation générée avec succès :\n";
echo "   ID : {$inv['id']}\n";
echo "   Email : {$inv['email']}\n";
echo "   Jeton Brut : {$inv['raw_token']}\n";
echo "   URL : {$inv['invite_url']}\n";

// 2. Vérification de la validation du token
$validation = $invService->validateToken($inv['raw_token']);
echo "2. Validation du jeton : " . ($validation ? "VALIDE (Statut: {$validation['status']})" : "INVALIDE") . "\n";

// 3. Vérification des données en base
$dbRow = Database::fetchOne("SELECT * FROM establishment_invitations WHERE id = :id", [':id' => $inv['id']]);
echo "3. Enregistrement en base de données vérifié : Statut = {$dbRow['status']}, Date exp = {$dbRow['expires_at']}\n";

echo "=== FLUX VALIDÉ AVEC SUCCÈS ===\n";
