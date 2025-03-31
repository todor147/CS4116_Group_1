<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Get search parameters
$search_query = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? null;
$min_price = $_GET['min_price'] ?? 0;
$max_price = $_GET['max_price'] ?? 1000;
$min_rating = $_GET['min_rating'] ?? 0;
$sort_by = $_GET['sort'] ?? 'relevance';

// Build base query
$query = "
    SELECT c.coach_id, u.username, c.expertise, c.availability, 
           AVG(r.rating_value) as avg_rating, COUNT(r.rating_id) as rating_count,
           MIN(st.price) as min_price, MAX(st.price) as max_price
    FROM Coaches c
    JOIN Users u ON c.user_id = u.user_id
    LEFT JOIN Ratings r ON c.coach_id = r.coach_id
    LEFT JOIN ServiceTiers st ON c.coach_id = st.coach_id
    WHERE u.is_banned = 0
";

// Add search conditions
$conditions = [];
$params = [];

if (!empty($search_query)) {
    $conditions[] = "(u.username LIKE ? OR c.expertise LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

if ($category_id) {
    $conditions[] = "c.coach_id IN (
        SELECT coach_id FROM CoachCategories WHERE category_id = ?
    )";
    $params[] = $category_id;
}

if ($min_price > 0 || $max_price < 1000) {
    $conditions[] = "st.price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
}

if ($min_rating > 0) {
    $conditions[] = "(
        SELECT AVG(r2.rating_value) 
        FROM Ratings r2 
        WHERE r2.coach_id = c.coach_id
    ) >= ?";
    $params[] = $min_rating;
}

// Add conditions to query
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Group by coach
$query .= " GROUP BY c.coach_id";

// Add sorting
switch ($sort_by) {
    case 'rating':
        $query .= " ORDER BY avg_rating DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY min_price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY max_price DESC";
        break;
    default:
        $query .= " ORDER BY (
            CASE WHEN u.username LIKE ? THEN 1 ELSE 0 END +
            CASE WHEN c.expertise LIKE ? THEN 1 ELSE 0 END
        ) DESC";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        break;
}

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Advanced Search</h1>
    
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="advanced_search.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="q" class="form-label">Search</label>
                            <input type="text" class="form-control" id="q" name="q" value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php
                                $categories = $pdo->query("SELECT * FROM Categories")->fetchAll();
                                foreach ($categories as $category) {
                                    $selected = $category['category_id'] == $category_id ? 'selected' : '';
                                    echo "<option value='{$category['category_id']}' $selected>{$category['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="min_price" class="form-label">Min Price</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="<?= $min_price ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="max_price" class="form-label">Max Price</label>
                            <input type="number" class="form-control" id="max_price" name="max_price" value="<?= $max_price ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="min_rating" class="form-label">Min Rating</label>
                            <input type="number" class="form-control" id="min_rating" name="min_rating" min="0" max="5" step="0.1" value="<?= $min_rating ?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="relevance" <?= $sort_by == 'relevance' ? 'selected' : '' ?>>Relevance</option>
                                <option value="rating" <?= $sort_by == 'rating' ? 'selected' : '' ?>>Rating</option>
                                <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <div class="row">
        <?php foreach ($results as $result): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($result['username']) ?></h5>
                    <p class="card-text"><?= htmlspecialchars($result['expertise']) ?></p>
                    <p class="card-text">
                        Rating: <?= number_format($result['avg_rating'], 1) ?> (<?= $result['rating_count'] ?> reviews)
                    </p>
                    <p class="card-text">
                        Price: $<?= number_format($result['min_price'], 2) ?> - $<?= number_format($result['max_price'], 2) ?>
                    </p>
                    <a href="coach-profile.php?id=<?= $result['coach_id'] ?>" class="btn btn-primary">View Profile</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 