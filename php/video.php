<?php
require_once 'config.php';

class VideoStreamer {
    private $videoPath;
    private $filename;
    private $filepath;
    
    public function __construct() {
        $this->videoPath = VIDEO_PATH;
        $this->filename = isset($_GET['file']) ? basename($_GET['file']) : null;
        
        if (!$this->filename) {
            $this->sendError('Video file not specified', 400);
        }
        
        $this->filepath = $this->videoPath . $this->filename;
        
        if (!file_exists($this->filepath)) {
            $this->sendError('Video file not found', 404);
        }
        
        if (!validateVideoFile($this->filename)) {
            $this->sendError('Invalid video file type', 400);
        }
    }
    
    public function stream() {
        $filesize = filesize($this->filepath);
        $mimeType = $this->getMimeType();
        
        // Set headers for video streaming
        header('Content-Type: ' . $mimeType);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $filesize);
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        
        // Handle range requests for video seeking
        if (isset($_SERVER['HTTP_RANGE'])) {
            $this->streamRange($filesize);
        } else {
            $this->streamFull();
        }
    }
    
    private function streamRange($filesize) {
        $range = $_SERVER['HTTP_RANGE'];
        
        // Parse range header
        if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            $start = intval($matches[1]);
            $end = !empty($matches[2]) ? intval($matches[2]) : $filesize - 1;
            
            // Validate range
            if ($start > $end || $start >= $filesize || $end >= $filesize) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header('Content-Range: bytes */' . $filesize);
                exit;
            }
            
            $length = $end - $start + 1;
            
            // Set partial content headers
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $filesize);
            header('Content-Length: ' . $length);
            
            // Stream the requested range
            $this->outputRange($start, $length);
        } else {
            // Invalid range header
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
    }
    
    private function streamFull() {
        header('HTTP/1.1 200 OK');
        
        // Stream the entire file
        $handle = fopen($this->filepath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }
    }
    
    private function outputRange($start, $length) {
        $handle = fopen($this->filepath, 'rb');
        if ($handle) {
            fseek($handle, $start);
            $remaining = $length;
            
            while ($remaining > 0 && !feof($handle)) {
                $chunkSize = min(8192, $remaining);
                echo fread($handle, $chunkSize);
                $remaining -= $chunkSize;
                flush();
            }
            
            fclose($handle);
        }
    }
    
    private function getMimeType() {
        $extension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'mkv' => 'video/x-matroska'
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
    
    private function sendError($message, $code) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ]);
        exit;
    }
}

// Log the video request
if (isset($_GET['file'])) {
    logMessage('Video requested: ' . $_GET['file'], 'INFO');
}

// Initialize and stream the video
try {
    $streamer = new VideoStreamer();
    $streamer->stream();
} catch (Exception $e) {
    logMessage('Video streaming error: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>

