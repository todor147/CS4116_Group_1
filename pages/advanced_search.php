<?php
// Redirect to the main search page with parameters
session_start();

// Map parameters from advanced_search.php to coach-search.php
$search_query = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$min_rating = $_GET['min_rating'] ?? '';
$sort_by = $_GET['sort'] ?? 'relevance';

// Map sort parameter values
$sort_mapping = [
    'relevance' => 'relevance',
    'rating' => 'rating_desc',
    'price_low' => 'price_asc',
    'price_high' => 'price_desc'
];

$mapped_sort = isset($sort_mapping[$sort_by]) ? $sort_mapping[$sort_by] : 'relevance';

// Build the redirect URL
$redirect_url = 'coach-search.php?';
$params = [];

if (!empty($search_query)) {
    $params[] = 'query=' . urlencode($search_query);
}

if (!empty($category_id)) {
    $params[] = 'category=' . urlencode($category_id);
}

if (!empty($min_price)) {
    $params[] = 'min_price=' . urlencode($min_price);
}

if (!empty($max_price)) {
    $params[] = 'max_price=' . urlencode($max_price);
}

if (!empty($min_rating)) {
    $params[] = 'min_rating=' . urlencode($min_rating);
}

$params[] = 'sort_by=' . $mapped_sort;

$redirect_url .= implode('&', $params);

// Redirect to the main search page
header('Location: ' . $redirect_url);
exit;
?> 