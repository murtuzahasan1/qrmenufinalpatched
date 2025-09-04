<?php
require_once APP_ROOT . '/config/config.php';

class ModuleManager {
    private $db;
    private $enabledModules = [];
    private static $instance = null;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadEnabledModules();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnabledModules() {
        if (file_exists(ENABLED_MODULES_FILE)) {
            $content = file_get_contents(ENABLED_MODULES_FILE);
            $this->enabledModules = json_decode($content, true) ?: [];
        }
    }

    private function saveEnabledModules() {
        file_put_contents(ENABLED_MODULES_FILE, json_encode($this->enabledModules));
    }

    public function getAvailableModules() {
        $modules = [];
        
        if (is_dir(MODULES_PATH)) {
            $moduleDirs = scandir(MODULES_PATH);
            
            foreach ($moduleDirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                
                $modulePath = MODULES_PATH . '/' . $dir;
                $configPath = $modulePath . '/config.json';
                
                if (is_dir($modulePath) && file_exists($configPath)) {
                    $config = json_decode(file_get_contents($configPath), true);
                    
                    if ($config) {
                        $modules[] = [
                            'id' => $dir,
                            'name' => $config['name'] ?? $dir,
                            'version' => $config['version'] ?? '1.0.0',
                            'description' => $config['description'] ?? '',
                            'author' => $config['author'] ?? '',
                            'enabled' => in_array($dir, $this->enabledModules),
                            'config' => $config
                        ];
                    }
                }
            }
        }
        
        return $modules;
    }

    public function enableModule($moduleId) {
        if (in_array($moduleId, $this->enabledModules)) {
            return ['success' => true, 'message' => 'Module already enabled'];
        }

        // Check if module exists
        $modulePath = MODULES_PATH . '/' . $moduleId;
        if (!is_dir($modulePath)) {
            return ['success' => false, 'message' => 'Module not found'];
        }

        // Check if module has install script
        $installScript = $modulePath . '/install.php';
        if (file_exists($installScript)) {
            include_once $installScript;
            if (function_exists('module_install')) {
                $result = module_install();
                if (!$result['success']) {
                    return $result;
                }
            }
        }

        $this->enabledModules[] = $moduleId;
        $this->saveEnabledModules();

        return ['success' => true, 'message' => 'Module enabled successfully'];
    }

    public function disableModule($moduleId) {
        if (!in_array($moduleId, $this->enabledModules)) {
            return ['success' => true, 'message' => 'Module already disabled'];
        }

        // Check if module has uninstall script
        $modulePath = MODULES_PATH . '/' . $moduleId;
        $uninstallScript = $modulePath . '/uninstall.php';
        
        if (file_exists($uninstallScript)) {
            include_once $uninstallScript;
            if (function_exists('module_uninstall')) {
                $result = module_uninstall();
                if (!$result['success']) {
                    return $result;
                }
            }
        }

        $this->enabledModules = array_diff($this->enabledModules, [$moduleId]);
        $this->saveEnabledModules();

        return ['success' => true, 'message' => 'Module disabled successfully'];
    }

    public function getEnabledModules() {
        return $this->enabledModules;
    }

    public function isModuleEnabled($moduleId) {
        return in_array($moduleId, $this->enabledModules);
    }

    public function getModuleConfig($moduleId) {
        if (!$this->isModuleEnabled($moduleId)) {
            return null;
        }

        $configPath = MODULES_PATH . '/' . $moduleId . '/config.json';
        if (file_exists($configPath)) {
            return json_decode(file_get_contents($configPath), true);
        }

        return null;
    }

    public function executeModuleHook($hookName, $params = []) {
        $results = [];
        
        foreach ($this->enabledModules as $moduleId) {
            $modulePath = MODULES_PATH . '/' . $moduleId;
            $hookFile = $modulePath . '/hooks.php';
            
            if (file_exists($hookFile)) {
                include_once $hookFile;
                
                $functionName = $moduleId . '_' . $hookName;
                if (function_exists($functionName)) {
                    $result = $functionName($params);
                    $results[$moduleId] = $result;
                }
            }
        }
        
        return $results;
    }

    public function getModuleRoutes() {
        $routes = [];
        
        foreach ($this->enabledModules as $moduleId) {
            $modulePath = MODULES_PATH . '/' . $moduleId;
            $routesFile = $modulePath . '/routes.php';
            
            if (file_exists($routesFile)) {
                $moduleRoutes = include $routesFile;
                if (is_array($moduleRoutes)) {
                    $routes = array_merge($routes, $moduleRoutes);
                }
            }
        }
        
        return $routes;
    }

    public function getModuleAssets() {
        $assets = [];
        
        foreach ($this->enabledModules as $moduleId) {
            $modulePath = MODULES_PATH . '/' . $moduleId;
            $assetsPath = $modulePath . '/assets';
            
            if (is_dir($assetsPath)) {
                $assets[$moduleId] = [
                    'css' => glob($assetsPath . '/*.css'),
                    'js' => glob($assetsPath . '/*.js'),
                    'path' => '/modules/' . $moduleId . '/assets'
                ];
            }
        }
        
        return $assets;
    }

    public function installModule($zipFile) {
        // This would handle uploading and extracting ZIP files
        // For now, we'll return a placeholder
        return ['success' => false, 'message' => 'Module installation from ZIP not implemented yet'];
    }

    public function updateModule($moduleId) {
        // This would handle updating modules
        return ['success' => false, 'message' => 'Module updates not implemented yet'];
    }

    public function deleteModule($moduleId) {
        // First disable the module
        $disableResult = $this->disableModule($moduleId);
        if (!$disableResult['success']) {
            return $disableResult;
        }

        // Then remove the module directory
        $modulePath = MODULES_PATH . '/' . $moduleId;
        if (is_dir($modulePath)) {
            $this->removeDirectory($modulePath);
        }

        return ['success' => true, 'message' => 'Module deleted successfully'];
    }

    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}

// Helper function to get module instance
function getModuleManager() {
    return ModuleManager::getInstance();
}

// Execute module hooks automatically
function executeModuleHooks($hookName, $params = []) {
    $manager = getModuleManager();
    return $manager->executeModuleHook($hookName, $params);
}

// Check if module is enabled
function isModuleEnabled($moduleId) {
    $manager = getModuleManager();
    return $manager->isModuleEnabled($moduleId);
}
?>