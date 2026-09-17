<?php
session_start();
include("kynap_db.php");

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(
        ['response' => 'Sesión no válida.'],
        JSON_UNESCAPED_UNICODE
    );
    exit();
}

$user_id = intval($_SESSION['user_id']);

$data = json_decode(file_get_contents("php://input"), true);
$prompt = isset($data['message']) ? trim($data['message']) : '';

if (isset($data['reset_messages']) && $data['reset_messages'] === true) {
    $stmt_update = $conn->prepare(
        "UPDATE accounts SET mensajes_enviados = 0 WHERE id = ?"
    );

    $stmt_update->bind_param("i", $user_id);
    $stmt_update->execute();
    $stmt_update->close();

    echo json_encode(
        ['response' => 'Contador de mensajes restablecido'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}

if (empty($prompt)) {
    echo json_encode(
        ['response' => 'No se ha recibido un mensaje.'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}

$stmt = $conn->prepare(
    "SELECT mensajes_enviados FROM accounts WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($mensajes_enviados);
$stmt->fetch();
$stmt->close();

if ($mensajes_enviados >= 5) {
    echo json_encode(
        ['response' => 'Límite de mensajes alcanzado'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}

$api_key = "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";

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
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $api_key,
    "Accept: application/json"
]);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode(
        $request_data,
        JSON_UNESCAPED_UNICODE
    )
);

$response = curl_exec($ch);

$http_code = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$curl_error = curl_error($ch);

curl_close($ch);

if ($response === false) {
    echo json_encode(
        ['response' => 'Error al obtener respuesta de la IA.'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}


$response_data = json_decode($response, true);


if ($http_code !== 200) {
    echo json_encode(
        ['response' => 'Error al obtener respuesta de la IA.'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}

if (
    !isset(
        $response_data['choices'][0]['message']['content']
    )
) {
    echo json_encode(
        ['response' => 'Error inesperado en la respuesta de la IA.'],
        JSON_UNESCAPED_UNICODE
    );

    exit();
}

$ai_response =
    $response_data['choices'][0]['message']['content'];

echo json_encode(
    ['response' => $ai_response],
    JSON_UNESCAPED_UNICODE
);

$stmt_update = $conn->prepare(
    "UPDATE accounts
     SET mensajes_enviados = mensajes_enviados + 1
     WHERE id = ?"
);

$stmt_update->bind_param("i", $user_id);
$stmt_update->execute();
$stmt_update->close();

?>