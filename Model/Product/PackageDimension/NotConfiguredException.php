<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\PackageDimension;

class NotConfiguredException extends PackageDimensionException
{
    public function __construct(string $type)
    {
        $messageData = $this->getMessageByType($type);
        parent::__construct($messageData['text'], [], $messageData['code']);
    }

    private function getMessageByType(string $type): array
    {
        switch ($type) {
            case \M2E\Temu\Model\Settings::DIMENSION_TYPE_WEIGHT:
                return [
                    'text' => (string)__('Package Weight not configured'),
                    'code' => self::CODE_WEIGHT_NOT_CONFIGURED
                ];
            case \M2E\Temu\Model\Settings::DIMENSION_TYPE_LENGTH:
                return [
                    'text' => (string)__('Package Length not configured'),
                    'code' => self::CODE_LENGTH_NOT_CONFIGURED
                ];
            case \M2E\Temu\Model\Settings::DIMENSION_TYPE_WIDTH:
                return [
                    'text' => (string)__('Package Width not configured'),
                    'code' => self::CODE_WIDTH_NOT_CONFIGURED
                ];
            case \M2E\Temu\Model\Settings::DIMENSION_TYPE_HEIGHT:
                return [
                    'text' => (string)__('Package Height not configured'),
                    'code' => self::CODE_HEIGHT_NOT_CONFIGURED
                ];
            default:
                return [
                    'text' => (string)__('N/A'),
                    'code' => 0
                ];
        }
    }
}
