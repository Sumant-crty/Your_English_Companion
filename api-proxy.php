<?php
// gemini-proxy.php
// This file securely handles API calls to Google Gemini 2.5 Flash

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// IMPORTANT: Replace with your actual Google Gemini API key
// Get your free key from: https://makersuite.google.com/app/apikey
$api_key = AIzaSyAJd1slmrkITmafx7CaMtZqE-EXxSlDppI;

// Read input from request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

// Prepare request data for Gemini API
$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $input['prompt']
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.7,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 8192,
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// Gemini API endpoint with model and API key
$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-exp:generateContent?key=' . $api_key;

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Parse Gemini response
$gemini_response = json_decode($response, true);

// Transform Gemini response to match expected format
if ($http_code === 200 && isset($gemini_response['candidates'][0]['content']['parts'][0]['text'])) {
    $text_content = $gemini_response['candidates'][0]['content']['parts'][0]['text'];
    
    // Return in Claude-compatible format
    $formatted_response = [
        'content' => [
            [
                'type' => 'text',
                'text' => $text_content
            ]
        ]
    ];
    
    http_response_code(200);
    echo json_encode($formatted_response);
} else {
    // Error handling
    http_response_code($http_code);
    
    if (isset($gemini_response['error'])) {
        echo json_encode([
            'error' => $gemini_response['error']['message'] ?? 'Unknown error from Gemini API'
        ]);
    } else {
        echo json_encode([
            'error' => 'Unexpected response format from Gemini API',
            'details' => $gemini_response
        ]);
    }
}
?>
