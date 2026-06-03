<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';
require_once __DIR__ . '/../../src/VehicleRepository.php';

requireApiAuth();

$input = requestData();
$method = requestMethod($input);

try {
    $repository = new VehicleRepository(getDbConnection());

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $vehicle = $repository->findById(requiredId($_GET['id']));

            if ($vehicle === null) {
                jsonResponse(['success' => false, 'error' => 'Vehicle not found.'], 404);
            }

            jsonResponse(['success' => true, 'vehicle' => $vehicle]);
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, (int) ($_GET['per_page'] ?? 8));

        jsonResponse([
            'success' => true,
            'vehicles' => $repository->listPaginated($page, $perPage),
        ]);
    }

    if ($method === 'POST') {
        $action = $input['action'] ?? 'create';

        if ($action === 'create') {
            $id = $repository->create($input);

            jsonResponse([
                'success' => true,
                'vehicle' => $repository->findById($id),
            ], 201);
        }

        if ($action === 'update') {
            $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
            $repository->update($id, $input);

            jsonResponse([
                'success' => true,
                'vehicle' => $repository->findById($id),
            ]);
        }

        if ($action === 'deactivate') {
            $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
            $repository->deactivate($id);

            jsonResponse([
                'success' => true,
                'vehicle' => $repository->findById($id),
            ]);
        }
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
        $repository->update($id, $input);

        jsonResponse([
            'success' => true,
            'vehicle' => $repository->findById($id),
        ]);
    }

    if ($method === 'DELETE') {
        $id = requiredId($input['id'] ?? $_GET['id'] ?? null);
        $repository->deactivate($id);

        jsonResponse([
            'success' => true,
            'vehicle' => $repository->findById($id),
        ]);
    }

    jsonResponse(['success' => false, 'error' => 'Unsupported vehicle action.'], 405);
} catch (DomainException $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], domainStatus($e));
} catch (PDOException $e) {
    appLog('Vehicle API database error', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    jsonResponse(['success' => false, 'error' => 'Vehicle operation failed.'], 500);
}

