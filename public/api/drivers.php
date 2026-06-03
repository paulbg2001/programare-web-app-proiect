<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';
require_once __DIR__ . '/../../src/DriverRepository.php';

requireApiAuth();

$input = requestData();
$method = requestMethod($input);

try {
    $repository = new DriverRepository(getDbConnection());

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $driver = $repository->findById(requiredId($_GET['id']));

            if ($driver === null) {
                jsonResponse(['success' => false, 'error' => 'Driver not found.'], 404);
            }

            jsonResponse(['success' => true, 'driver' => $driver]);
        }

        $status = $_GET['status'] ?? null;
        $status = in_array($status, ['active', 'inactive'], true) ? $status : null;

        jsonResponse([
            'success' => true,
            'drivers' => $repository->listByStatus($status),
        ]);
    }

    if ($method === 'POST') {
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $id = $repository->create($input);

            jsonResponse([
                'success' => true,
                'driver' => $repository->findById($id),
            ], 201);
        }

        if ($action === 'update') {
            $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
            $repository->update($id, $input);

            jsonResponse([
                'success' => true,
                'driver' => $repository->findById($id),
            ]);
        }

        if ($action === 'deactivate') {
            $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
            $repository->deactivate($id);

            jsonResponse([
                'success' => true,
                'driver' => $repository->findById($id),
            ]);
        }
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
        $repository->update($id, $input);

        jsonResponse([
            'success' => true,
            'driver' => $repository->findById($id),
        ]);
    }

    if ($method === 'DELETE') {
        $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
        $repository->deactivate($id);

        jsonResponse([
            'success' => true,
            'driver' => $repository->findById($id),
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Unsupported driver action.'], 405);
} catch (DomainException $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], domainStatus($e));
} catch (PDOException $e) {
    appLog('Driver API database error', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    jsonResponse(['success' => false, 'error' => 'Driver operation failed.'], 500);
}

