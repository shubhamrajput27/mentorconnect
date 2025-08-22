<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Rate limiting for search
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!checkRateLimit($clientIP, 'search', 100, 3600)) { // 100 requests per hour
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}

// Validate request
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Search query required']);
    exit;
}

$query = trim($_GET['q']);
$type = $_GET['type'] ?? 'all'; // all, mentors, students, sessions
$limit = min((int)($_GET['limit'] ?? 20), 50); // Max 50 results
$offset = max((int)($_GET['offset'] ?? 0), 0);

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $results = [];
    
    // Search mentors
    if ($type === 'all' || $type === 'mentors') {
        $mentorSql = "
            SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, 
                   mp.bio, mp.hourly_rate, mp.rating,
                   GROUP_CONCAT(s.name SEPARATOR ', ') as skills
            FROM users u 
            JOIN mentor_profiles mp ON u.id = mp.user_id 
            LEFT JOIN user_skills us ON u.id = us.user_id 
            LEFT JOIN skills s ON us.skill_id = s.id 
            WHERE u.user_type = 'mentor' 
            AND u.status = 'active'
            AND (u.first_name LIKE ? OR u.last_name LIKE ? 
                 OR mp.bio LIKE ? OR s.name LIKE ?)
            GROUP BY u.id
            ORDER BY mp.rating DESC, u.first_name ASC
            LIMIT ? OFFSET ?
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $conn->prepare($mentorSql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
        
        $mentors = $stmt->fetchAll();
        foreach ($mentors as &$mentor) {
            $mentor['type'] = 'mentor';
            $mentor['avatar'] = "/uploads/avatars/" . $mentor['id'] . ".jpg";
        }
        
        $results['mentors'] = $mentors;
    }
    
    // Search students
    if ($type === 'all' || $type === 'students') {
        $studentSql = "
            SELECT u.id, u.first_name, u.last_name, u.email,
                   GROUP_CONCAT(s.name SEPARATOR ', ') as interests
            FROM users u 
            LEFT JOIN user_skills us ON u.id = us.user_id 
            LEFT JOIN skills s ON us.skill_id = s.id 
            WHERE u.user_type = 'student' 
            AND u.status = 'active'
            AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.name LIKE ?)
            GROUP BY u.id
            ORDER BY u.first_name ASC
            LIMIT ? OFFSET ?
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $conn->prepare($studentSql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
        
        $students = $stmt->fetchAll();
        foreach ($students as &$student) {
            $student['type'] = 'student';
            $student['avatar'] = "/uploads/avatars/" . $student['id'] . ".jpg";
        }
        
        $results['students'] = $students;
    }
    
    // Search sessions
    if ($type === 'all' || $type === 'sessions') {
        $sessionSql = "
            SELECT s.id, s.title, s.description, s.scheduled_at, s.duration, s.status,
                   m.first_name as mentor_first_name, m.last_name as mentor_last_name,
                   st.first_name as student_first_name, st.last_name as student_last_name
            FROM sessions s
            JOIN users m ON s.mentor_id = m.id
            JOIN users st ON s.student_id = st.id
            WHERE (s.title LIKE ? OR s.description LIKE ?)
            AND s.status IN ('scheduled', 'completed')
            ORDER BY s.scheduled_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $searchTerm = "%{$query}%";
        $stmt = $conn->prepare($sessionSql);
        $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
        
        $sessions = $stmt->fetchAll();
        foreach ($sessions as &$session) {
            $session['type'] = 'session';
            $session['mentor_name'] = $session['mentor_first_name'] . ' ' . $session['mentor_last_name'];
            $session['student_name'] = $session['student_first_name'] . ' ' . $session['student_last_name'];
        }
        
        $results['sessions'] = $sessions;
    }
    
    // Get total counts for pagination
    $totalCounts = [];
    if ($type === 'all') {
        $totalCounts['mentors'] = count($results['mentors'] ?? []);
        $totalCounts['students'] = count($results['students'] ?? []);
        $totalCounts['sessions'] = count($results['sessions'] ?? []);
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'type' => $type,
        'limit' => $limit,
        'offset' => $offset,
        'total_counts' => $totalCounts
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Search failed. Please try again.'
    ]);
}
?>
