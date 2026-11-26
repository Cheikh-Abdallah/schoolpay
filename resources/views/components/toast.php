<?php

declare(strict_types=1);

use App\Helpers\FlashHelper;

$flashes = FlashHelper::all();
if (!empty($flashes)):
    foreach ($flashes as $flash):
?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
        <span><?= e($flash['message']) ?></span>
        <button type="button" style="background:none;border:none;cursor:pointer;font-weight:bold;color:inherit;" onclick="this.parentElement.remove();">&times;</button>
    </div>
<?php
    endforeach;
endif;
?>
