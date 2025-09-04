<?php
// Analytics Module Routes
return [
    'GET /analytics/dashboard' => 'modules/analytics/dashboard.php',
    'GET /analytics/reports' => 'modules/analytics/reports.php',
    'POST /analytics/export' => 'modules/analytics/export.php',
    'GET /analytics/api/sales' => 'modules/analytics/api/sales.php',
    'GET /analytics/api/items' => 'modules/analytics/api/items.php'
];
?>