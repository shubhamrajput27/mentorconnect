<?php
/**
 * MentorConnect SEO Optimization Class
 * Comprehensive SEO improvements and meta optimizations
 */

class SEOOptimizer {
    private $pageData = [];
    private $structuredData = [];
    
    public function __construct() {
        $this->initializeSEO();
    }
    
    private function initializeSEO() {
        // Set default meta data
        $this->pageData = [
            'title' => 'MentorConnect - Connect, Learn, Grow with Expert Mentors',
            'description' => 'Join MentorConnect to connect with expert mentors and accelerate your career growth. Find personalized mentorship, book sessions, and achieve your goals faster.',
            'keywords' => 'mentorship, career coaching, professional development, skill learning, expert mentors, online mentoring',
            'author' => 'MentorConnect',
            'robots' => 'index, follow',
            'canonical' => $this->getCurrentURL(),
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image'
        ];
    }
    
    public function setPageData($data) {
        $this->pageData = array_merge($this->pageData, $data);
        return $this;
    }
    
    public function addStructuredData($type, $data) {
        $this->structuredData[$type] = $data;
        return $this;
    }
    
    public function renderMetaTags() {
        $html = '';
        
        // Basic meta tags
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
        
        // SEO meta tags
        $html .= '<title>' . htmlspecialchars($this->pageData['title']) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($this->pageData['description']) . '">' . "\n";
        $html .= '<meta name="keywords" content="' . htmlspecialchars($this->pageData['keywords']) . '">' . "\n";
        $html .= '<meta name="author" content="' . htmlspecialchars($this->pageData['author']) . '">' . "\n";
        $html .= '<meta name="robots" content="' . htmlspecialchars($this->pageData['robots']) . '">' . "\n";
        
        // Canonical URL
        $html .= '<link rel="canonical" href="' . htmlspecialchars($this->pageData['canonical']) . '">' . "\n";
        
        // Open Graph tags
        $html .= '<meta property="og:title" content="' . htmlspecialchars($this->pageData['title']) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($this->pageData['description']) . '">' . "\n";
        $html .= '<meta property="og:url" content="' . htmlspecialchars($this->pageData['canonical']) . '">' . "\n";
        $html .= '<meta property="og:type" content="' . htmlspecialchars($this->pageData['og_type']) . '">' . "\n";
        $html .= '<meta property="og:site_name" content="MentorConnect">' . "\n";
        
        if (isset($this->pageData['og_image'])) {
            $html .= '<meta property="og:image" content="' . htmlspecialchars($this->pageData['og_image']) . '">' . "\n";
            $html .= '<meta property="og:image:width" content="1200">' . "\n";
            $html .= '<meta property="og:image:height" content="630">' . "\n";
        }
        
        // Twitter Card tags
        $html .= '<meta name="twitter:card" content="' . htmlspecialchars($this->pageData['twitter_card']) . '">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($this->pageData['title']) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($this->pageData['description']) . '">' . "\n";
        
        if (isset($this->pageData['twitter_image'])) {
            $html .= '<meta name="twitter:image" content="' . htmlspecialchars($this->pageData['twitter_image']) . '">' . "\n";
        }
        
        // Performance and optimization meta tags
        $html .= '<meta name="theme-color" content="#6366f1">' . "\n";
        $html .= '<meta name="msapplication-TileColor" content="#6366f1">' . "\n";
        $html .= '<meta name="color-scheme" content="light dark">' . "\n";
        
        // Preconnect to external domains
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        $html .= '<link rel="preconnect" href="https://cdnjs.cloudflare.com">' . "\n";
        
        return $html;
    }
    
    public function renderStructuredData() {
        if (empty($this->structuredData)) {
            return '';
        }
        
        $html = '<script type="application/ld+json">' . "\n";
        $html .= json_encode($this->structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $html .= "\n" . '</script>' . "\n";
        
        return $html;
    }
    
    public function generateSitemap() {
        $urls = [
            ['loc' => BASE_URL . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => BASE_URL . '/auth/login.php', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => BASE_URL . '/auth/signup.php', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => BASE_URL . '/mentors/browse.php', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => BASE_URL . '/about.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => BASE_URL . '/contact.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];
        
        // Add dynamic mentor profiles
        try {
            $mentors = fetchAll("SELECT id, username FROM users WHERE role = 'mentor' AND status = 'active' LIMIT 100");
            foreach ($mentors as $mentor) {
                $urls[] = [
                    'loc' => BASE_URL . '/mentor/' . urlencode($mentor['username']),
                    'priority' => '0.8',
                    'changefreq' => 'weekly'
                ];
            }
        } catch (Exception $e) {
            error_log("Sitemap generation error: " . $e->getMessage());
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    public function generateRobotsTxt() {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /config/\n";
        $content .= "Disallow: /cache/\n";
        $content .= "Disallow: /dev-tools/\n";
        $content .= "Disallow: /*debug*\n";
        $content .= "Disallow: /*test*\n";
        $content .= "\n";
        $content .= "Sitemap: " . BASE_URL . "/sitemap.xml\n";
        
        return $content;
    }
    
    private function getCurrentURL() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        return $protocol . '://' . $host . $uri;
    }
    
    public function optimizePageForSEO($pageType, $data = []) {
        switch ($pageType) {
            case 'homepage':
                $this->optimizeHomepage($data);
                break;
            case 'mentor_profile':
                $this->optimizeMentorProfile($data);
                break;
            case 'dashboard':
                $this->optimizeDashboard($data);
                break;
            case 'auth':
                $this->optimizeAuthPage($data);
                break;
        }
    }
    
    private function optimizeHomepage($data) {
        $this->setPageData([
            'title' => 'MentorConnect - Find Expert Mentors & Accelerate Your Growth',
            'description' => 'Connect with industry-leading mentors on MentorConnect. Get personalized guidance, book 1-on-1 sessions, and achieve your career goals faster with expert mentorship.',
            'keywords' => 'find mentor, career mentorship, professional coaching, skill development, expert guidance, career growth, online mentoring platform',
            'og_type' => 'website'
        ]);
        
        $this->addStructuredData('Organization', [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'MentorConnect',
            'url' => BASE_URL,
            'logo' => BASE_URL . '/assets/images/logo.png',
            'description' => 'Leading online mentorship platform connecting learners with expert mentors',
            'sameAs' => [
                'https://facebook.com/mentorconnect',
                'https://twitter.com/mentorconnect',
                'https://linkedin.com/company/mentorconnect'
            ]
        ]);
    }
    
    private function optimizeMentorProfile($data) {
        $mentor = $data['mentor'] ?? [];
        
        $this->setPageData([
            'title' => ($mentor['first_name'] ?? 'Mentor') . ' ' . ($mentor['last_name'] ?? '') . ' - Expert Mentor on MentorConnect',
            'description' => 'Connect with ' . ($mentor['first_name'] ?? 'this expert mentor') . ' on MentorConnect. ' . ($mentor['bio'] ?? 'Get personalized mentorship and accelerate your growth.'),
            'keywords' => 'mentor profile, ' . ($mentor['first_name'] ?? '') . ' mentor, expert guidance, personalized coaching',
            'og_type' => 'profile'
        ]);
        
        if (!empty($mentor)) {
            $this->addStructuredData('Person', [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => ($mentor['first_name'] ?? '') . ' ' . ($mentor['last_name'] ?? ''),
                'jobTitle' => $mentor['title'] ?? 'Mentor',
                'worksFor' => $mentor['company'] ?? '',
                'description' => $mentor['bio'] ?? '',
                'url' => BASE_URL . '/mentor/' . ($mentor['username'] ?? ''),
                'image' => $mentor['profile_photo'] ? BASE_URL . '/uploads/' . $mentor['profile_photo'] : ''
            ]);
        }
    }
    
    private function optimizeDashboard($data) {
        $this->setPageData([
            'title' => 'Dashboard - MentorConnect',
            'description' => 'Access your MentorConnect dashboard to manage sessions, messages, and track your mentoring progress.',
            'robots' => 'noindex, nofollow' // Private area
        ]);
    }
    
    private function optimizeAuthPage($data) {
        $pageType = $data['type'] ?? 'login';
        
        if ($pageType === 'signup') {
            $this->setPageData([
                'title' => 'Join MentorConnect - Start Your Mentoring Journey',
                'description' => 'Create your MentorConnect account and start connecting with expert mentors or become a mentor yourself. Join thousands of learners and mentors.',
                'keywords' => 'join mentorconnect, signup, create account, become mentor, find mentor'
            ]);
        } else {
            $this->setPageData([
                'title' => 'Sign In to MentorConnect - Access Your Dashboard',
                'description' => 'Sign in to your MentorConnect account to access your dashboard, manage sessions, and continue your mentoring journey.',
                'keywords' => 'login, sign in, mentorconnect access, dashboard'
            ]);
        }
    }
}

// Usage functions
function renderSEOHead($pageType = 'general', $data = []) {
    $seo = new SEOOptimizer();
    $seo->optimizePageForSEO($pageType, $data);
    
    echo $seo->renderMetaTags();
    echo $seo->renderStructuredData();
}

function generateSitemapFile() {
    $seo = new SEOOptimizer();
    $sitemap = $seo->generateSitemap();
    
    file_put_contents(__DIR__ . '/../sitemap.xml', $sitemap);
    return true;
}

function generateRobotsFile() {
    $seo = new SEOOptimizer();
    $robots = $seo->generateRobotsTxt();
    
    file_put_contents(__DIR__ . '/../robots.txt', $robots);
    return true;
}
?>