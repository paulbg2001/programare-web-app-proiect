<?php

require_once __DIR__ . '/DashboardRepository.php';

class DashboardService
{
    private $repository;

    public function __construct(DashboardRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getDashboardData(): array
    {
        return [
            'vehicles' => $this->repository->getVehicleStats(),
            'documents' => $this->repository->getDocumentStats(),
            'importantDocuments' => $this->repository->getImportantDocuments(),
        ];
    }
}
