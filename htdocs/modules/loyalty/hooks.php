<?php
// Loyalty Module Hooks
function loyalty_after_order_created($params) {
    global $db;
    
    $order = $params['order'];
    $config = getModuleManager()->getModuleConfig('loyalty');
    $settings = $config['settings'];
    
    try {
        // Get or create loyalty customer
        $customer = $db->fetch(
            "SELECT lc.* FROM loyalty_customers lc 
             JOIN users u ON lc.customer_id = u.id 
             WHERE u.email = ?",
            [$order['customer_email']]
        );
        
        if (!$customer && $order['customer_email']) {
            // Create new loyalty customer
            $userId = $db->fetch("SELECT id FROM users WHERE email = ?", [$order['customer_email']]);
            if ($userId) {
                $customerId = $db->insert('loyalty_customers', [
                    'customer_id' => $userId['id'],
                    'points_balance' => $settings['welcome_bonus'],
                    'total_earned' => $settings['welcome_bonus'],
                    'join_date' => date('Y-m-d'),
                    'last_activity' => date('Y-m-d')
                ]);
                
                // Add welcome bonus points
                $db->insert('loyalty_points', [
                    'customer_id' => $customerId,
                    'points' => $settings['welcome_bonus'],
                    'type' => 'bonus',
                    'description' => 'Welcome bonus'
                ]);
                
                $customer = $db->fetch("SELECT * FROM loyalty_customers WHERE id = ?", [$customerId]);
            }
        }
        
        if ($customer) {
            // Calculate points earned
            $pointsEarned = floor($order['total_amount'] * $settings['points_per_currency']);
            
            if ($pointsEarned > 0) {
                // Add points
                $db->insert('loyalty_points', [
                    'customer_id' => $customer['id'],
                    'order_id' => $order['id'],
                    'points' => $pointsEarned,
                    'type' => 'earned',
                    'description' => 'Points from order #' . $order['id']
                ]);
                
                // Update customer balance
                $newBalance = $customer['points_balance'] + $pointsEarned;
                $newTotal = $customer['total_earned'] + $pointsEarned;
                $newSpent = $customer['total_spent'] + $order['total_amount'];
                
                // Update tier if enabled
                $newTier = $customer['tier'];
                if ($settings['enable_tier_system']) {
                    $newTier = calculateLoyaltyTier($newTotal);
                }
                
                $db->update('loyalty_customers', [
                    'points_balance' => $newBalance,
                    'total_earned' => $newTotal,
                    'total_spent' => $newSpent,
                    'tier' => $newTier,
                    'last_activity' => date('Y-m-d')
                ], 'id = ?', [$customer['id']]);
            }
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function loyalty_customer_registration($params) {
    global $db;
    
    $customerData = $params['customer_data'];
    $config = getModuleManager()->getModuleConfig('loyalty');
    $settings = $config['settings'];
    
    try {
        // Create loyalty customer record
        $customerId = $db->insert('loyalty_customers', [
            'customer_id' => $customerData['id'],
            'points_balance' => $settings['welcome_bonus'],
            'total_earned' => $settings['welcome_bonus'],
            'join_date' => date('Y-m-d'),
            'last_activity' => date('Y-m-d'),
            'referral_code' => generateReferralCode()
        ]);
        
        // Add welcome bonus points
        $db->insert('loyalty_points', [
            'customer_id' => $customerId,
            'points' => $settings['welcome_bonus'],
            'type' => 'bonus',
            'description' => 'Welcome bonus'
        ]);
        
        return ['success' => true, 'customer_id' => $customerId];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function loyalty_customer_info($params) {
    global $db;
    
    $customerId = $params['customer_id'];
    
    try {
        $loyaltyInfo = $db->fetch(
            "SELECT * FROM loyalty_customers WHERE customer_id = ?",
            [$customerId]
        );
        
        if ($loyaltyInfo) {
            // Get recent points activity
            $recentActivity = $db->fetchAll(
                "SELECT * FROM loyalty_points 
                 WHERE customer_id = ? 
                 ORDER BY created_at DESC LIMIT 10",
                [$loyaltyInfo['id']]
            );
            
            // Get available rewards
            $availableRewards = $db->fetchAll(
                "SELECT * FROM loyalty_rewards 
                 WHERE points_required <= ? AND is_active = 1
                 ORDER BY points_required ASC",
                [$loyaltyInfo['points_balance']]
            );
            
            return [
                'success' => true,
                'loyalty_info' => $loyaltyInfo,
                'recent_activity' => $recentActivity,
                'available_rewards' => $availableRewards
            ];
        }
        
        return ['success' => false, 'message' => 'Loyalty info not found'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function calculateLoyaltyTier($totalSpent) {
    if ($totalSpent >= 50000) return 'platinum';
    if ($totalSpent >= 20000) return 'gold';
    if ($totalSpent >= 5000) return 'silver';
    return 'bronze';
}

function generateReferralCode() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}
?>