<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Connector\Attribute;

class GetCommand implements \M2E\Core\Model\Connector\CommandInterface
{
    private string $region;
    private int $categoryId;
    private string $account;

    public function __construct(string $region, int $categoryId, string $account)
    {
        $this->region = $region;
        $this->categoryId = $categoryId;
        $this->account = $account;
    }

    public function getCommand(): array
    {
        return ['category', 'get', 'attributes'];
    }

    public function getRequestData(): array
    {
        return [
            'region' => $this->region,
            'category_id' => $this->categoryId,
            'account' => $this->account,
        ];
    }

    public function parseResponse(\M2E\Core\Model\Connector\Response $response): Get\Response
    {
        $this->processError($response);
        $responseData = $response->getResponseData();

        $attributes = [];
        foreach ($responseData['attributes'] as $attributeData) {
            $rules = [
                'min_value' => $attributeData['rules']['min_value'],
                'max_value' => $attributeData['rules']['min_value'],
            ];
            $attribute = new \M2E\Temu\Model\Channel\Attribute\Item(
                (string)$attributeData['id'],
                $attributeData['name'],
                $this->getAttributeType($attributeData['is_sale']),
                $attributeData['is_sale'],
                $attributeData['is_required'],
                $attributeData['is_customized'],
                $attributeData['is_multiple_selected'],
                $attributeData['type'],
                $rules,
                $attributeData['pid'],
                $attributeData['ref_pid'],
                $attributeData['template_pid'],
                $attributeData['parent_spec_id'],
                $attributeData['parent_template_pid'],
                $attributeData['multiple_selected_max_count']
            );

            foreach ($attributeData['options'] as $value) {
                $attribute->addValue(
                    (string)$value['id'],
                    $value['value'],
                    $value['spec_id'],
                    $value['group_id'],
                    $value['children_relation']
                );
            }

            $attributes[] = $attribute;
        }

        return new \M2E\Temu\Model\Channel\Connector\Attribute\Get\Response(
            $attributes,
            [
                /*'size_chart' => [
                    'is_supported' => $responseData['rules']['size_chart']['is_supported'],
                    'is_required' => $responseData['rules']['size_chart']['is_required'],
                ]*/
            ]
        );
    }

    private function processError(\M2E\Core\Model\Connector\Response $response): void
    {
        if (!$response->isResultError()) {
            return;
        }

        foreach ($response->getMessageCollection()->getMessages() as $message) {
            if ($message->isError()) {
                throw new \M2E\Temu\Model\Exception\CategoryInvalid(
                    $message->getText(),
                    [],
                    (int)$message->getCode()
                );
            }
        }
    }

    private function getAttributeType(bool $isSale): string
    {
        return $isSale ?
            \M2E\Temu\Model\Channel\Attribute\Item::SALES_TYPE
            : \M2E\Temu\Model\Channel\Attribute\Item::PRODUCT_TYPE;
    }
}
