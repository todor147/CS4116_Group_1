<?php
// Helper functions for debug information
function getCategoryName($pdo, $category_id) {
    $stmt = $pdo->prepare("SELECT category_name FROM Expertise_Categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_COLUMN);
    return $result ?: "Unknown Category";
}

function getSkillName($pdo, $skill_id) {
    $stmt = $pdo->prepare("SELECT skill_name FROM Skills WHERE skill_id = ?");
    $stmt->execute([$skill_id]);
    $result = $stmt->fetch(PDO::FETCH_COLUMN);
    return $result ?: "Unknown Skill";
}

session_start();
require_once '../includes/db_connection.php';

// No login check - allow everyone to search

// Function to calculate relevance score
function calculateRelevanceScore($coach, $search_query) {
    $score = 0;
    $search_terms = explode(' ', strtolower($search_query));
    
    foreach ($search_terms as $term) {
        if (strlen($term) < 2) continue;
        
        // Exact username match gets highest score
        if (stripos($coach['username'], $term) !== false) {
            $score += 10;
        }
        
        // Headline match
        if (stripos($coach['headline'], $term) !== false) {
            $score += 5;
        }
        
        // Bio match
        if (stripos($coach['bio'], $term) !== false) {
            $score += 3;
        }
        
        // About me match
        if (stripos($coach['about_me'], $term) !== false) {
            $score += 3;
        }
        
        // Skills match
        if (isset($coach['top_skills'])) {
            foreach ($coach['top_skills'] as $skill) {
                if (stripos($skill['skill_name'], $term) !== false) {
                    $score += 8; // Higher score for matching skills
                }
            }
        }
    }
    
    return $score;
}

// Check for diagnostic mode
$diagnostic_mode = isset($_GET['diagnostic']);
if ($diagnostic_mode) {
    echo '<div class="container mt-3 p-3 bg-light">';
    echo '<h4>Diagnostic Information</h4>';
    
    // Test database connection
    echo '<p>Database connection: ';
    try {
        $pdo->query('SELECT 1');
        echo '<span class="text-success">Connected</span>';
    } catch (Exception $e) {
        echo '<span class="text-danger">Failed - ' . htmlspecialchars($e->getMessage()) . '</span>';
    }
    echo '</p>';
    
    // Count coaches
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM Coaches');
        $coach_count = $stmt->fetchColumn();
        echo '<p>Total coaches in database: ' . $coach_count . '</p>';
        
        if ($coach_count > 0) {
            // Show sample coach
            $stmt = $pdo->query('SELECT c.*, u.username FROM Coaches c JOIN Users u ON c.user_id = u.user_id LIMIT 1');
            $sample_coach = $stmt->fetch(PDO::FETCH_ASSOC);
            echo '<p>Sample coach: ' . htmlspecialchars($sample_coach['username']) . 
                 ' (ID: ' . $sample_coach['coach_id'] . 
                 ', Rating: ' . $sample_coach['rating'] . 
                 ', Hourly Rate: €' . $sample_coach['hourly_rate'] . ')</p>';
            
            // Check for skills
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM Coach_Skills');
            $stmt->execute();
            $skills_count = $stmt->fetchColumn();
            echo '<p>Total coach skills mappings: ' . $skills_count . '</p>';
            
            if ($skills_count > 0) {
                // Show sample skills
                $stmt = $pdo->query('SELECT cs.*, s.skill_name FROM Coach_Skills cs JOIN Skills s ON cs.skill_id = s.skill_id LIMIT 5');
                $sample_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<p>Sample skills:</p><ul>';
                foreach ($sample_skills as $skill) {
                    echo '<li>Coach ID ' . $skill['coach_id'] . ' has skill: ' . htmlspecialchars($skill['skill_name']) . ' (ID: ' . $skill['skill_id'] . ')</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="text-warning">No skills found in Coach_Skills table!</p>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="text-danger">Error counting coaches: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '<hr>';
    echo '</div>';
}

// Function to highlight matching terms
function highlightMatchingTerms($text, $query) {
    if (empty($query) || empty($text)) {
        return htmlspecialchars($text);
    }
    
    $search_terms = preg_split('/\s+/', trim($query));
    $text_html = htmlspecialchars($text);
    
    foreach ($search_terms as $term) {
        if (strlen($term) < 2) continue;
        $pattern = '/(' . preg_quote($term, '/') . ')/i';
        $text_html = preg_replace($pattern, '<mark class="bg-warning text-dark">$1</mark>', $text_html);
    }
    
    return $text_html;
}

// Initialize variables
$coaches = [];
$categories = [];
$skills = [];
$selected_category = $_GET['category'] ?? '';
$selected_skills = isset($_GET['skills']) ? (is_array($_GET['skills']) ? $_GET['skills'] : [$_GET['skills']]) : [];
$min_rating = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;
// Set max_price to a very high number by default to effectively mean "Any"
$max_price = isset($_GET['max_price']) && !empty($_GET['max_price']) ? floatval($_GET['max_price']) : 9999999;
$min_price = isset($_GET['min_price']) && !empty($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$search_query = $_GET['query'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'relevance';

// Smart filtering - Auto-detect categories and skills in search query
$auto_detected_categories = [];
$auto_detected_skills = [];

// Get all categories and skills for filter options and auto-detection
try {
    $stmt = $pdo->query("SELECT * FROM Expertise_Categories ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT s.*, ec.category_name FROM Skills s JOIN Expertise_Categories ec ON s.category_id = ec.category_id ORDER BY ec.category_name, s.skill_name");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Auto-detect categories and skills in search query if not empty
    if (!empty($search_query)) {
        $search_terms = preg_split('/\s+/', strtolower(trim($search_query)));
        
        // Check if search query exactly matches a category name
        foreach ($categories as $category) {
            $category_name = strtolower($category['category_name']);
            // Check for exact match or if search query contains the category name
            if ($category_name === strtolower($search_query) || 
                in_array($category_name, $search_terms)) {
                $auto_detected_categories[] = $category['category_id'];
                // If exact match and no category selected yet, auto-select it
                if (empty($selected_category) && $category_name === strtolower($search_query)) {
                    $selected_category = $category['category_id'];
                }
            }
        }
        
        // Check if search query matches a skill name
        foreach ($skills as $skill) {
            $skill_name = strtolower($skill['skill_name']);
            // Check for exact match or if search query contains the skill name
            if ($skill_name === strtolower($search_query) || 
                in_array($skill_name, $search_terms)) {
                $auto_detected_skills[] = $skill['skill_id'];
                // Auto-select the skill if it's not already selected
                if (!in_array($skill['skill_id'], $selected_skills)) {
                    $selected_skills[] = $skill['skill_id'];
                }
            }
        }
    }
    
    // Get common search terms for suggestions
    $popular_searches = [];
    
    // Add all skills as possible search suggestions
    foreach ($skills as $skill) {
        $popular_searches[] = $skill['skill_name'];
    }
    
    // Add all categories as possible search suggestions
    foreach ($categories as $category) {
        $popular_searches[] = $category['category_name'];
    }
    
    // Add some common expertise areas and coach types
    $common_terms = [
        'Math', 'Science', 'Language', 'Programming', 'Music', 'Art', 
        'Tutor', 'Mentor', 'Professional', 'Expert', 'Certified',
        'Beginner friendly', 'Advanced', 'Affordable', 'Top rated'
    ];
    
    $popular_searches = array_merge($popular_searches, $common_terms);
    $popular_searches = array_unique($popular_searches);
    sort($popular_searches);
} catch (PDOException $e) {
    $error_message = "Error fetching filter options: " . $e->getMessage();
}

// Build the search query
try {
    $params = [];
    $where_clauses = []; // Initialize the array ONCE here
    $sql = "
        SELECT DISTINCT c.*, u.username, u.profile_image, u.bio
        FROM Coaches c
        JOIN Users u ON c.user_id = u.user_id
    ";
    
    // Add Skills and Categories joins for search - make these permanent joins
    // This ensures we search skills and categories for all queries
    $sql .= " LEFT JOIN Coach_Skills cs_search ON c.coach_id = cs_search.coach_id
              LEFT JOIN Skills s_search ON cs_search.skill_id = s_search.skill_id
              LEFT JOIN Expertise_Categories ec_search ON s_search.category_id = ec_search.category_id";
    
    // Skills selection requires a separate join but make it a LEFT JOIN to ensure we don't lose coaches
    if (!empty($selected_skills)) {
        // Use COUNT to ensure we match ALL selected skills, not just ANY
        $placeholders = implode(',', array_fill(0, count($selected_skills), '?'));
        $skill_count_sql = "(SELECT COUNT(DISTINCT skill_id) FROM Coach_Skills WHERE coach_id = c.coach_id AND skill_id IN ($placeholders)) = ?";
        $where_clauses[] = $skill_count_sql;
        $params = array_merge($params, $selected_skills, [count($selected_skills)]);
    }
    
    // Category selection requires its own join
    if (!empty($selected_category)) {
        $sql .= " LEFT JOIN Coach_Skills cs2 ON c.coach_id = cs2.coach_id
                  LEFT JOIN Skills s ON cs2.skill_id = s.skill_id
                  LEFT JOIN Expertise_Categories ec ON s.category_id = ec.category_id";
        
        $where_clauses[] = "ec.category_id = ?";
        $params[] = $selected_category;
    }
    
    // Filter by search query - changed to use OR logic between text fields and skills
    if (!empty($search_query)) {
        $search_terms = preg_split('/\s+/', trim($search_query));
        $search_conditions = [];
        
        foreach ($search_terms as $term) {
            if (strlen($term) < 2) continue; // Skip very short terms
            
            $search_param = "%" . $term . "%";
            $term_condition = [];
            
            // Check in text fields
            $term_condition[] = "u.username LIKE ?";
            $params[] = $search_param;
            
            $term_condition[] = "c.headline LIKE ?";
            $params[] = $search_param;
            
            $term_condition[] = "c.about_me LIKE ?";
            $params[] = $search_param;
            
            $term_condition[] = "u.bio LIKE ?";
            $params[] = $search_param;
            
            // Check in skills and categories
            $term_condition[] = "s_search.skill_name LIKE ?";
            $params[] = $search_param;
            
            $term_condition[] = "ec_search.category_name LIKE ?";
            $params[] = $search_param;
            
            // Add custom category search
            $term_condition[] = "c.custom_category LIKE ?";
            $params[] = $search_param;
            
            // Add this term's conditions
            $search_conditions[] = "(" . implode(" OR ", $term_condition) . ")";
        }
        
        if (!empty($search_conditions)) {
            // Join different terms with AND (must match at least one field for each term)
            $where_clauses[] = "(" . implode(" AND ", $search_conditions) . ")";
        }
    }
    
    // Category selection requires its own join - this is now done above
    if (!empty($selected_category)) {
        $where_clauses[] = "ec.category_id = ?";
        $params[] = $selected_category;
    }
    
    // Skills selection - already handled above in a way that ensures matching ALL skills
    
    // Filter by minimum rating
    if ($min_rating > 0) {
        $where_clauses[] = "c.rating >= ?";
        $params[] = $min_rating;
    }
    
    // Filter by price range
    if ($min_price > 0) {
        $where_clauses[] = "c.hourly_rate >= ?";
        $params[] = $min_price;
    }
    
    if ($max_price < 9999999) {
        $where_clauses[] = "c.hourly_rate <= ?";
        $params[] = $max_price;
    }
    
    // Only show coaches with active status
    $where_clauses[] = "u.is_banned = 0";
    
    // Add WHERE clause if we have conditions
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Add GROUP BY to prevent duplicates
    $sql .= " GROUP BY c.coach_id";
    
    // Add ordering
    if ($sort_by === 'price_asc') {
        $sql .= " ORDER BY c.hourly_rate ASC";
    } elseif ($sort_by === 'price_desc') {
        $sql .= " ORDER BY c.hourly_rate DESC";
    } elseif ($sort_by === 'rating_desc') {
        $sql .= " ORDER BY c.rating DESC";
    } else {
        // Default to relevance, which we'll calculate in PHP
        $sql .= " ORDER BY c.rating DESC"; // Fallback server-side sort
    }
    
    // Add debug output
    if (isset($_GET['debug'])) {
        echo "<pre>SQL Query: " . htmlspecialchars($sql) . "</pre>";
        echo "<pre>Params: " . htmlspecialchars(print_r($params, true)) . "</pre>";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get skills for each coach
    foreach ($coaches as &$coach) {
        $stmt = $pdo->prepare("
            SELECT s.skill_name, cs.proficiency_level
            FROM Coach_Skills cs
            JOIN Skills s ON cs.skill_id = s.skill_id
            WHERE cs.coach_id = ?
            ORDER BY cs.proficiency_level DESC
            LIMIT 5
        ");
        $stmt->execute([$coach['coach_id']]);
        $coach['top_skills'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we're sorting by relevance and have a search query, calculate a more precise relevance score
        if ($sort_by == 'relevance' && !empty($search_query)) {
            $coach['relevance_score'] = calculateRelevanceScore($coach, $search_query);
        }
    }
    
    // Ensure no duplicate coaches by using coach_id as key
    $unique_coaches = [];
    foreach ($coaches as $coach) {
        $unique_coaches[$coach['coach_id']] = $coach;
    }
    $coaches = array_values($unique_coaches);
    
    // If we calculated relevance scores, sort by them (gives more precise ranking than SQL)
    if ($sort_by == 'relevance' && !empty($search_query)) {
        usort($coaches, function($a, $b) {
            // First compare by relevance score
            if ($a['relevance_score'] != $b['relevance_score']) {
                return $b['relevance_score'] - $a['relevance_score']; // Descending
            }
            // If tied, compare by rating
            return $b['rating'] <=> $a['rating']; // Descending
        });
    }
    
} catch (PDOException $e) {
    $error_message = "Error searching coaches: " . $e->getMessage();
}

// Include header
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Search Filters</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="">
                        <!-- Search Query -->
                        <div class="mb-3">
                            <label for="query" class="form-label">Search</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="query" name="query" 
                                       value="<?= htmlspecialchars($search_query) ?>" 
                                       placeholder="Search by name, keyword, skill..."
                                       autocomplete="off">
                                <div id="search-suggestions" class="position-absolute w-100 bg-white border rounded shadow-sm" style="display: none; z-index: 100; max-height: 200px; overflow-y: auto;">
                                </div>
                            </div>
                            <?php if (!empty($search_query)): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Showing results for: <strong><?= htmlspecialchars($search_query) ?></strong></small>
                                    <?php if (!empty($auto_detected_categories) || !empty($auto_detected_skills)): ?>
                                        <div class="mt-1">
                                            <small class="text-success">
                                                <i class="bi bi-magic"></i> Automatically applied:
                                                <?php 
                                                $applied_filters = [];
                                                
                                                // Show auto-detected categories
                                                foreach ($auto_detected_categories as $cat_id) {
                                                    foreach ($categories as $category) {
                                                        if ($category['category_id'] == $cat_id) {
                                                            $applied_filters[] = 'Category: ' . htmlspecialchars($category['category_name']);
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                // Show auto-detected skills
                                                foreach ($auto_detected_skills as $skill_id) {
                                                    foreach ($skills as $skill) {
                                                        if ($skill['skill_id'] == $skill_id) {
                                                            $applied_filters[] = 'Skill: ' . htmlspecialchars($skill['skill_name']);
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                echo implode(', ', $applied_filters);
                                                ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    <a href="coach-search.php" class="ms-2 small">Clear search</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>" 
                                            <?= $selected_category == $category['category_id'] ? 'selected' : '' ?>
                                            <?= in_array($category['category_id'], $auto_detected_categories) ? 'class="text-success fw-bold"' : '' ?>>
                                        <?= htmlspecialchars($category['category_name']) ?>
                                        <?= in_array($category['category_id'], $auto_detected_categories) ? ' ✓' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Skills Filter -->
                        <div class="mb-3">
                            <label class="form-label">Skills</label>
                            <div class="skill-filter" style="max-height: 200px; overflow-y: auto;">
                                <?php 
                                // Group skills by category
                                $skills_by_category = [];
                                foreach ($skills as $skill) {
                                    $category = $skill['category_name'];
                                    if (!isset($skills_by_category[$category])) {
                                        $skills_by_category[$category] = [];
                                    }
                                    $skills_by_category[$category][] = $skill;
                                }
                                
                                foreach ($skills_by_category as $category => $category_skills):
                                ?>
                                    <div class="mb-2">
                                        <strong><?= htmlspecialchars($category) ?></strong>
                                        <?php foreach ($category_skills as $skill): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="skills[]" value="<?= $skill['skill_id'] ?>" 
                                                       id="skill_<?= $skill['skill_id'] ?>"
                                                       <?= in_array($skill['skill_id'], $selected_skills) ? 'checked' : '' ?>>
                                                <label class="form-check-label <?= in_array($skill['skill_id'], $auto_detected_skills) ? 'text-success fw-bold' : '' ?>" for="skill_<?= $skill['skill_id'] ?>">
                                                    <?= htmlspecialchars($skill['skill_name']) ?>
                                                    <?= in_array($skill['skill_id'], $auto_detected_skills) ? ' <i class="bi bi-check-circle-fill text-success small"></i>' : '' ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div class="mb-3">
                            <label class="form-label">Price Range (€/hour)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" name="min_price" 
                                               value="<?= $min_price > 0 ? $min_price : '' ?>" placeholder="Min" min="0">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" name="max_price" 
                                               value="<?= $max_price < 9999999 ? $max_price : '' ?>" placeholder="Max" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rating Filter -->
                        <div class="mb-3">
                            <label for="min_rating" class="form-label">Minimum Rating</label>
                            <select class="form-select" id="min_rating" name="min_rating">
                                <option value="0" <?= $min_rating == 0 ? 'selected' : '' ?>>Any Rating</option>
                                <option value="3" <?= $min_rating == 3 ? 'selected' : '' ?>>3+ Stars</option>
                                <option value="4" <?= $min_rating == 4 ? 'selected' : '' ?>>4+ Stars</option>
                                <option value="4.5" <?= $min_rating == 4.5 ? 'selected' : '' ?>>4.5+ Stars</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="mb-3">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="relevance" <?= $sort_by == 'relevance' ? 'selected' : '' ?>>Most Relevant</option>
                                <option value="rating_desc" <?= $sort_by == 'rating_desc' ? 'selected' : '' ?>>Highest Rating</option>
                                <option value="rating_asc" <?= $sort_by == 'rating_asc' ? 'selected' : '' ?>>Lowest Rating</option>
                                <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="coach-search.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear All Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Search Results -->
        <div class="col-md-9">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if (!empty($coaches)): ?>
                            <?= count($coaches) ?> Coach<?= count($coaches) > 1 ? 'es' : '' ?> Found
                        <?php else: ?>
                            Coaches
                        <?php endif; ?>
                    </h5>
                    
                    <!-- Sort controls moved to header for better visibility -->
                    <div class="d-flex align-items-center">
                        <label for="sort_by" class="text-white me-2 mb-0">Sort:</label>
                        <select id="sort_by" name="sort_by" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="relevance" <?= $sort_by === 'relevance' ? 'selected' : '' ?>>Most Relevant</option>
                            <option value="rating_desc" <?= $sort_by === 'rating_desc' ? 'selected' : '' ?>>Highest Rated</option>
                            <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($coaches)): ?>
                        <div class="alert alert-info">
                            <p>No coaches found matching your criteria. Try adjusting your filters.</p>
                            <?php if (isset($_GET['debug'])): ?>
                                <div class="mt-3 p-3 bg-light">
                                    <p><strong>Debug Information:</strong></p>
                                    <p>Active filters:</p>
                                    <ul>
                                        <?php if (!empty($search_query)): ?>
                                            <li>Search terms: <?= htmlspecialchars($search_query) ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($selected_category)): ?>
                                            <li>Selected category: <?= htmlspecialchars(getCategoryName($pdo, $selected_category)) ?></li>
                                        <?php endif; ?>
                                        <?php if (!empty($selected_skills) && count($selected_skills) > 0): ?>
                                            <li>Selected skills: 
                                                <?php 
                                                $skill_names = [];
                                                foreach ($selected_skills as $skill_id) {
                                                    $skill_names[] = getSkillName($pdo, $skill_id);
                                                }
                                                echo htmlspecialchars(implode(', ', $skill_names)); 
                                                ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($min_rating > 0): ?>
                                            <li>Minimum rating: <?= htmlspecialchars($min_rating) ?></li>
                                        <?php endif; ?>
                                        <?php if ($min_price > 0): ?>
                                            <li>Minimum price: €<?= htmlspecialchars($min_price) ?></li>
                                        <?php endif; ?>
                                        <?php if ($max_price < 9999999): ?>
                                            <li>Maximum price: €<?= htmlspecialchars($max_price) ?></li>
                                        <?php endif; ?>
                                    </ul>
                                    <p>Total coaches in database: 
                                        <?php
                                        $count_stmt = $pdo->query("SELECT COUNT(*) FROM Coaches");
                                        echo $count_stmt->fetchColumn();
                                        ?>
                                    </p>
                                    <p><a href="coach-search.php" class="btn btn-sm btn-outline-secondary">Clear all filters</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($coaches as $coach): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex mb-3">
                                                <?php 
                                                // Profile image handling with fallbacks
                                                $profile_image = $coach['profile_image'] ?? 'default.jpg';
                                                $image_path = "/assets/images/profiles/{$profile_image}";
                                                $default_image = "/assets/images/profiles/default.jpg";
                                                
                                                // Check if file exists and is readable
                                                $full_image_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
                                                $full_default_path = $_SERVER['DOCUMENT_ROOT'] . $default_image;
                                                
                                                // If user image doesn't exist or fallback doesn't exist, use an external default
                                                if (file_exists($full_image_path) && is_readable($full_image_path)) {
                                                    $display_image = $image_path;
                                                } elseif (file_exists($full_default_path) && is_readable($full_default_path)) {
                                                    $display_image = $default_image;
                                                } else {
                                                    // Fallback to a reliable external avatar generator
                                                    $display_image = "https://ui-avatars.com/api/?name=" . urlencode($coach['username']) . "&background=random";
                                                }
                                                ?>
                                                
                                                <img src="<?= $display_image ?>" alt="<?= htmlspecialchars($coach['username']) ?>" 
                                                     class="rounded-circle me-3" style="width: 64px; height: 64px; object-fit: cover;">
                                                
                                                <div>
                                                    <h5 class="card-title mb-1">
                                                        <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="text-decoration-none">
                                                            <?= !empty($search_query) ? highlightMatchingTerms($coach['username'], $search_query) : htmlspecialchars($coach['username']) ?>
                                                        </a>
                                                    </h5>
                                                    <p class="text-muted small mb-1">
                                                        <?= !empty($search_query) ? highlightMatchingTerms($coach['headline'], $search_query) : htmlspecialchars($coach['headline']) ?>
                                                    </p>
                                                    
                                                    <!-- Rating display -->
                                                    <div class="mb-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= floor($coach['rating'])): ?>
                                                                <i class="bi bi-star-fill text-warning small"></i>
                                                            <?php elseif ($i - 0.5 == $coach['rating']): ?>
                                                                <i class="bi bi-star-half text-warning small"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star text-warning small"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                        <span class="ms-1 small"><?= number_format($coach['rating'], 1) ?></span>
                                                    </div>
                                                    
                                                    <div class="d-flex">
                                                        <span class="badge bg-primary me-1">€<?= number_format($coach['hourly_rate'], 2) ?>/hr</span>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($coach['experience']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <p class="card-text small">
                                                <?= !empty($search_query) ? highlightMatchingTerms(substr($coach['bio'], 0, 150) . (strlen($coach['bio']) > 150 ? '...' : ''), $search_query) : htmlspecialchars(substr($coach['bio'], 0, 150)) . (strlen($coach['bio']) > 150 ? '...' : '') ?>
                                            </p>
                                            
                                            <?php if (!empty($coach['top_skills'])): ?>
                                                <div class="mb-2">
                                                    <strong class="small">Top Skills:</strong>
                                                    <div>
                                                        <?php foreach ($coach['top_skills'] as $skill): ?>
                                                            <span class="badge bg-light text-dark me-1 mb-1">
                                                                <?= !empty($search_query) ? highlightMatchingTerms($skill['skill_name'], $search_query) : htmlspecialchars($skill['skill_name']) ?>
                                                                <?php if ($skill['proficiency_level'] >= 4): ?>
                                                                    <i class="bi bi-star-fill text-warning small"></i>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Debug: Relevance score for search results -->
                                            <?php if (isset($_GET['debug']) && isset($coach['relevance_score'])): ?>
                                                <div class="small text-muted">
                                                    Relevance score: <?= $coach['relevance_score'] ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                                View Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for search functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const queryInput = document.getElementById('query');
    const suggestionsContainer = document.getElementById('search-suggestions');
    const popularSearches = <?= json_encode($popular_searches) ?>;
    
    if (queryInput) {
        // Show suggestions when input is focused
        queryInput.addEventListener('focus', function() {
            if (queryInput.value.length > 0) {
                showSuggestions(queryInput.value);
            }
        });
        
        // Update suggestions as user types
        queryInput.addEventListener('input', function() {
            if (queryInput.value.length > 0) {
                showSuggestions(queryInput.value);
            } else {
                suggestionsContainer.style.display = 'none';
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!queryInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }
    
    function showSuggestions(query) {
        const filteredSuggestions = popularSearches.filter(term => 
            term.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 8); // Limit to 8 suggestions
        
        if (filteredSuggestions.length > 0) {
            suggestionsContainer.innerHTML = '';
            
            filteredSuggestions.forEach(suggestion => {
                const suggestionElem = document.createElement('div');
                suggestionElem.className = 'p-2 suggestion-item';
                suggestionElem.style.cursor = 'pointer';
                suggestionElem.innerHTML = highlightQuery(suggestion, query);
                
                suggestionElem.addEventListener('click', function() {
                    queryInput.value = suggestion;
                    suggestionsContainer.style.display = 'none';
                    document.querySelector('form').submit();
                });
                
                suggestionElem.addEventListener('mouseover', function() {
                    this.classList.add('bg-light');
                });
                
                suggestionElem.addEventListener('mouseout', function() {
                    this.classList.remove('bg-light');
                });
                
                suggestionsContainer.appendChild(suggestionElem);
            });
            
            suggestionsContainer.style.display = 'block';
        } else {
            suggestionsContainer.style.display = 'none';
        }
    }
    
    function highlightQuery(text, query) {
        const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
        return text.replace(regex, '<strong class="bg-warning text-dark">$1</strong>');
    }
    
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
});
</script>

<?php include '../includes/footer.php'; ?> 