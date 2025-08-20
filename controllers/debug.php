<?php
// debug.php - Quick test to see what auth_api.php returns
header('Content-Type: text/html; charset=utf-8');

echo "<h2>API Response Test</h2>";

// Test the API endpoint
$url = 'http://localhost/NewProject/controllers/auth_api.php?action=test';

echo "<h3>Testing: $url</h3>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Accept: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);

echo "<h4>Raw Response:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h4>Response Headers:</h4>";
echo "<pre>" . print_r($http_response_header, true) . "</pre>";

if ($response) {
    echo "<h4>JSON Decode Test:</h4>";
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<pre>✅ Valid JSON:\n" . print_r($decoded, true) . "</pre>";
    } else {
        echo "<pre>❌ JSON Error: " . json_last_error_msg() . "</pre>";
        echo "<pre>First 200 characters:\n" . substr($response, 0, 200) . "</pre>";
    }
}
?>