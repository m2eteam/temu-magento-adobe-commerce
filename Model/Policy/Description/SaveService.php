<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Description;

use M2E\Temu\Model\Policy\Description;

class SaveService
{
    private \M2E\Temu\Model\Policy\DescriptionFactory $descriptionFactory;
    private Description\Repository $descriptionRepository;
    private Description\SnapshotBuilderFactory $snapshotBuilderFactory;
    private Description\BuilderFactory $builderFactory;
    private Description\DiffFactory $diffFactory;
    private Description\AffectedListingsProductsFactory $affectedProductsFactory;
    private Description\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \M2E\Temu\Model\Policy\DescriptionFactory $descriptionFactory,
        Description\ChangeProcessorFactory $changeProcessorFactory,
        Description\AffectedListingsProductsFactory $affectedProductsFactory,
        Description\DiffFactory $diffFactory,
        Description\BuilderFactory $builderFactory,
        Description\Repository $descriptionRepository,
        Description\SnapshotBuilderFactory $snapshotBuilderFactory
    ) {
        $this->descriptionFactory = $descriptionFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->affectedProductsFactory = $affectedProductsFactory;
        $this->diffFactory = $diffFactory;
        $this->builderFactory = $builderFactory;
        $this->descriptionRepository = $descriptionRepository;
        $this->snapshotBuilderFactory = $snapshotBuilderFactory;
    }

    public function save(array $data): Description
    {
        $templateModel = $this->descriptionFactory->create();

        if (empty($data['id'])) {
            $oldData = [];
        } else {
            $templateModel = $this->descriptionRepository->get((int)$data['id']);
            $oldData = $this->makeSnapshot($templateModel);
        }

        $templateBuilder = $this->builderFactory->create();
        $templateBuilder->build($templateModel, $data);
        if (empty($data['id'])) {
            $this->descriptionRepository->create($templateModel);
        } else {
            $this->descriptionRepository->save($templateModel);
        }

        $snapshotBuilder = $this->snapshotBuilderFactory->create();
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
        $snapshotBuilder = $this->snapshotBuilderFactory->create();
        $snapshotBuilder->setModel($model);

        return $snapshotBuilder->getSnapshot();
    }
}
