<?php
// api-proxy.php
// This file securely handles API calls to Anthropic

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// IMPORTANT: Replace with your actual Anthropic API key
// Get your key from: https://console.anthropic.com/settings/keys
$api_key = 'YOUR_ANTHROPIC_API_KEY_HERE';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

$data = [
    'model' => 'claude-sonnet-4-20250514',
    'max_tokens' => 4000,
    'messages' => [
        [
            'role' => 'user',
            'content' => $input['prompt']
        ]
    ]
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $api_key,
    'anthropic-version: 2023-06-01'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($http_code);
echo $response;
?>
