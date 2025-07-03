<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Policy\Shipping;

class ShippingService
{
    private const CACHE_KEY_SHIPPING_TEMPLATES = 'shipping_templates';
    private const CACHE_LIFETIME_THIRTY_MINUTES = 1800;

    private \M2E\Temu\Helper\Data\Cache\Permanent $cache;
    private \M2E\Temu\Model\Channel\Shipping\TemplateRetriever $deliveryTemplateService;

    public function __construct(
        \M2E\Temu\Model\Channel\Shipping\TemplateRetriever $deliveryTemplateService,
        \M2E\Temu\Helper\Data\Cache\Permanent $cache
    ) {
        $this->cache = $cache;
        $this->deliveryTemplateService = $deliveryTemplateService;
    }

    /**
     * @param \M2E\Temu\Model\Account $account
     * @param bool $force
     *
     * @return \M2E\Temu\Model\Channel\Shipping\Template\Collection
     * @throws \M2E\Temu\Model\Exception
     */
    public function getTemplates(
        \M2E\Temu\Model\Account $account,
        bool $force
    ): \M2E\Temu\Model\Channel\Shipping\Template\Collection {
        if ($force) {
            $this->clearCache($account);

            return $this->retrieveTemplates($account);
        }

        $collection = $this->fromCache($account);
        if ($collection === null) {
            $collection = $this->retrieveTemplates($account);
            $this->toCache($collection, $account);
        }

        return $collection;
    }

    // ----------------------------------------

    /**
     * @param \M2E\Temu\Model\Account $account
     *
     * @return \M2E\Temu\Model\Channel\Shipping\Template\Collection
     * @throws \M2E\Temu\Model\Exception
     */
    private function retrieveTemplates(
        \M2E\Temu\Model\Account $account
    ): \M2E\Temu\Model\Channel\Shipping\Template\Collection {
        return $this->deliveryTemplateService->retrieve($account);
    }

    private function toCache(
        \M2E\Temu\Model\Channel\Shipping\Template\Collection $collection,
        \M2E\Temu\Model\Account $account
    ): void {
        $data = [];
        foreach ($collection->getAll() as $template) {
            $data[] = [
                'id' => $template->id,
                'name' => $template->name,
            ];
        }

        $this->cache->setValue($this->createCacheKey($account), $data, [], self::CACHE_LIFETIME_THIRTY_MINUTES);
    }

    private function fromCache(\M2E\Temu\Model\Account $account): ?\M2E\Temu\Model\Channel\Shipping\Template\Collection
    {
        $value = $this->cache->getValue($this->createCacheKey($account));
        if ($value === null) {
            return null;
        }

        $collection = new \M2E\Temu\Model\Channel\Shipping\Template\Collection();
        foreach ($value as $templateRaw) {
            $collection->add(new \M2E\Temu\Model\Channel\Shipping\Template($templateRaw['id'], $templateRaw['name']));
        }

        return $collection;
    }

    private function clearCache(\M2E\Temu\Model\Account $account): void
    {
        $this->cache->removeValue($this->createCacheKey($account));
    }

    private function createCacheKey(
        \M2E\Temu\Model\Account $account
    ): string {
        return self::CACHE_KEY_SHIPPING_TEMPLATES . $account->getId();
    }
}
