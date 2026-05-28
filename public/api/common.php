<?php

function requireApiAuth(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
    }
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    $data = $_POST;
    $raw = trim((string) file_get_contents('php://input'));

    if ($raw !== '') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $json = json_decode($raw, true);

            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        } else {
            parse_str($raw, $parsed);

            if (is_array($parsed)) {
                $data = array_merge($data, $parsed);
            }
        }
    }

    return $data;
}

function requestMethod(array $input): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'POST' && isset($input['_method'])) {
        $method = strtoupper((string) $input['_method']);
    }

    return $method;
}

function requiredId($value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        throw new DomainException('A valid id is required.');
    }

    return $id;
}

function domainStatus(DomainException $e): int
{
    return strpos(strtolower($e->getMessage()), 'not found') !== false ? 404 : 422;
}

