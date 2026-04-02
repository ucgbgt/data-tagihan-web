<?php
declare(strict_types=1);

// Handle preflight OPTIONS request immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Response.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Router.php';
require_once __DIR__ . '/src/Log.php';
require_once __DIR__ . '/src/ImageProcessor.php';
require_once __DIR__ . '/src/Controllers/AuthController.php';
require_once __DIR__ . '/src/Controllers/TagihanController.php';
require_once __DIR__ . '/src/Controllers/UploadController.php';
require_once __DIR__ . '/src/Controllers/UserController.php';
require_once __DIR__ . '/src/Controllers/LogController.php';

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
});

$router = new Router();

// ── Auth ──────────────────────────────────────────────────────────────────
$router->post('/api/auth/login',  [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me',      [AuthController::class, 'me']);

// ── Tagihan ───────────────────────────────────────────────────────────────
$router->get('/api/tagihan/aktif',               [TagihanController::class, 'aktif']);
$router->get('/api/tagihan',                     [TagihanController::class, 'index']);
$router->get('/api/tagihan/:id',                 [TagihanController::class, 'show']);
$router->post('/api/tagihan/bulk',               [TagihanController::class, 'bulk']);
$router->post('/api/tagihan',                    [TagihanController::class, 'store']);
$router->put('/api/tagihan/:id',                 [TagihanController::class, 'update']);
$router->patch('/api/tagihan/:id/status',        [TagihanController::class, 'changeStatus']);
$router->patch('/api/tagihan/:id/verify',        [TagihanController::class, 'verify']);
$router->patch('/api/tagihan/:id/unverify',      [TagihanController::class, 'unverify']);
$router->delete('/api/tagihan/:id',              [TagihanController::class, 'destroy']);

// ── Upload / File ─────────────────────────────────────────────────────────
$router->post('/api/tagihan/:id/upload/:type',   [UploadController::class, 'upload']);
$router->get('/api/tagihan/:id/file/:type',      [UploadController::class, 'serve']);

// ── Users ─────────────────────────────────────────────────────────────────
$router->get('/api/users',                       [UserController::class, 'index']);
$router->post('/api/users',                      [UserController::class, 'store']);
$router->delete('/api/users/:id',                [UserController::class, 'destroy']);
$router->patch('/api/users/:id/toggle',          [UserController::class, 'toggle']);
$router->patch('/api/users/:id/password',        [UserController::class, 'resetPassword']);

// ── Logs ──────────────────────────────────────────────────────────────────
$router->get('/api/logs',                        [LogController::class, 'index']);

$router->dispatch();
