<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Category\Dictionary;

class TitleService
{
    private \M2E\Temu\Model\Category\Dictionary\Repository $repository;

    public function __construct(\M2E\Temu\Model\Category\Dictionary\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getUnique(string $region, int $categoryId, string $path): string
    {
        $existedTitles = $this->getExistedTitles($region, $categoryId);
        $pathParts = explode('>', $path);
        $lastPathPart = trim(end($pathParts));
        $counter = 1;
        do {
            $candidateTitle = sprintf('%s #%s', $lastPathPart, $counter);
            $counter++;
        } while (in_array($candidateTitle, $existedTitles));

        return $candidateTitle;
    }

    public function isUnique(string $region, int $categoryId, string $candidateTitle): bool
    {
        $existedTitles = $this->getExistedTitles($region, $categoryId);

        return !in_array($candidateTitle, $existedTitles);
    }

    private function getExistedTitles(string $region, int $categoryId): array
    {
        $existedCategories = $this->repository
            ->findAllByRegionAndCategoryId($region, $categoryId);

        if (empty($existedCategories)) {
            return [];
        }

        $existedTitles = [];
        foreach ($existedCategories as $category) {
            $existedTitles[] = $category->getTitle();
        }

        return $existedTitles;
    }
}
