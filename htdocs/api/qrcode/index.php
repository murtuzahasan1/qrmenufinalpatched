<?php
// Load the configuration file FIRST to define APP_ROOT and other constants.
require_once __DIR__ . '/../../config/config.php';

// Now that APP_ROOT is defined, we can use it.
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/utils.php';
// This is the fix: Ensure the QRCodeGenerator class is loaded before it is used.
require_once APP_ROOT . '/includes/qrcode.php';

class QRCodeAPI {
    private $auth;
    private $utils;
    private $qrGenerator;
    private $db;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->utils = new Utils();
        $this->qrGenerator = new QRCodeGenerator();
        $this->db = Database::getInstance();
    }

    public function handleRequest() {
        // Require authentication for all QR code endpoints
        $this->auth->requireAuth();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'generate':
                        $this->generateQR();
                        break;
                    case 'batch':
                        $this->generateBatchQR();
                        break;
                    case 'download':
                        $this->downloadQR();
                        break;
                    case 'parse':
                        $this->parseQR();
                        break;
                    default:
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                }
                break;
            case 'POST':
                switch ($action) {
                    case 'validate':
                        $this->validateQR();
                        break;
                    case 'create-pdf':
                        $this->createQRPdf();
                        break;
                    default:
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                }
                break;
            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }

    private function generateQR() {
        $currentUser = $this->auth->getCurrentUser();
        $type = $_GET['type'] ?? 'branch';
        
        switch ($type) {
            case 'branch':
                $branchId = $_GET['branch_id'] ?? null;
                $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];
                
                if (!$branchId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Branch ID required'], 400);
                }

                // Check permissions
                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $restaurantId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $qrUrl = $this->qrGenerator->generateBranchQR($branchId, $restaurantId);
                $this->utils->jsonResponse(['success' => true, 'qr_url' => $qrUrl]);
                break;

            case 'table':
                $branchId = $_GET['branch_id'] ?? null;
                $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];
                $tableNumber = $_GET['table_number'] ?? null;
                
                if (!$branchId || !$tableNumber) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Branch ID and table number required'], 400);
                }

                // Check permissions
                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $restaurantId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $qrUrl = $this->qrGenerator->generateTableQR($branchId, $restaurantId, $tableNumber);
                $this->utils->jsonResponse(['success' => true, 'qr_url' => $qrUrl]);
                break;

            case 'menu_item':
                $itemId = $_GET['item_id'] ?? null;
                $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];
                
                if (!$itemId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Item ID required'], 400);
                }

                // Check permissions
                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $restaurantId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $qrUrl = $this->qrGenerator->generateMenuItemQR($itemId, $restaurantId);
                $this->utils->jsonResponse(['success' => true, 'qr_url' => $qrUrl]);
                break;

            case 'order':
                $orderId = $_GET['order_id'] ?? null;
                $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];
                
                if (!$orderId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Order ID required'], 400);
                }

                // Check permissions
                if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $restaurantId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
                }

                $qrUrl = $this->qrGenerator->generateOrderQR($orderId, $restaurantId);
                $this->utils->jsonResponse(['success' => true, 'qr_url' => $qrUrl]);
                break;

            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid QR type'], 400);
        }
    }

    private function generateBatchQR() {
        $currentUser = $this->auth->getCurrentUser();
        $type = $_GET['type'] ?? 'branch';
        $restaurantId = $_GET['restaurant_id'] ?? $currentUser['restaurant_id'];

        // Check permissions
        if ($currentUser['role'] !== 'super_admin' && $currentUser['restaurant_id'] != $restaurantId) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
        }

        $items = [];

        switch ($type) {
            case 'branch':
                $items = $this->db->fetchAll(
                    "SELECT id, restaurant_id FROM branches WHERE restaurant_id = ? AND status = 1",
                    [$restaurantId]
                );
                break;

            case 'table':
                $branchId = $_GET['branch_id'] ?? null;
                if (!$branchId) {
                    $this->utils->jsonResponse(['success' => false, 'message' => 'Branch ID required for table QR generation'], 400);
                }
                
                // Generate QR codes for tables 1-20 (you can customize this)
                for ($i = 1; $i <= 20; $i++) {
                    $items[] = [
                        'branch_id' => $branchId,
                        'restaurant_id' => $restaurantId,
                        'table_number' => $i
                    ];
                }
                break;

            case 'menu_item':
                $items = $this->db->fetchAll(
                    "SELECT id, restaurant_id FROM menu_items WHERE restaurant_id = ? AND available = 1",
                    [$restaurantId]
                );
                break;

            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid QR type'], 400);
        }

        if (empty($items)) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'No items found for QR generation'], 404);
        }

        $results = $this->qrGenerator->generateBatchQR($items, $type);
        
        $this->utils->jsonResponse([
            'success' => true,
            'message' => 'Batch QR codes generated successfully',
            'count' => count($results),
            'qrcodes' => $results
        ]);
    }

    private function downloadQR() {
        $currentUser = $this->auth->getCurrentUser();
        $qrUrl = $_GET['qr_url'] ?? null;
        $filename = $_GET['filename'] ?? null;

        if (!$qrUrl) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'QR URL required'], 400);
        }

        $result = $this->qrGenerator->downloadQRCode($qrUrl, $filename);
        
        if ($result['success']) {
            $this->utils->jsonResponse([
                'success' => true,
                'message' => 'QR code downloaded successfully',
                'filepath' => $result['filepath'],
                'filename' => $result['filename']
            ]);
        } else {
            $this->utils->jsonResponse(['success' => false, 'message' => $result['message']], 500);
        }
    }

    private function parseQR() {
        $qrData = $_GET['data'] ?? null;

        if (!$qrData) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'QR data required'], 400);
        }

        $result = $this->qrGenerator->parseQRData($qrData);
        
        if ($result['success']) {
            $this->utils->jsonResponse([
                'success' => true,
                'data' => $result['data']
            ]);
        } else {
            $this->utils->jsonResponse(['success' => false, 'message' => $result['message']], 400);
        }
    }

    private function validateQR() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
        }

        $result = $this->qrGenerator->validateQRData($data);
        
        if ($result['success']) {
            $this->utils->jsonResponse([
                'success' => true,
                'message' => 'QR data is valid',
                'data' => $data
            ]);
        } else {
            $this->utils->jsonResponse(['success' => false, 'message' => $result['message']], 400);
        }
    }

    private function createQRPdf() {
        $currentUser = $this->auth->getCurrentUser();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
        }

        $qrCodes = $data['qrcodes'] ?? [];
        $title = $data['title'] ?? 'QR Codes';

        if (empty($qrCodes)) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'No QR codes provided'], 400);
        }

        $result = $this->qrGenerator->createQRPdf($qrCodes, $title);
        
        if ($result['success']) {
            $this->utils->jsonResponse([
                'success' => true,
                'message' => 'PDF created successfully',
                'filepath' => $result['filepath'],
                'filename' => $result['filename']
            ]);
        } else {
            $this->utils->jsonResponse(['success' => false, 'message' => $result['message']], 500);
        }
    }
}

// Handle the request
$qrAPI = new QRCodeAPI();
$qrAPI->handleRequest();
?>