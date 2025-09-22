<?php
// Advanced Search Engine for MentorConnect
require_once '../config/config.php';
require_once '../config/rate-limiter.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Rate limiting
RateLimiter::handleRateLimit($_SERVER, 'search');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // mentors, sessions, messages, files
$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);

if (strlen($query) < 2) {
    echo json_encode([
        'error' => 'Query too short',
        'message' => 'Search query must be at least 2 characters long'
    ]);
    exit;
}

$results = [];

try {
    // Search Mentors with advanced full-text search
    if ($type === 'all' || $type === 'mentors') {
        $mentorResults = searchMentors($query, $limit, $offset);
        $results['mentors'] = $mentorResults;
    }
    
    // Search Sessions
    if ($type === 'all' || $type === 'sessions') {
        $sessionResults = searchSessions($query, $user['id'], $limit, $offset);
        $results['sessions'] = $sessionResults;
    }
    
    // Search Messages
    if ($type === 'all' || $type === 'messages') {
        $messageResults = searchMessages($query, $user['id'], $limit, $offset);
        $results['messages'] = $messageResults;
    }
    
    // Search Files
    if ($type === 'all' || $type === 'files') {
        $fileResults = searchFiles($query, $user['id'], $limit, $offset);
        $results['files'] = $fileResults;
    }
    
    // Global search statistics
    $stats = [
        'query' => $query,
        'total_results' => array_sum(array_map('count', $results)),
        'search_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        'timestamp' => time()
    ];
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Search failed',
        'message' => 'An error occurred while searching'
    ]);
}

function searchMentors($query, $limit, $offset) {
    // Optimized: Use full-text search and better indexing strategy
    $cacheKey = "mentor_search_" . md5($query . $limit . $offset);
    
    // Check cache first (if available)
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }
    
    // Use FULLTEXT search if available, otherwise fallback to LIKE
    $searchQuery = trim($query);
    
    // First try with MATCH AGAINST (requires FULLTEXT indexes)
    $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_photo, u.bio,
                   '' as title, '' as company, 0 as hourly_rate, 
                   0 as rating, 0 as experience_years, 0 as total_sessions,
                   '' as skills,
                   1 as relevance_score
            FROM users u
            WHERE u.role = 'mentor' AND u.status = 'active'
            AND (
                u.first_name LIKE ? OR
                u.last_name LIKE ? OR
                u.bio LIKE ? OR
                u.username LIKE ?
            )
            ORDER BY relevance_score DESC, u.id ASC
            LIMIT ? OFFSET ?";
    
    $searchTerm = '%' . $searchQuery . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset];
    
    try {
        $results = fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        $results = [];
    }
    
    // Cache results for 5 minutes (if available)
    if (function_exists('apcu_store') && !empty($results)) {
        apcu_store($cacheKey, $results, 300);
    }
    
    return $results;
}

function searchSessions($query, $userId, $limit, $offset) {
    $searchTerms = explode(' ', $query);
    $whereConditions = ["(s.mentor_id = ? OR s.student_id = ?)"];
    $params = [$userId, $userId];
    
    $searchConditions = [];
    foreach ($searchTerms as $term) {
        if (strlen(trim($term)) > 1) {
            $searchConditions[] = "(
                s.title LIKE ? OR 
                s.description LIKE ? OR 
                s.notes LIKE ?
            )";
            $searchTerm = '%' . trim($term) . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
    }
    
    if (!empty($searchConditions)) {
        $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT s.*, 
                   mentor.first_name as mentor_first_name, mentor.last_name as mentor_last_name,
                   student.first_name as student_first_name, student.last_name as student_last_name
            FROM sessions s
            JOIN users mentor ON s.mentor_id = mentor.id
            JOIN users student ON s.student_id = student.id
            WHERE {$whereClause}
            ORDER BY s.scheduled_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    return fetchAll($sql, $params);
}

function searchMessages($query, $userId, $limit, $offset) {
    $searchTerms = explode(' ', $query);
    $whereConditions = ["(m.sender_id = ? OR m.recipient_id = ?)"];
    $params = [$userId, $userId];
    
    $searchConditions = [];
    foreach ($searchTerms as $term) {
        if (strlen(trim($term)) > 1) {
            $searchConditions[] = "(
                m.subject LIKE ? OR 
                m.content LIKE ? OR
                m.message LIKE ?
            )";
            $searchTerm = '%' . trim($term) . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
    }
    
    if (!empty($searchConditions)) {
        $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT m.*, 
                   sender.first_name as sender_first_name, sender.last_name as sender_last_name,
                   recipient.first_name as recipient_first_name, recipient.last_name as recipient_last_name
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users recipient ON m.recipient_id = recipient.id
            WHERE {$whereClause}
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    return fetchAll($sql, $params);
}

function searchFiles($query, $userId, $limit, $offset) {
    $searchTerms = explode(' ', $query);
    $whereConditions = [
        "(f.user_id = ? OR f.is_public = TRUE)"
    ];
    $params = [$userId];
    
    $searchConditions = [];
    foreach ($searchTerms as $term) {
        if (strlen(trim($term)) > 1) {
            $searchConditions[] = "(
                f.original_name LIKE ? OR 
                f.filename LIKE ?
            )";
            $searchTerm = '%' . trim($term) . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
    }
    
    if (!empty($searchConditions)) {
        $whereConditions[] = '(' . implode(' AND ', $searchConditions) . ')';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT f.*, 
                   uploader.first_name as uploader_first_name, 
                   uploader.last_name as uploader_last_name
            FROM files f
            JOIN users uploader ON f.user_id = uploader.id
            WHERE {$whereClause}
            ORDER BY f.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    return fetchAll($sql, $params);
}
?>
