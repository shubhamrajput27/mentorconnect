<?php
// AI-Powered Mentor Matching System
require_once '../config/optimized-config.php';

class MentorMatcher {
    private $db;
    private $weightings;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Matching criteria weightings (total should be 1.0)
        $this->weightings = [
            'skills_match' => 0.35,      // Most important - skill alignment
            'experience_level' => 0.20,   // Experience compatibility
            'availability' => 0.15,       // Schedule compatibility
            'rating' => 0.10,             // Mentor quality
            'learning_style' => 0.10,     // Teaching/learning style match
            'industry' => 0.05,           // Industry preference
            'location' => 0.05            // Time zone/location preference
        ];
    }
    
    /**
     * Find best mentor matches for a student
     */
    public function findMatches($studentId, $limit = 10) {
        // Get student profile and preferences
        $student = $this->getStudentProfile($studentId);
        if (!$student) {
            throw new Exception('Student not found');
        }
        
        // Get all active mentors
        $mentors = $this->getActiveMentors();
        
        // Calculate match scores for each mentor
        $scoredMentors = [];
        foreach ($mentors as $mentor) {
            $score = $this->calculateMatchScore($student, $mentor);
            if ($score > 0.3) { // Minimum threshold
                $mentor['match_score'] = $score;
                $mentor['match_reasons'] = $this->getMatchReasons($student, $mentor);
                $scoredMentors[] = $mentor;
            }
        }
        
        // Sort by match score
        usort($scoredMentors, function($a, $b) {
            return $b['match_score'] <=> $a['match_score'];
        });
        
        return array_slice($scoredMentors, 0, $limit);
    }
    
    /**
     * Calculate overall match score between student and mentor
     */
    private function calculateMatchScore($student, $mentor) {
        $scores = [
            'skills_match' => $this->calculateSkillsMatch($student, $mentor),
            'experience_level' => $this->calculateExperienceMatch($student, $mentor),
            'availability' => $this->calculateAvailabilityMatch($student, $mentor),
            'rating' => $this->calculateRatingScore($mentor),
            'learning_style' => $this->calculateLearningStyleMatch($student, $mentor),
            'industry' => $this->calculateIndustryMatch($student, $mentor),
            'location' => $this->calculateLocationMatch($student, $mentor)
        ];
        
        $totalScore = 0;
        foreach ($scores as $criterion => $score) {
            $totalScore += $score * $this->weightings[$criterion];
        }
        
        return min(1.0, max(0.0, $totalScore));
    }
    
    /**
     * Calculate skills compatibility score
     */
    private function calculateSkillsMatch($student, $mentor) {
        $studentSkills = $this->getUserSkills($student['id'], 'learning');
        $mentorSkills = $this->getUserSkills($mentor['id'], 'teaching');
        
        if (empty($studentSkills) || empty($mentorSkills)) {
            return 0.1; // Low score if no skills defined
        }
        
        $matches = 0;
        $studentSkillIds = array_column($studentSkills, 'skill_id');
        $mentorSkillIds = array_column($mentorSkills, 'skill_id');
        
        foreach ($studentSkillIds as $skillId) {
            if (in_array($skillId, $mentorSkillIds)) {
                // Find proficiency levels
                $studentLevel = $this->getSkillLevel($studentSkills, $skillId);
                $mentorLevel = $this->getSkillLevel($mentorSkills, $skillId);
                
                // Mentor should be at least one level above student
                if ($mentorLevel > $studentLevel) {
                    $matches += 1.0;
                } else if ($mentorLevel == $studentLevel && $mentorLevel >= 3) {
                    $matches += 0.7; // Same level is ok if both are advanced
                } else {
                    $matches += 0.3; // Some credit for having the skill
                }
            }
        }
        
        return min(1.0, $matches / count($studentSkillIds));
    }
    
    /**
     * Calculate experience level compatibility
     */
    private function calculateExperienceMatch($student, $mentor) {
        $studentLevel = $student['experience_level'] ?? 1; // 1=beginner, 5=expert
        $mentorExperience = $mentor['experience_years'] ?? 0;
        
        // Convert mentor years to level
        $mentorLevel = min(5, max(1, floor($mentorExperience / 2) + 1));
        
        // Ideal gap is 1-2 levels
        $gap = $mentorLevel - $studentLevel;
        if ($gap >= 1 && $gap <= 2) {
            return 1.0;
        } else if ($gap == 3) {
            return 0.8;
        } else if ($gap == 0) {
            return 0.6;
        } else {
            return 0.3;
        }
    }
    
    /**
     * Calculate schedule availability compatibility
     */
    private function calculateAvailabilityMatch($student, $mentor) {
        $studentAvailability = $this->getUserAvailability($student['id']);
        $mentorAvailability = $this->getUserAvailability($mentor['id']);
        
        if (empty($studentAvailability) || empty($mentorAvailability)) {
            return 0.5; // Neutral if availability not set
        }
        
        $overlappingSlots = 0;
        $totalStudentSlots = 0;
        
        foreach ($studentAvailability as $studentSlot) {
            $totalStudentSlots++;
            foreach ($mentorAvailability as $mentorSlot) {
                if ($this->slotsOverlap($studentSlot, $mentorSlot)) {
                    $overlappingSlots++;
                    break;
                }
            }
        }
        
        return $totalStudentSlots > 0 ? $overlappingSlots / $totalStudentSlots : 0.5;
    }
    
    /**
     * Calculate rating score (normalized)
     */
    private function calculateRatingScore($mentor) {
        $rating = $mentor['rating'] ?? 0;
        $totalSessions = $mentor['total_sessions'] ?? 0;
        
        if ($totalSessions < 5) {
            return 0.5; // Neutral for new mentors
        }
        
        return min(1.0, $rating / 5.0);
    }
    
    /**
     * Calculate learning/teaching style compatibility
     */
    private function calculateLearningStyleMatch($student, $mentor) {
        $studentStyle = $student['learning_style'] ?? '';
        $mentorStyle = $mentor['teaching_style'] ?? '';
        
        if (empty($studentStyle) || empty($mentorStyle)) {
            return 0.5;
        }
        
        // Style compatibility matrix
        $compatibility = [
            'visual' => ['visual' => 1.0, 'hands-on' => 0.8, 'theoretical' => 0.6, 'collaborative' => 0.7],
            'hands-on' => ['hands-on' => 1.0, 'visual' => 0.8, 'collaborative' => 0.9, 'theoretical' => 0.5],
            'theoretical' => ['theoretical' => 1.0, 'visual' => 0.6, 'hands-on' => 0.5, 'collaborative' => 0.7],
            'collaborative' => ['collaborative' => 1.0, 'hands-on' => 0.9, 'visual' => 0.7, 'theoretical' => 0.7]
        ];
        
        return $compatibility[$studentStyle][$mentorStyle] ?? 0.5;
    }
    
    /**
     * Calculate industry/domain match
     */
    private function calculateIndustryMatch($student, $mentor) {
        $studentIndustry = $student['preferred_industry'] ?? '';
        $mentorIndustry = $mentor['industry'] ?? '';
        
        if (empty($studentIndustry) || empty($mentorIndustry)) {
            return 0.5;
        }
        
        return $studentIndustry === $mentorIndustry ? 1.0 : 0.3;
    }
    
    /**
     * Calculate location/timezone compatibility
     */
    private function calculateLocationMatch($student, $mentor) {
        $studentTz = $student['timezone'] ?? '';
        $mentorTz = $mentor['timezone'] ?? '';
        
        if (empty($studentTz) || empty($mentorTz)) {
            return 0.5;
        }
        
        try {
            $studentTime = new DateTime('now', new DateTimeZone($studentTz));
            $mentorTime = new DateTime('now', new DateTimeZone($mentorTz));
            $hoursDiff = abs($studentTime->getOffset() - $mentorTime->getOffset()) / 3600;
            
            if ($hoursDiff <= 3) return 1.0;
            if ($hoursDiff <= 6) return 0.8;
            if ($hoursDiff <= 9) return 0.6;
            return 0.3;
        } catch (Exception $e) {
            return 0.5;
        }
    }
    
    /**
     * Get detailed reasons for the match
     */
    private function getMatchReasons($student, $mentor) {
        $reasons = [];
        
        // Skills match
        $skillsScore = $this->calculateSkillsMatch($student, $mentor);
        if ($skillsScore > 0.7) {
            $reasons[] = "Excellent skills alignment";
        } else if ($skillsScore > 0.5) {
            $reasons[] = "Good skills match";
        }
        
        // Experience
        $expScore = $this->calculateExperienceMatch($student, $mentor);
        if ($expScore > 0.8) {
            $reasons[] = "Perfect experience level";
        }
        
        // Availability
        $availScore = $this->calculateAvailabilityMatch($student, $mentor);
        if ($availScore > 0.7) {
            $reasons[] = "Great schedule compatibility";
        }
        
        // Rating
        if ($mentor['rating'] >= 4.5) {
            $reasons[] = "Highly rated mentor";
        }
        
        // Learning style
        $styleScore = $this->calculateLearningStyleMatch($student, $mentor);
        if ($styleScore >= 1.0) {
            $reasons[] = "Perfect teaching style match";
        }
        
        return $reasons;
    }
    
    /**
     * Get student profile with preferences
     */
    private function getStudentProfile($studentId) {
        $sql = "SELECT u.*, sp.* 
                FROM users u 
                LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                WHERE u.id = ? AND u.role = 'student'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all active mentors
     */
    private function getActiveMentors() {
        $sql = "SELECT u.*, mp.* 
                FROM users u 
                JOIN mentor_profiles mp ON u.id = mp.user_id 
                WHERE u.role = 'mentor' AND u.status = 'active' AND mp.is_available = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user skills with proficiency levels
     */
    private function getUserSkills($userId, $type = null) {
        $sql = "SELECT us.*, s.name as skill_name 
                FROM user_skills us 
                JOIN skills s ON us.skill_id = s.id 
                WHERE us.user_id = ?";
        
        if ($type) {
            $sql .= " AND us.skill_type = ?";
            $params = [$userId, $type];
        } else {
            $params = [$userId];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get skill proficiency level
     */
    private function getSkillLevel($skills, $skillId) {
        foreach ($skills as $skill) {
            if ($skill['skill_id'] == $skillId) {
                return $skill['proficiency_level'] ?? 1;
            }
        }
        return 1;
    }
    
    /**
     * Get user availability slots
     */
    private function getUserAvailability($userId) {
        $sql = "SELECT * FROM user_availability WHERE user_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if two time slots overlap
     */
    private function slotsOverlap($slot1, $slot2) {
        // Assuming slots have day_of_week, start_time, end_time
        if ($slot1['day_of_week'] !== $slot2['day_of_week']) {
            return false;
        }
        
        $start1 = strtotime($slot1['start_time']);
        $end1 = strtotime($slot1['end_time']);
        $start2 = strtotime($slot2['start_time']);
        $end2 = strtotime($slot2['end_time']);
        
        return ($start1 < $end2) && ($end1 > $start2);
    }
    
    /**
     * Save match results for analytics
     */
    public function saveMatchResults($studentId, $matches) {
        $sql = "INSERT INTO mentor_match_analytics (student_id, mentor_id, match_score, match_reasons, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($matches as $match) {
            $stmt->execute([
                $studentId,
                $match['id'],
                $match['match_score'],
                json_encode($match['match_reasons'])
            ]);
        }
    }
}

// API endpoint for mentor matching
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_id'])) {
    header('Content-Type: application/json');
    
    try {
        $matcher = new MentorMatcher();
        $studentId = (int)$_GET['student_id'];
        $limit = min((int)($_GET['limit'] ?? 10), 20);
        
        $matches = $matcher->findMatches($studentId, $limit);
        $matcher->saveMatchResults($studentId, $matches);
        
        echo json_encode([
            'success' => true,
            'matches' => $matches,
            'total' => count($matches)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
