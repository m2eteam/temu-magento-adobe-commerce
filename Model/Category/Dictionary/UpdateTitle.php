<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class UpdateTitle
{
    private \M2E\Temu\Model\Category\Dictionary\TitleService $titleService;
    private \M2E\Temu\Model\Category\Dictionary\Repository $repository;

    public function __construct(
        \M2E\Temu\Model\Category\Dictionary\TitleService $titleService,
        \M2E\Temu\Model\Category\Dictionary\Repository $repository
    ) {
        $this->titleService = $titleService;
        $this->repository = $repository;
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function execute(\M2E\Temu\Model\Category\Dictionary $dictionary, string $candidateTitle): bool
    {
        if ($dictionary->getTitle() === $candidateTitle) {
            return true;
        }

        $isUniqueTitle = $this->titleService->isUnique(
            $dictionary->getRegion(),
            (int)$dictionary->getCategoryId(),
            $candidateTitle
        );

        if (!$isUniqueTitle) {
            throw new \M2E\Temu\Model\Exception\Logic((string)__('Title not unique'));
        }

        $dictionary->setTitle($candidateTitle);
        $this->repository->save($dictionary);

        return true;
    }
}
