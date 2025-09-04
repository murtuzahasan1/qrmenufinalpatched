<?php
// Loyalty Module Routes
return [
    'GET /loyalty/dashboard' => 'modules/loyalty/dashboard.php',
    'GET /loyalty/rewards' => 'modules/loyalty/rewards.php',
    'POST /loyalty/redeem' => 'modules/loyalty/redeem.php',
    'GET /loyalty/api/customer' => 'modules/loyalty/api/customer.php',
    'GET /loyalty/api/rewards' => 'modules/loyalty/api/rewards.php',
    'POST /loyalty/api/referral' => 'modules/loyalty/api/referral.php'
];
?>