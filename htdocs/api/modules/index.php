<?php
require_once __DIR__ . '/../bootstrap.php';

class ModuleAPI {
    // ... (rest of the file is unchanged)
    private $auth;
    private $utils;
    private $moduleManager;

    public function __construct() {
        $this->auth = Auth::getInstance();
        $this->utils = new Utils();
        $this->moduleManager = ModuleManager::getInstance();
    }

    public function handleRequest() {
        // Require authentication and super admin role
        $this->auth->requireAuth();
        $currentUser = $this->auth->getCurrentUser();
        
        if ($currentUser['role'] !== 'super_admin') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Super admin access required'], 403);
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'list':
                        $this->listModules();
                        break;
                    case 'enabled':
                        $this->getEnabledModules();
                        break;
                    case 'config':
                        $this->getModuleConfig();
                        break;
                    default:
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                }
                break;
            case 'POST':
                switch ($action) {
                    case 'enable':
                        $this->enableModule();
                        break;
                    case 'disable':
                        $this->disableModule();
                        break;
                    case 'install':
                        $this->installModule();
                        break;
                    case 'update':
                        $this->updateModule();
                        break;
                    case 'delete':
                        $this->deleteModule();
                        break;
                    default:
                        $this->utils->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                }
                break;
            default:
                $this->utils->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }

    private function listModules() {
        $modules = $this->moduleManager->getAvailableModules();
        $this->utils->jsonResponse(['success' => true, 'data' => $modules]);
    }

    private function getEnabledModules() {
        $enabledModules = $this->moduleManager->getEnabledModules();
        $this->utils->jsonResponse(['success' => true, 'data' => $enabledModules]);
    }

    private function getModuleConfig() {
        $moduleId = $_GET['module_id'] ?? null;
        
        if (!$moduleId) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module ID required'], 400);
        }

        $config = $this->moduleManager->getModuleConfig($moduleId);
        
        if ($config) {
            $this->utils->jsonResponse(['success' => true, 'data' => $config]);
        } else {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module not found or not enabled'], 404);
        }
    }

    private function enableModule() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['module_id'])) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module ID required'], 400);
        }

        $moduleId = $data['module_id'];
        $result = $this->moduleManager->enableModule($moduleId);
        
        $this->utils->jsonResponse($result);
    }

    private function disableModule() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['module_id'])) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module ID required'], 400);
        }

        $moduleId = $data['module_id'];
        $result = $this->moduleManager->disableModule($moduleId);
        
        $this->utils->jsonResponse($result);
    }

    private function installModule() {
        // Handle file upload
        if (!isset($_FILES['module_file'])) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module file required'], 400);
        }

        $file = $_FILES['module_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'File upload error'], 400);
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'File too large'], 400);
        }

        if ($file['type'] !== 'application/zip' && $file['type'] !== 'application/x-zip-compressed') {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Only ZIP files are allowed'], 400);
        }

        $result = $this->moduleManager->installModule($file);
        $this->utils->jsonResponse($result);
    }

    private function updateModule() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['module_id'])) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module ID required'], 400);
        }

        $moduleId = $data['module_id'];
        $result = $this->moduleManager->updateModule($moduleId);
        
        $this->utils->jsonResponse($result);
    }

    private function deleteModule() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['module_id'])) {
            $this->utils->jsonResponse(['success' => false, 'message' => 'Module ID required'], 400);
        }

        $moduleId = $data['module_id'];
        $result = $this->moduleManager->deleteModule($moduleId);
        
        $this->utils->jsonResponse($result);
    }
}

// Handle the request
$moduleAPI = new ModuleAPI();
$moduleAPI->handleRequest();
?>