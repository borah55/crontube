<?php
// Configuration file for VideoTube application

// Database configuration (if needed for future expansion)
define('DB_HOST', 'localhost');
define('DB_NAME', 'videotube');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'VideoTube');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Video settings
define('VIDEO_PATH', '../videos/');
define('THUMBNAIL_PATH', '../thumbnails/');
define('MAX_VIDEO_SIZE', 100 * 1024 * 1024); // 100MB
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'ogg']);

// API settings
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per minute

// Security settings
define('ENABLE_CORS', true);
define('ALLOWED_ORIGINS', ['*']);
define('API_KEY_REQUIRED', false);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// CORS headers function
function setCorsHeaders() {
    if (ENABLE_CORS) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// JSON response function
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

// Error response function
function errorResponse($message, $status = 400) {
    jsonResponse([
        'error' => true,
        'message' => $message,
        'timestamp' => date('c')
    ], $status);
}

// Success response function
function successResponse($data, $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
}

// Validate video file function
function validateVideoFile($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_VIDEO_TYPES);
}

// Get video metadata function
function getVideoMetadata($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $metadata = [
        'filename' => basename($filepath),
        'size' => filesize($filepath),
        'modified' => filemtime($filepath),
        'type' => mime_content_type($filepath)
    ];
    
    // Try to get video duration using ffprobe if available
    $ffprobe = shell_exec("which ffprobe");
    if ($ffprobe) {
        $command = "ffprobe -v quiet -print_format json -show_format -show_streams " . escapeshellarg($filepath);
        $output = shell_exec($command);
        if ($output) {
            $videoInfo = json_decode($output, true);
            if (isset($videoInfo['format']['duration'])) {
                $metadata['duration'] = floatval($videoInfo['format']['duration']);
            }
            if (isset($videoInfo['streams'][0]['width'])) {
                $metadata['width'] = intval($videoInfo['streams'][0]['width']);
                $metadata['height'] = intval($videoInfo['streams'][0]['height']);
            }
        }
    }
    
    return $metadata;
}

// Format file size function
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Format duration function
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}

// Log function
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents('../logs/app.log', $logEntry, FILE_APPEND | LOCK_EX);
}

// Create logs directory if it doesn't exist
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

// Initialize CORS headers
setCorsHeaders();
?>

