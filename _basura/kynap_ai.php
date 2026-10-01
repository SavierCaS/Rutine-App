<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$api_key = trim("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");

$data = json_decode(file_get_contents("php://input"), true);

$prompt = isset($data['message']) ? trim($data['message']) : '';


if (empty($prompt)) {

    echo json_encode([
        'response' => 'No se ha recibido un prompt.'
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$url = "https://openrouter.ai/api/v1/chat/completions";

$request_data = [
    "model" => "inclusionai/ling-3.0-flash-fin:free",

    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $api_key,
    "Content-Type: application/json",
    "Accept: application/json"
]);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($request_data, JSON_UNESCAPED_UNICODE)
);


$response = curl_exec($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($response === false) {

    echo json_encode([
        'response' => 'Error de conexión con la IA: ' . $error
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$response_data = json_decode($response, true);

if ($http_code !== 200) {

    $error_message = 'Error de API.';

    if (isset($response_data['error']['message'])) {
        $error_message = $response_data['error']['message'];
    }

    echo json_encode([
        'response' => $error_message
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

if (isset($response_data['choices'][0]['message']['content'])) {

    $ai_response = $response_data['choices'][0]['message']['content'];

    echo json_encode([
        'response' => $ai_response
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

echo json_encode([
    'response' => 'No se pudo obtener el texto.'
], JSON_UNESCAPED_UNICODE);

?>