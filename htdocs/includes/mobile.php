<?php
// Mobile optimization utilities
class MobileOptimizer {
    
    // Detect mobile device
    public static function isMobile() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $mobileAgents = [
            'android', 'avantgo', 'blackberry', 'bolt', 'boost', 'cricket', 'docomo',
            'fone', 'hiptop', 'mini', 'mobi', 'palm', 'phone', 'pie', 'tablet',
            'up\.browser', 'up\.link', 'webos', 'wos', 'iphone', 'ipod', 'ipad'
        ];
        
        return preg_match('/(' . implode('|', $mobileAgents) . ')/i', $userAgent);
    }
    
    // Get mobile-optimized configuration
    public static function getConfig() {
        return [
            'compress_images' => true,
            'lazy_load' => true,
            'reduce_animations' => true,
            'simplify_layout' => true,
            'cache_timeout' => 7200,
            'max_image_size' => 800,
            'enable_amp' => false,
            'touch_friendly' => true,
            'fast_load' => true
        ];
    }
    
    // Generate mobile-optimized HTML
    public static function optimizeHTML($html) {
        if (!self::isMobile()) {
            return $html;
        }
        
        $config = self::getConfig();
        
        // Remove large images
        if ($config['compress_images']) {
            $html = preg_replace('/<img[^>]+>/i', '<img loading="lazy" style="max-width: 100%; height: auto;">', $html);
        }
        
        // Simplify layout
        if ($config['simplify_layout']) {
            $html = str_replace(['class="large"', 'class="desktop-only"'], '', $html);
        }
        
        // Add viewport meta tag if not present
        if (strpos($html, 'viewport') === false) {
            $html = str_replace('<head>', '<head><meta name="viewport" content="width=device-width, initial-scale=1.0">', $html);
        }
        
        // Add touch optimization
        if ($config['touch_friendly']) {
            $html = str_replace('<button', '<button style="min-height: 44px; min-width: 44px;"', $html);
        }
        
        return $html;
    }
    
    // Generate mobile-optimized CSS
    public static function optimizeCSS($css) {
        $config = self::getConfig();
        
        // Add mobile-specific rules
        $mobileCSS = "
            @media (max-width: 768px) {
                .container { padding: 10px; }
                .grid { grid-template-columns: 1fr !important; }
                .desktop-only { display: none !important; }
                .mobile-only { display: block !important; }
                .btn { min-height: 44px; min-width: 44px; }
                .menu-item { margin-bottom: 15px; }
                .card { margin-bottom: 15px; }
            }
        ";
        
        return $css . $mobileCSS;
    }
    
    // Optimize images for mobile
    public static function optimizeImage($imagePath, $maxSize = 800) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Calculate new dimensions
        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            switch ($imageInfo['mime']) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($imagePath);
                    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagejpeg($newImage, $imagePath, 80);
                    break;
                    
                case 'image/png':
                    $source = imagecreatefrompng($imagePath);
                    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagepng($newImage, $imagePath, 8);
                    break;
                    
                case 'image/gif':
                    $source = imagecreatefromgif($imagePath);
                    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagegif($newImage, $imagePath);
                    break;
            }
            
            imagedestroy($newImage);
            imagedestroy($source);
        }
        
        return true;
    }
    
    // Generate responsive images
    public static function generateResponsiveImages($imagePath) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $pathInfo = pathinfo($imagePath);
        $sizes = [320, 640, 1024];
        $srcset = [];
        
        foreach ($sizes as $size) {
            $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
            
            // Copy and resize image
            if (copy($imagePath, $newPath)) {
                self::optimizeImage($newPath, $size);
                $srcset[] = $newPath . ' ' . $size . 'w';
            }
        }
        
        return implode(', ', $srcset);
    }
    
    // Create service worker for offline support
    public static function createServiceWorker() {
        $serviceWorker = '
            const CACHE_NAME = "qr-menu-v1";
            const urlsToCache = [
                "/",
                "/assets/css/style.css",
                "/assets/js/app.js",
                "/favicon.ico"
            ];
            
            self.addEventListener("install", event => {
                event.waitUntil(
                    caches.open(CACHE_NAME)
                        .then(cache => cache.addAll(urlsToCache))
                );
            });
            
            self.addEventListener("fetch", event => {
                event.respondWith(
                    caches.match(event.request)
                        .then(response => {
                            return response || fetch(event.request);
                        })
                );
            });
        ';
        
        file_put_contents(__DIR__ . '/../sw.js', $serviceWorker);
    }
    
    // Generate manifest.json for PWA
    public static function generateManifest() {
        $manifest = [
            "name" => "QR Menu System",
            "short_name" => "QR Menu",
            "description" => "Digital restaurant menu system",
            "start_url" => "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#667eea",
            "icons" => [
                [
                    "src" => "/assets/icons/icon-192.png",
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => "/assets/icons/icon-512.png",
                    "sizes" => "512x512",
                    "type" => "image/png"
                ]
            ]
        ];
        
        file_put_contents(__DIR__ . '/../manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }
    
    // Optimize database queries for mobile
    public static function optimizeDatabaseQueries() {
        global $db;
        
        // Use WAL mode for better performance
        $db->query('PRAGMA journal_mode = WAL');
        $db->query('PRAGMA synchronous = NORMAL');
        $db->query('PRAGMA cache_size = 10000');
        $db->query('PRAGMA temp_store = MEMORY');
        
        // Optimize for mobile connections
        $db->query('PRAGMA busy_timeout = 5000');
    }
    
    // Reduce data transfer
    public static function compressData($data) {
        if (function_exists('gzencode')) {
            return gzencode(json_encode($data), 6);
        }
        return json_encode($data);
    }
    
    // Get mobile-specific headers
    public static function getHeaders() {
        $headers = [
            'Cache-Control' => 'public, max-age=7200',
            'Content-Type' => 'text/html; charset=utf-8'
        ];
        
        if (self::isMobile()) {
            $headers['Vary'] = 'User-Agent';
            $headers['X-Mobile'] = 'true';
        }
        
        return $headers;
    }
    
    // Check for slow connection
    public static function isSlowConnection() {
        return isset($_SERVER['HTTP_CONNECTION']) && 
               strpos($_SERVER['HTTP_CONNECTION'], 'slow') !== false;
    }
    
    // Get optimized configuration based on connection speed
    public static function getOptimizedConfig() {
        $config = self::getConfig();
        
        if (self::isSlowConnection()) {
            $config['compress_images'] = true;
            $config['lazy_load'] = true;
            $config['reduce_animations'] = true;
            $config['enable_amp'] = true;
        }
        
        return $config;
    }
}

// Initialize mobile optimizations
if (!defined('DISABLE_MOBILE_OPTIMIZATIONS')) {
    // Optimize database for mobile
    MobileOptimizer::optimizeDatabaseQueries();
    
    // Create service worker and manifest if they don't exist
    if (!file_exists(__DIR__ . '/../sw.js')) {
        MobileOptimizer::createServiceWorker();
    }
    
    if (!file_exists(__DIR__ . '/../manifest.json')) {
        MobileOptimizer::generateManifest();
    }
}
?>