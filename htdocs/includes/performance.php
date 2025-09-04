<?php
// Performance optimization utilities
class PerformanceOptimizer {
    
    // Cache configuration
    private static $cacheEnabled = true;
    private static $cacheDir = __DIR__ . '/../cache';
    private static $cacheTimeout = 3600; // 1 hour
    
    // Database optimization
    public static function optimizeDatabase() {
        global $db;
        
        try {
            // Optimize SQLite database
            $db->query('PRAGMA optimize');
            $db->query('PRAGMA vacuum');
            $db->query('PRAGMA wal_checkpoint(TRUNCATE)');
            
            return true;
        } catch (Exception $e) {
            error_log('Database optimization failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // File-based caching
    public static function cacheGet($key) {
        if (!self::$cacheEnabled) {
            return false;
        }
        
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        if (filemtime($cacheFile) < (time() - self::$cacheTimeout)) {
            unlink($cacheFile);
            return false;
        }
        
        $data = file_get_contents($cacheFile);
        return unserialize($data);
    }
    
    public static function cacheSet($key, $data, $timeout = null) {
        if (!self::$cacheEnabled) {
            return false;
        }
        
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        $timeout = $timeout ?: self::$cacheTimeout;
        
        $data = serialize($data);
        file_put_contents($cacheFile, $data);
        
        // Set timeout
        touch($cacheFile, time() + $timeout);
        
        return true;
    }
    
    public static function cacheDelete($key) {
        $cacheFile = self::$cacheDir . '/' . md5($key) . '.cache';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            return true;
        }
        
        return false;
    }
    
    public static function cacheClear() {
        if (!is_dir(self::$cacheDir)) {
            return true;
        }
        
        $files = glob(self::$cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    // Output buffering and compression
    public static function startOutputBuffering() {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
    }
    
    public static function endOutputBuffering() {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }
    
    // CSS/JS minification
    public static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary semicolons
        $css = str_replace('; ', ';', $css);
        $css = str_replace(': ', ':', $css);
        $css = str_replace(' {', '{', $css);
        $css = str_replace('{ ', '{', $css);
        $css = str_replace(', ', ',', $css);
        $css = str_replace('} ', '}', $css);
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    public static function minifyJS($js) {
        // Remove comments
        $js = preg_replace('/((?:\/\*(?:[^*]|(?:\*[^/]))*\*\/)|(?:\/\/.*))/', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove unnecessary spaces
        $js = str_replace(' ; ', ';', $js);
        $js = str_replace(' = ', '=', $js);
        $js = str_replace(' , ', ',', $js);
        $js = str_replace(' ;', ';', $js);
        $js = str_replace(' ; ', ';', $js);
        
        return trim($js);
    }
    
    // Image optimization
    public static function optimizeImage($sourcePath, $destPath, $quality = 75) {
        if (!file_exists($sourcePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($sourcePath);
        
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                imagejpeg($image, $destPath, $quality);
                break;
                
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                imagepng($image, $destPath, 9);
                break;
                
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                imagegif($image, $destPath);
                break;
                
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                imagewebp($image, $destPath, $quality);
                break;
                
            default:
                return false;
        }
        
        imagedestroy($image);
        return true;
    }
    
    // Lazy loading configuration
    public static function getLazyLoadingConfig() {
        return [
            'enabled' => true,
            'threshold' => 200,
            'root_margin' => '50px',
            'placeholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMSIgaGVpZ2h0PSIxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjwvc3ZnPg=='
        ];
    }
    
    // Mobile optimization
    public static function isMobile() {
        return preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $_SERVER['HTTP_USER_AGENT']);
    }
    
    public static function getMobileOptimizedConfig() {
        return [
            'compress_images' => true,
            'lazy_load' => true,
            'reduce_animations' => true,
            'simplify_layout' => true,
            'cache_timeout' => 7200 // 2 hours for mobile
        ];
    }
    
    // Database query optimization
    public static function optimizeQuery($sql, $params = []) {
        global $db;
        
        // Add EXPLAIN for debugging (remove in production)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $explainSql = "EXPLAIN QUERY PLAN " . $sql;
            $explainResult = $db->fetchAll($explainSql, $params);
            error_log('Query Explain: ' . json_encode($explainResult));
        }
        
        return $db->fetchAll($sql, $params);
    }
    
    // Session optimization
    public static function optimizeSession() {
        // Use files for session storage (more efficient than database for small sessions)
        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', self::$cacheDir . '/sessions');
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => 7200, // 2 hours
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    // Memory optimization
    public static function optimizeMemory() {
        // Increase memory limit if needed
        if (defined('MEMORY_LIMIT')) {
            ini_set('memory_limit', MEMORY_LIMIT);
        }
        
        // Clean up variables
        $vars = get_defined_vars();
        foreach ($vars as $key => $value) {
            if (strpos($key, 'temp_') === 0 || strpos($key, 'cache_') === 0) {
                unset($$key);
            }
        }
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    // CDN configuration
    public static function getCDNConfig() {
        return [
            'enabled' => false, // Enable if you have a CDN
            'base_url' => '',
            'assets_url' => '',
            'version' => '1.0.0'
        ];
    }
    
    // Performance monitoring
    public static function startTimer($name) {
        global $performance_timers;
        $performance_timers[$name] = microtime(true);
    }
    
    public static function endTimer($name) {
        global $performance_timers;
        
        if (!isset($performance_timers[$name])) {
            return 0;
        }
        
        $time = microtime(true) - $performance_timers[$name];
        unset($performance_timers[$name]);
        
        return $time;
    }
    
    public static function logPerformance($data) {
        $logFile = self::$cacheDir . '/performance.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

// Initialize performance optimizations
if (!defined('DISABLE_OPTIMIZATIONS')) {
    // Start output buffering
    PerformanceOptimizer::startOutputBuffering();
    
    // Optimize session
    PerformanceOptimizer::optimizeSession();
    
    // Create cache directory if it doesn't exist
    if (!is_dir(PerformanceOptimizer::$cacheDir)) {
        mkdir(PerformanceOptimizer::$cacheDir, 0755, true);
    }
    
    // Register shutdown function
    register_shutdown_function(function() {
        PerformanceOptimizer::endOutputBuffering();
        PerformanceOptimizer::optimizeMemory();
    });
}
?>