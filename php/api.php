<?php
require_once 'config.php';

// Main API handler
class VideoAPI {
    private $videos = [];
    
    public function __construct() {
        $this->loadVideos();
    }
    
    private function loadVideos() {
        $videoPath = VIDEO_PATH;
        $videos = [];
        
        // Predefined video data
        $videoData = [
            'archita-phukan-viral-video.mp4' => [
                'id' => 1,
                'title' => 'Archita Phukan Viral Video',
                'description' => 'An amazing viral video that has captured everyone\'s attention. Watch this incredible content that showcases talent and creativity.',
                'views' => 1200000,
                'likes' => 45000,
                'dislikes' => 1200,
                'upload_date' => '2025-01-07',
                'channel' => 'VideoTube Channel',
                'category' => 'Entertainment',
                'tags' => ['viral', 'trending', 'entertainment']
            ],
            'VID_20250709154938.mp4' => [
                'id' => 2,
                'title' => 'Latest Video Content',
                'description' => 'Fresh new content that brings entertainment and joy. Don\'t miss this exciting video experience.',
                'views' => 856000,
                'likes' => 32000,
                'dislikes' => 800,
                'upload_date' => '2025-01-08',
                'channel' => 'VideoTube Channel',
                'category' => 'Entertainment',
                'tags' => ['new', 'content', 'entertainment']
            ]
        ];
        
        // Scan video directory
        if (is_dir($videoPath)) {
            $files = scandir($videoPath);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && validateVideoFile($file)) {
                    $filepath = $videoPath . $file;
                    $metadata = getVideoMetadata($filepath);
                    
                    if ($metadata) {
                        $video = [
                            'id' => isset($videoData[$file]) ? $videoData[$file]['id'] : crc32($file),
                            'filename' => $file,
                            'title' => isset($videoData[$file]) ? $videoData[$file]['title'] : pathinfo($file, PATHINFO_FILENAME),
                            'description' => isset($videoData[$file]) ? $videoData[$file]['description'] : 'No description available',
                            'url' => 'videos/' . $file,
                            'thumbnail' => $this->getThumbnailUrl($file),
                            'duration' => isset($metadata['duration']) ? $metadata['duration'] : 0,
                            'duration_formatted' => isset($metadata['duration']) ? formatDuration($metadata['duration']) : '0:00',
                            'size' => $metadata['size'],
                            'size_formatted' => formatFileSize($metadata['size']),
                            'width' => isset($metadata['width']) ? $metadata['width'] : 0,
                            'height' => isset($metadata['height']) ? $metadata['height'] : 0,
                            'type' => $metadata['type'],
                            'views' => isset($videoData[$file]) ? $videoData[$file]['views'] : rand(1000, 100000),
                            'likes' => isset($videoData[$file]) ? $videoData[$file]['likes'] : rand(100, 10000),
                            'dislikes' => isset($videoData[$file]) ? $videoData[$file]['dislikes'] : rand(10, 1000),
                            'upload_date' => isset($videoData[$file]) ? $videoData[$file]['upload_date'] : date('Y-m-d', $metadata['modified']),
                            'upload_date_formatted' => $this->formatUploadDate(isset($videoData[$file]) ? $videoData[$file]['upload_date'] : date('Y-m-d', $metadata['modified'])),
                            'channel' => isset($videoData[$file]) ? $videoData[$file]['channel'] : 'VideoTube Channel',
                            'category' => isset($videoData[$file]) ? $videoData[$file]['category'] : 'General',
                            'tags' => isset($videoData[$file]) ? $videoData[$file]['tags'] : ['video'],
                            'created_at' => date('c', $metadata['modified']),
                            'updated_at' => date('c')
                        ];
                        
                        $videos[] = $video;
                    }
                }
            }
        }
        
        $this->videos = $videos;
    }
    
    private function getThumbnailUrl($filename) {
        $thumbnailName = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $thumbnailPath = THUMBNAIL_PATH . $thumbnailName;
        
        if (file_exists($thumbnailPath)) {
            return 'thumbnails/' . $thumbnailName;
        }
        
        return null;
    }
    
    private function formatUploadDate($date) {
        $uploadDate = new DateTime($date);
        $now = new DateTime();
        $diff = $now->diff($uploadDate);
        
        if ($diff->days == 0) {
            return 'Today';
        } elseif ($diff->days == 1) {
            return '1 day ago';
        } elseif ($diff->days < 7) {
            return $diff->days . ' days ago';
        } elseif ($diff->days < 30) {
            $weeks = floor($diff->days / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff->days < 365) {
            $months = floor($diff->days / 30);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff->days / 365);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Remove 'php' and 'api.php' from path parts
        $pathParts = array_filter($pathParts, function($part) {
            return $part !== 'php' && $part !== 'api.php';
        });
        $pathParts = array_values($pathParts);
        
        $endpoint = isset($pathParts[0]) ? $pathParts[0] : '';
        $id = isset($pathParts[1]) ? $pathParts[1] : null;
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($endpoint, $id);
                    break;
                case 'POST':
                    $this->handlePost($endpoint);
                    break;
                default:
                    errorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            logMessage('API Error: ' . $e->getMessage(), 'ERROR');
            errorResponse('Internal server error', 500);
        }
    }
    
    private function handleGet($endpoint, $id) {
        switch ($endpoint) {
            case 'videos':
                if ($id) {
                    $this->getVideo($id);
                } else {
                    $this->getVideos();
                }
                break;
            case 'search':
                $this->searchVideos();
                break;
            case 'stats':
                $this->getStats();
                break;
            case 'health':
                $this->healthCheck();
                break;
            default:
                $this->getVideos();
        }
    }
    
    private function handlePost($endpoint) {
        switch ($endpoint) {
            case 'view':
                $this->recordView();
                break;
            case 'like':
                $this->recordLike();
                break;
            case 'dislike':
                $this->recordDislike();
                break;
            default:
                errorResponse('Endpoint not found', 404);
        }
    }
    
    private function getVideos() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'upload_date';
        $order = isset($_GET['order']) ? $_GET['order'] : 'desc';
        
        $videos = $this->videos;
        
        // Filter by category
        if ($category) {
            $videos = array_filter($videos, function($video) use ($category) {
                return strtolower($video['category']) === strtolower($category);
            });
        }
        
        // Sort videos
        usort($videos, function($a, $b) use ($sort, $order) {
            $aVal = isset($a[$sort]) ? $a[$sort] : 0;
            $bVal = isset($b[$sort]) ? $b[$sort] : 0;
            
            if ($order === 'desc') {
                return $bVal <=> $aVal;
            } else {
                return $aVal <=> $bVal;
            }
        });
        
        // Pagination
        $total = count($videos);
        $offset = ($page - 1) * $limit;
        $videos = array_slice($videos, $offset, $limit);
        
        successResponse([
            'videos' => array_values($videos),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getVideo($id) {
        $video = null;
        foreach ($this->videos as $v) {
            if ($v['id'] == $id || $v['filename'] === $id) {
                $video = $v;
                break;
            }
        }
        
        if (!$video) {
            errorResponse('Video not found', 404);
        }
        
        successResponse($video);
    }
    
    private function searchVideos() {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (empty($query)) {
            errorResponse('Search query is required', 400);
        }
        
        $results = [];
        $queryLower = strtolower($query);
        
        foreach ($this->videos as $video) {
            $score = 0;
            
            // Search in title
            if (stripos($video['title'], $query) !== false) {
                $score += 10;
            }
            
            // Search in description
            if (stripos($video['description'], $query) !== false) {
                $score += 5;
            }
            
            // Search in tags
            foreach ($video['tags'] as $tag) {
                if (stripos($tag, $query) !== false) {
                    $score += 3;
                }
            }
            
            // Search in category
            if (stripos($video['category'], $query) !== false) {
                $score += 2;
            }
            
            if ($score > 0) {
                $video['relevance_score'] = $score;
                $results[] = $video;
            }
        }
        
        // Sort by relevance
        usort($results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        successResponse([
            'query' => $query,
            'results' => $results,
            'count' => count($results)
        ]);
    }
    
    private function getStats() {
        $totalVideos = count($this->videos);
        $totalViews = array_sum(array_column($this->videos, 'views'));
        $totalLikes = array_sum(array_column($this->videos, 'likes'));
        $totalSize = array_sum(array_column($this->videos, 'size'));
        
        $categories = [];
        foreach ($this->videos as $video) {
            $category = $video['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }
        
        successResponse([
            'total_videos' => $totalVideos,
            'total_views' => $totalViews,
            'total_likes' => $totalLikes,
            'total_size' => $totalSize,
            'total_size_formatted' => formatFileSize($totalSize),
            'categories' => $categories,
            'server_time' => date('c')
        ]);
    }
    
    private function healthCheck() {
        $status = [
            'status' => 'healthy',
            'version' => APP_VERSION,
            'timestamp' => date('c'),
            'videos_available' => count($this->videos),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_usage_formatted' => formatFileSize(memory_get_usage(true))
        ];
        
        successResponse($status);
    }
    
    private function recordView() {
        $input = json_decode(file_get_contents('php://input'), true);
        $videoId = isset($input['video_id']) ? $input['video_id'] : null;
        
        if (!$videoId) {
            errorResponse('Video ID is required', 400);
        }
        
        // In a real application, this would update the database
        logMessage("View recorded for video ID: $videoId", 'INFO');
        
        successResponse(['message' => 'View recorded']);
    }
    
    private function recordLike() {
        $input = json_decode(file_get_contents('php://input'), true);
        $videoId = isset($input['video_id']) ? $input['video_id'] : null;
        $action = isset($input['action']) ? $input['action'] : 'like'; // 'like' or 'unlike'
        
        if (!$videoId) {
            errorResponse('Video ID is required', 400);
        }
        
        // In a real application, this would update the database
        logMessage("Like action '$action' recorded for video ID: $videoId", 'INFO');
        
        successResponse(['message' => 'Like recorded', 'action' => $action]);
    }
    
    private function recordDislike() {
        $input = json_decode(file_get_contents('php://input'), true);
        $videoId = isset($input['video_id']) ? $input['video_id'] : null;
        $action = isset($input['action']) ? $input['action'] : 'dislike'; // 'dislike' or 'undislike'
        
        if (!$videoId) {
            errorResponse('Video ID is required', 400);
        }
        
        // In a real application, this would update the database
        logMessage("Dislike action '$action' recorded for video ID: $videoId", 'INFO');
        
        successResponse(['message' => 'Dislike recorded', 'action' => $action]);
    }
}

// Initialize and handle the API request
$api = new VideoAPI();
$api->handleRequest();
?>

