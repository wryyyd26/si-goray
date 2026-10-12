<?php

declare(strict_types=1);

header('Content-Type: application/json');

function respond(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['message' => $message]);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path !== '/api/payments/callback') {
    respond(404, 'Not Found');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, 'Method Not Allowed');
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (!preg_match('/^application\/json(?:\s*;|$)/i', $contentType)) {
    respond(415, 'Unsupported Media Type');
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 65536) {
    respond(413, 'Payload Too Large');
}

$body = file_get_contents('php://input', false, null, 0, 65537);

if ($body === false) {
    respond(400, 'Invalid Request');
}

if (strlen($body) > 65536) {
    respond(413, 'Payload Too Large');
}

$ch = curl_init('http://127.0.0.1:8000/api/payments/callback');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$failed = $response === false;

curl_close($ch);

if ($failed || $status < 100 || $status > 599) {
    respond(502, 'Laravel webhook unavailable');
}

http_response_code($status);
echo $response;