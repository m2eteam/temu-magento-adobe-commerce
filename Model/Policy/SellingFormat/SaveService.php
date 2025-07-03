<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\SellingFormat;

use M2E\Temu\Model\Policy\SellingFormat;

class SaveService
{
    private \M2E\Temu\Model\Policy\SellingFormatFactory $sellingFormatFactory;
    private SellingFormat\Repository $sellingFormatRepository;
    private SellingFormat\SnapshotBuilderFactory $syncSnapshotBuilderFactory;
    private SellingFormat\BuilderFactory $builderFactory;
    private SellingFormat\DiffFactory $diffFactory;
    private SellingFormat\AffectedListingsProductsFactory $affectedProductsFactory;
    private SellingFormat\ChangeProcessorFactory $changeProcessorFactory;

    public function __construct(
        \M2E\Temu\Model\Policy\SellingFormatFactory $sellingFormatFactory,
        SellingFormat\ChangeProcessorFactory $changeProcessorFactory,
        SellingFormat\AffectedListingsProductsFactory $affectedProductsFactory,
        SellingFormat\DiffFactory $diffFactory,
        SellingFormat\BuilderFactory $builderFactory,
        SellingFormat\Repository $sellingFormatRepository,
        SellingFormat\SnapshotBuilderFactory $syncSnapshotBuilderFactory
    ) {
        $this->sellingFormatFactory = $sellingFormatFactory;
        $this->changeProcessorFactory = $changeProcessorFactory;
        $this->affectedProductsFactory = $affectedProductsFactory;
        $this->diffFactory = $diffFactory;
        $this->builderFactory = $builderFactory;
        $this->sellingFormatRepository = $sellingFormatRepository;
        $this->syncSnapshotBuilderFactory = $syncSnapshotBuilderFactory;
    }

    public function save(array $data): SellingFormat
    {
        $templateModel = $this->sellingFormatFactory->create();

        if (empty($data['id'])) {
            $oldData = [];
        } else {
            $templateModel = $this->sellingFormatRepository->get((int)$data['id']);
            $oldData = $this->makeSnapshot($templateModel);
        }

        $templateBuilder = $this->builderFactory->create();

        $templateBuilder->build($templateModel, $data);
        if (empty($data['id'])) {
            $this->sellingFormatRepository->create($templateModel);
        } else {
            $this->sellingFormatRepository->save($templateModel);
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
