<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_role(['company', 'agent']);
require_csrf();

$companyId = managed_company_id();
$blockId = (int) ($_POST['block_id'] ?? 0);

if (!$companyId || $blockId < 1) {
    set_flash('Invalid availability block request.', 'danger');
    redirect('dashboard.php?section=vehicles');
}

$deleted = require_db()->prepare('DELETE FROM availability_blocks WHERE id = ? AND company_id = ?');
$deleted->execute([$blockId, $companyId]);

set_flash('Availability block removed.', 'success');
redirect('dashboard.php?section=vehicles');


