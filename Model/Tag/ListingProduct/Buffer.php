<?php

namespace M2E\Temu\Model\Tag\ListingProduct;

use M2E\Temu\Model\ResourceModel;

class Buffer
{
    private const MAX_PACK_SIZE = 500;

    /** @var array<int, Buffer\Item> */
    private array $items = [];
    private ResourceModel\Tag\ListingProduct\Relation $relationResource;
    private \M2E\Temu\Model\Tag\Repository $tagRepository;
    private Repository $listingProductTagRepository;
    private \M2E\Temu\Model\Product\Repository $productRepository;
    private \M2E\Temu\Model\Tag\BlockingErrors $blockingErrors;

    public function __construct(
        \M2E\Temu\Model\Tag\Repository $tagRepository,
        ResourceModel\Tag\ListingProduct\Relation $relationResource,
        Repository $listingProductTagRepository,
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\Tag\BlockingErrors $blockingErrors
    ) {
        $this->relationResource = $relationResource;
        $this->tagRepository = $tagRepository;
        $this->listingProductTagRepository = $listingProductTagRepository;
        $this->productRepository = $productRepository;
        $this->blockingErrors = $blockingErrors;
    }

    public function addTag(\M2E\Temu\Model\Product $listingProduct, \M2E\Temu\Model\Tag $tag): void
    {
        $this->addTags($listingProduct, [$tag]);
    }

    /**
     * @param \M2E\Temu\Model\Product $listingProduct
     * @param \M2E\Temu\Model\Tag[] $tags
     *
     * @return void
     */
    public function addTags(\M2E\Temu\Model\Product $listingProduct, array $tags): void
    {
        $item = $this->getItem($listingProduct->getId());
        foreach ($tags as $tag) {
            $item->addTag($tag);
        }
    }

    public function removeAllTags(\M2E\Temu\Model\Product $listingProduct): void
    {
        $item = $this->getItem($listingProduct->getId());
        foreach ($this->tagRepository->getAllTags() as $tag) {
            $item->removeTag($tag);
        }
    }

    public function removeTagByCode(\M2E\Temu\Model\Product $product, string $code): void
    {
        $item = $this->getItem($product->getId());
        $tag = $this->tagRepository->findTagByCode($code);
        if ($tag !== null) {
            $item->removeTag($tag);
        }
    }

    private function getItem(int $productId): Buffer\Item
    {
        return $this->items[$productId] ?? $this->items[$productId] = new Buffer\Item($productId);
    }

    // ----------------------------------------

    public function flush(): void
    {
        if (empty($this->items)) {
            return;
        }

        $this->createNewTags($this->items);

        $tagsEntitiesByErrorCode = $this->getTagsEntitiesByErrorCode();
        $existedRelations = $this->getExistsRelationsByProductId($this->items);

        $this->flushAdd($this->items, $tagsEntitiesByErrorCode, $existedRelations);
        $this->flushRemove($this->items, $tagsEntitiesByErrorCode, $existedRelations);

        $this->items = [];
    }

    /**
     * @param Buffer\Item[] $items
     *
     * @return void
     */
    private function createNewTags(array $items): void
    {
        foreach ($items as $item) {
            foreach ($item->getAddedTags() as $tag) {
                $this->tagRepository->create($tag);
            }
        }
    }

    /**
     * @return array<string, \M2E\Temu\Model\Tag\Entity>
     */
    private function getTagsEntitiesByErrorCode(): array
    {
        $result = [];
        foreach ($this->tagRepository->getAllEntities() as $entity) {
            $result[$entity->getErrorCode()] = $entity;
        }

        return $result;
    }

    /**
     * @param Buffer\Item[] $items
     *
     * @return array<int, <string, \M2E\Temu\Model\Tag\Entity>>
     */
    private function getExistsRelationsByProductId(array $items): array
    {
        $productsIds = array_map(
            function ($item) {
                return $item->getProductId();
            },
            $items
        );
        $relations = $this->listingProductTagRepository->findRelationsByProductIds($productsIds);

        $result = [];
        foreach ($relations as $relation) {
            $tagEntity = $this->tagRepository->findEntityById($relation->getTagId());
            if ($tagEntity === null) {
                continue;
            }

            $result[$relation->getListingProductId()][$tagEntity->getErrorCode()] = $tagEntity;
        }

        return $result;
    }

    /**
     * @param \M2E\Temu\Model\Tag\ListingProduct\Buffer\Item[] $items
     * @param array $tagsEntitiesByErrorCode
     * @param array $existsRelations
     *
     * @return void
     */
    private function flushAdd(array $items, array $tagsEntitiesByErrorCode, array $existsRelations): void
    {
        $pack = [];
        $productIdsWithBlockingError = [];

        $blockingErrorsList = $this->blockingErrors->getList();
        foreach ($items as $item) {
            $existRelation = $existsRelations[$item->getProductId()] ?? [];
            foreach ($item->getAddedTags() as $tag) {
                if (!isset($existRelation[$tag->getErrorCode()])) {
                    $pack[$item->getProductId()][] = (int)$tagsEntitiesByErrorCode[$tag->getErrorCode()]->getId();

                    if (in_array($tag->getErrorCode(), $blockingErrorsList, true)) {
                        $productIdsWithBlockingError[] = $item->getProductId();
                    }
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->insertTags($chunk);
            }
        }

        if (!empty($productIdsWithBlockingError)) {
            $lastBlockingErrorDate = \M2E\Core\Helper\Date::createCurrentGmt();
            foreach (array_chunk($productIdsWithBlockingError, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->productRepository->updateLastBlockingErrorDate($chunk, $lastBlockingErrorDate);
            }
        }
    }

    /**
     * @param \M2E\Temu\Model\Tag\ListingProduct\Buffer\Item[] $items
     * @param array $tagsEntitiesByErrorCode
     * @param array $existsRelations
     *
     * @return void
     */
    private function flushRemove(array $items, array $tagsEntitiesByErrorCode, array $existsRelations): void
    {
        $pack = [];

        foreach ($items as $item) {
            $existRelation = $existsRelations[$item->getProductId()] ?? [];
            foreach ($item->getRemovedTags() as $tag) {
                if (isset($existRelation[$tag->getErrorCode()])) {
                    $pack[$item->getProductId()][] = (int)$tagsEntitiesByErrorCode[$tag->getErrorCode()]->getId();
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->removeTags($chunk);
            }
        }
    }
}
