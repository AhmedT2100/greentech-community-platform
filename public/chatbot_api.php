<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// LM Studio API endpoint (OpenAI-compatible)
require_once __DIR__ . '/../config/environment.php';
define('LM_STUDIO_API', app_env('LM_STUDIO_API', 'http://127.0.0.1:1234/v1/chat/completions'));

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['messages']) || !is_array($data['messages'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request format'
    ]);
    exit;
}

// Prepare request for LM Studio
$requestData = [
    'model' => app_env('LM_STUDIO_MODEL', 'tinyllama-1.1b-chat-v1.0'),  // The model you have loaded
    'messages' => $data['messages'],
    'temperature' => 0.7,
    'max_tokens' => 150,  // Keep responses concise
    'stream' => false
];

// Initialize cURL
$ch = curl_init(LM_STUDIO_API);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($curlError) {
    echo json_encode([
        'success' => false,
        'message' => 'LM Studio is not reachable. Please ensure it is running at http://127.0.0.1:1234',
        'error' => $curlError
    ]);
    exit;
}

// Check HTTP response code
if ($httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'LM Studio returned an error',
        'http_code' => $httpCode,
        'response' => $response
    ]);
    exit;
}

// Parse and return response
$aiResponse = json_decode($response, true);

if (isset($aiResponse['choices'][0]['message']['content'])) {
    echo json_encode([
        'success' => true,
        'message' => $aiResponse['choices'][0]['message']['content']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid response from LM Studio',
        'response' => $aiResponse
    ]);
}
