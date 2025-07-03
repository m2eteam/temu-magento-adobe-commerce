<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Synchronization;

use M2E\Temu\Model\Policy\Synchronization;

class SaveService
{
    private \M2E\Temu\Model\Policy\SynchronizationFactory $synchronizationFactory;
    private Synchronization\Repository $synchronizationRepository;
    private Synchronization\SnapshotBuilderFactory $syncSnapshotBuilderFactory;
    private Synchronization\BuilderFactory $builderFactory;
    private Synchronization\DiffFactory $diffFactory;
    private Synchronization\AffectedListingsProductsFactory $affectedProductsFactory;
    private Synchronization\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \M2E\Temu\Model\Policy\SynchronizationFactory $synchronizationFactory,
        Synchronization\ChangeProcessorFactory $changeProcessorFactory,
        Synchronization\AffectedListingsProductsFactory $affectedProductsFactory,
        Synchronization\DiffFactory $diffFactory,
        Synchronization\BuilderFactory $builderFactory,
        Synchronization\Repository $synchronizationRepository,
        Synchronization\SnapshotBuilderFactory $syncSnapshotBuilderFactory
    ) {
        $this->synchronizationFactory = $synchronizationFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->affectedProductsFactory = $affectedProductsFactory;
        $this->diffFactory = $diffFactory;
        $this->builderFactory = $builderFactory;
        $this->synchronizationRepository = $synchronizationRepository;
        $this->syncSnapshotBuilderFactory = $syncSnapshotBuilderFactory;
    }

    public function save(array $data): Synchronization
    {
        $templateModel = $this->synchronizationFactory->create();

        if (empty($data['id'])) {
            $oldData = [];
        } else {
            $templateModel = $this->synchronizationRepository->get((int)$data['id']);
            $oldData = $this->makeSnapshot($templateModel);
        }

        $templateBuilder = $this->builderFactory->create();
        $templateBuilder->build($templateModel, $data);
        if (empty($data['id'])) {
            $this->synchronizationRepository->create($templateModel);
        } else {
            $this->synchronizationRepository->save($templateModel);
        }

        $snapshotBuilder = $this->syncSnapshotBuilderFactory->create();
        $snapshotBuilder->setModel($templateModel);

        $newData = $this->makeSnapshot($templateModel);

        $diff = $this->diffFactory->create();

        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        $affectedListingsProducts = $this->affectedProductsFactory->create();
        $affectedListingsProducts->setModel($templateModel);

        $changeProcessor = $this->changeProcessorFactory->create();

        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'])
        );

        return $templateModel;
    }

    private function makeSnapshot($model)
    {
        $snapshotBuilder = $this->syncSnapshotBuilderFactory->create();
        $snapshotBuilder->setModel($model);

        return $snapshotBuilder->getSnapshot();
    }
}
