<?php

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../../helpers/session.php';
require_once __DIR__ . '/../../models/AdminSession.php';

inventra_bootstrap_session();

// We use this lightweight endpoint so frontend activity can refresh the same
// backend inactivity window used by Admin and Staff protected requests.
$session = new AdminSession();
$account = $session->resolveAuthenticatedAccount();

if ($account === null) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Your session has expired. Please log in again.',
        'session_expired' => true,
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'timeout_seconds' => INVENTRA_SESSION_TIMEOUT_SECONDS,
    'server_time' => time(),
]);
