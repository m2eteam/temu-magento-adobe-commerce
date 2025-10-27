<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\UpdateFromChannel;

use M2E\Temu\Model\Product;

class Processor
{
    private \M2E\Temu\Model\Product $product;
    private \M2E\Temu\Model\Channel\Product $channelProduct;
    /** @var \M2E\Temu\Model\Product\CalculateStatusByChannel */
    private Product\CalculateStatusByChannel $calculateStatusByChannel;

    private array $instructionsData = [];
    /** @var \M2E\Temu\Model\Listing\Log\Record[] */
    private array $logs = [];

    public function __construct(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Channel\Product $channelProduct,
        \M2E\Temu\Model\Product\CalculateStatusByChannel $calculateStatusByChannel
    ) {
        $this->product = $product;
        $this->channelProduct = $channelProduct;
        $this->calculateStatusByChannel = $calculateStatusByChannel;
    }

    public function processChanges(): ChangeResult
    {
        $isChangedProduct = $this->processProduct();
        $isChangedSomeVariant = $this->processVariants();

        if ($isChangedSomeVariant) {
            $this->product->recalculateOnlineDataByVariants();

            $isChangedProduct = true;
        }

        return new ChangeResult(
            $this->product,
            $isChangedProduct,
            $isChangedSomeVariant,
            array_values($this->instructionsData),
            array_values($this->logs),
        );
    }

    private function processProduct(): bool
    {
        $isChangedProduct = false;

        if ($this->processStatus()) {
            $isChangedProduct = true;
        }

        if ($this->isNeedChangeIncompleteReason()) {
            $this->changeIncompleteReason();
            $isChangedProduct = true;
        }

        return $isChangedProduct;
    }

    private function processVariants(): bool
    {
        $isChanged = false;
        foreach ($this->channelProduct->getVariantSkusCollection()->getAll() as $channelVariant) {
            $existVariant = $this->product->findVariantBySkuId($channelVariant->getSkuId());

            if ($existVariant === null) {
                if ($this->product->isStatusListed()) {
                    $this->addInstructionData(
                        \M2E\Temu\Model\Product::INSTRUCTION_TYPE_VARIANT_SKU_REMOVED,
                        80,
                    );
                }

                continue;
            }

            if ($this->isNeedUpdateVariantQty($existVariant, $channelVariant)) {
                $this->addInstructionData(
                    Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
                    80,
                );

                if ($this->product->isSimple()) {
                    $message = (string)__(
                        'Item QTY was changed from %from to %to.',
                        [
                            'from' => $existVariant->getOnlineQty(),
                            'to' => $channelVariant->getQty(),
                        ],
                    );
                } else {
                    $message = (string)__(
                        'SKU ID %skuId: Item QTY was changed from %from to %to.',
                        [
                            'skuId' => $existVariant->getSkuId(),
                            'from' => $existVariant->getOnlineQty(),
                            'to' => $channelVariant->getQty(),
                        ],
                    );
                }

                $this->addLog(
                    new \M2E\Temu\Model\Listing\Log\Record(
                        $message,
                        \M2E\Temu\Model\Log\AbstractModel::TYPE_SUCCESS,
                    ),
                );

                $existVariant->setOnlineQty($channelVariant->getQty());

                $isChanged = true;
            }

            if ($this->isNeedUpdateVariantPrice($existVariant, $channelVariant)) {
                $this->addInstructionData(
                    Product::INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED,
                    60,
                );

                if ($this->product->isSimple()) {
                    $message = (string)__(
                        'Item Price was changed from %from to %to.',
                        [
                            'from' => $existVariant->getOnlineCurrentPrice(),
                            'to' => $channelVariant->getPrice(),
                        ]
                    );
                } else {
                    $message = (string)__(
                        'SKU %sku: Item Price was changed from %from to %to.',
                        [
                            'sku' => $existVariant->getSku(),
                            'from' => $existVariant->getOnlineCurrentPrice(),
                            'to' => $channelVariant->getPrice(),
                        ],
                    );
                }

                $this->addLog(
                    new \M2E\Temu\Model\Listing\Log\Record(
                        $message,
                        \M2E\Temu\Model\Log\AbstractModel::TYPE_SUCCESS,
                    ),
                );

                $existVariant->setOnlinePrice($channelVariant->getPrice());

                $isChanged = true;
            }

            if ($this->isNeedForceChangeStatusByProduct()) {
                // We do not create instructions and do not write logs as this case depends on the product change
                if ($this->product->isStatusNotListed()) {
                    $existVariant->changeStatusToNoListed();
                } elseif ($this->product->isStatusBlocked()) {
                    $existVariant->changeStatusToInactive();
                } elseif ($this->product->isStatusInactive()) {
                    $existVariant->changeStatusToInactive();
                }

                $isChanged = true;
            } elseif ($this->isNeedChangeStatus($existVariant->getStatus(), $channelVariant->getStatus())) {
                $this->addInstructionData(
                    Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
                    80,
                );

                $this->addLog(
                    new \M2E\Temu\Model\Listing\Log\Record(
                        (string)__(
                            'SKU %sku: Item Status was changed from %from to %to.',
                            [
                                'sku' => $existVariant->getSku(),
                                'from' => Product::getStatusTitle($existVariant->getStatus()),
                                'to' => Product::getStatusTitle($channelVariant->getStatus()),
                            ],
                        ),
                        \M2E\Temu\Model\Log\AbstractModel::TYPE_SUCCESS,
                    ),
                );

                $existVariant->setStatus($channelVariant->getStatus());

                $isChanged = true;
            }
        }

        return $isChanged;
    }

    // ----------------------------------------

    # region status
    private function processStatus(): bool
    {
        if (!$this->isNeedChangeStatus($this->product->getStatus(), $this->channelProduct->getStatus())) {
            return false;
        }

        $this->addInstructionData(
            Product::INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED,
            80,
        );

        $calculatedStatus = $this->calculateStatusByChannel->calculate(
            $this->product,
            $this->channelProduct,
        );
        if ($calculatedStatus === null) {
            throw new \M2E\Temu\Model\Exception\Logic(
                'Unable calculate status of channel product.',
                [
                    'product' => $this->product->getId(),
                    'extension_status' => $this->product->getStatus(),
                    'channel_status' => $this->channelProduct->getStatus(),
                ],
            );
        }

        $this->processNewStatus($calculatedStatus);

        $this->addLog($calculatedStatus->getMessageAboutChange());

        return true;
    }

    private function isNeedForceChangeStatusByProduct(): bool
    {
        return !$this->product->isStatusListed();
    }

    private function isNeedChangeStatus(int $productStatus, int $channelStatus): bool
    {
        return $productStatus !== $channelStatus;
    }

    private function processNewStatus(
        \M2E\Temu\Model\Product\CalculateStatusByChannel\Result $calculatedStatus
    ): void {
        switch ($calculatedStatus->getStatus()) {
            case \M2E\Temu\Model\Product::STATUS_NOT_LISTED:
                $this->product->setStatusNotListed($calculatedStatus->getStatusChanger());
                break;

            default:
                $this->product->setStatus($calculatedStatus->getStatus(), $calculatedStatus->getStatusChanger());
        }
    }

    private function isNeedUpdateVariantQty(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Channel\Product\VariantSku $channelVariantSku
    ): bool {
        if (!$this->product->isStatusListed()) {
            return false;
        }

        if ($variant->getOnlineQty() === $channelVariantSku->getQty()) {
            return false;
        }

        return !$this->isNeedSkipQtyChange($variant->getOnlineQty(), $channelVariantSku->getQty());
    }

    private function isNeedSkipQtyChange(int $currentQty, int $channelQty): bool
    {
        if ($channelQty > $currentQty) {
            return false;
        }

        return $currentQty < 5;
    }

    private function isNeedUpdateVariantPrice(
        \M2E\Temu\Model\Product\VariantSku $variant,
        \M2E\Temu\Model\Channel\Product\VariantSku $channelVariant
    ): bool {
        if (!$this->product->isStatusListed()) {
            return false;
        }

        if ($channelVariant->getPrice() <= 0) {
            return false;
        }

        return $variant->getOnlinePrice() !== $channelVariant->getPrice();
    }

    # endregion

    private function isNeedChangeIncompleteReason(): bool
    {
        return (string)$this->channelProduct->getIncompleteReason() !== $this->product->getIncompleteReason();
    }

    private function changeIncompleteReason(): void
    {
        $this->product->setIncompleteReason($this->channelProduct->getIncompleteReason());
    }

    // ----------------------------------------

    private function addInstructionData(string $type, int $priority): void
    {
        $this->instructionsData[$type] = [
            'listing_product_id' => $this->product->getId(),
            'type' => $type,
            'priority' => $priority,
            'initiator' => 'channel_changes_synchronization',
        ];
    }

    private function addLog(\M2E\Temu\Model\Listing\Log\Record $record): void
    {
        $this->logs[$record->getMessage()] = $record;
    }
}
