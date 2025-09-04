<?php
require_once APP_ROOT . '/includes/utils.php';

class QRCodeGenerator {
    private $utils;

    public function __construct() {
        $this->utils = new Utils();
    }

    public function generateQRCode($data, $size = QR_SIZE, $margin = QR_MARGIN) {
        // Using Google Charts API for QR code generation
        $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data) . "&chld=H|{$margin}";
        return $url;
    }

    public function generateBranchQR($branchId, $restaurantId) {
        $data = json_encode([
            'type' => 'branch',
            'branch_id' => $branchId,
            'restaurant_id' => $restaurantId,
            'generated' => date('Y-m-d H:i:s')
        ]);
        
        return $this->generateQRCode($data);
    }

    public function generateTableQR($branchId, $restaurantId, $tableNumber) {
        $data = json_encode([
            'type' => 'table',
            'branch_id' => $branchId,
            'restaurant_id' => $restaurantId,
            'table_number' => $tableNumber,
            'generated' => date('Y-m-d H:i:s')
        ]);
        
        return $this->generateQRCode($data);
    }

    public function generateMenuItemQR($itemId, $restaurantId) {
        $data = json_encode([
            'type' => 'menu_item',
            'item_id' => $itemId,
            'restaurant_id' => $restaurantId,
            'generated' => date('Y-m-d H:i:s')
        ]);
        
        return $this->generateQRCode($data);
    }

    public function generateOrderQR($orderId, $restaurantId) {
        $data = json_encode([
            'type' => 'order',
            'order_id' => $orderId,
            'restaurant_id' => $restaurantId,
            'generated' => date('Y-m-d H:i:s')
        ]);
        
        return $this->generateQRCode($data);
    }

    public function parseQRData($qrData) {
        try {
            $data = json_decode($qrData, true);
            
            if (!$data || !isset($data['type'])) {
                return ['success' => false, 'message' => 'Invalid QR code data'];
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to parse QR code data'];
        }
    }

    public function validateQRData($data) {
        $requiredFields = ['type', 'restaurant_id'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }

        // Validate type-specific fields
        switch ($data['type']) {
            case 'branch':
                if (!isset($data['branch_id'])) {
                    return ['success' => false, 'message' => 'Missing branch_id for branch type'];
                }
                break;
            case 'table':
                if (!isset($data['branch_id']) || !isset($data['table_number'])) {
                    return ['success' => false, 'message' => 'Missing branch_id or table_number for table type'];
                }
                break;
            case 'menu_item':
                if (!isset($data['item_id'])) {
                    return ['success' => false, 'message' => 'Missing item_id for menu_item type'];
                }
                break;
            case 'order':
                if (!isset($data['order_id'])) {
                    return ['success' => false, 'message' => 'Missing order_id for order type'];
                }
                break;
            default:
                return ['success' => false, 'message' => 'Invalid QR code type'];
        }

        return ['success' => true, 'message' => 'Valid QR code data'];
    }

    public function downloadQRCode($qrUrl, $filename = null) {
        if (!$filename) {
            $filename = 'qr_code_' . time() . '.png';
        }

        $imageData = file_get_contents($qrUrl);
        
        if ($imageData === false) {
            return ['success' => false, 'message' => 'Failed to download QR code'];
        }

        $filepath = UPLOAD_PATH . '/qrcodes/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        if (file_put_contents($filepath, $imageData)) {
            return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to save QR code'];
        }
    }

    public function generateBatchQR($items, $type = 'branch') {
        $results = [];
        
        foreach ($items as $item) {
            $qrUrl = null;
            $filename = null;

            switch ($type) {
                case 'branch':
                    $qrUrl = $this->generateBranchQR($item['id'], $item['restaurant_id']);
                    $filename = "branch_{$item['id']}_qr.png";
                    break;
                case 'table':
                    $qrUrl = $this->generateTableQR($item['branch_id'], $item['restaurant_id'], $item['table_number']);
                    $filename = "table_{$item['branch_id']}_{$item['table_number']}_qr.png";
                    break;
                case 'menu_item':
                    $qrUrl = $this->generateMenuItemQR($item['id'], $item['restaurant_id']);
                    $filename = "item_{$item['id']}_qr.png";
                    break;
            }

            if ($qrUrl) {
                $downloadResult = $this->downloadQRCode($qrUrl, $filename);
                if ($downloadResult['success']) {
                    $results[] = [
                        'item' => $item,
                        'qr_url' => $qrUrl,
                        'filepath' => $downloadResult['filepath'],
                        'filename' => $downloadResult['filename']
                    ];
                }
            }
        }

        return $results;
    }

    public function createQRPdf($qrCodes, $title = 'QR Codes') {
        // This would require a PDF library like TCPDF or FPDF
        // For now, we'll return a placeholder implementation
        
        $pdfContent = "PDF Generation for QR Codes\n\n";
        $pdfContent .= "Title: $title\n";
        $pdfContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($qrCodes as $qrCode) {
            $pdfContent .= "QR Code: {$qrCode['filename']}\n";
            $pdfContent .= "Path: {$qrCode['filepath']}\n\n";
        }

        $filename = "qr_codes_" . time() . ".txt";
        $filepath = UPLOAD_PATH . '/qrcodes/' . $filename;
        
        if (file_put_contents($filepath, $pdfContent)) {
            return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Failed to create QR code file'];
        }
    }
}
?>