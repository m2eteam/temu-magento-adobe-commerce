<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Connector\Shipping;

class GetTemplatesCommand implements \M2E\Core\Model\Connector\CommandInterface
{
    private string $accountHash;

    public function __construct(string $accountHash)
    {
        $this->accountHash = $accountHash;
    }

    public function getCommand(): array
    {
        return ['shippingTemplate', 'get', 'entities'];
    }

    public function getRequestData(): array
    {
        return [
            'account' => $this->accountHash,
        ];
    }

    public function parseResponse(
        \M2E\Core\Model\Connector\Response $response
    ): \M2E\Temu\Model\Channel\Shipping\Template\Collection {
        if ($response->getMessageCollection()->hasErrors()) {
            throw new \M2E\Temu\Model\Exception(
                'Unable retrieve shipping templates.',
                [
                    'errors' => array_map(
                        static fn(\M2E\Core\Model\Connector\Response\Message $message) => $message->asArray(),
                        $response->getMessageCollection()->getErrors(),
                    ),
                ]
            );
        }

        $collection = new \M2E\Temu\Model\Channel\Shipping\Template\Collection();

        foreach ($response->getResponseData()['templates'] ?? [] as $templateData) {
            $collection->add(
                new \M2E\Temu\Model\Channel\Shipping\Template(
                    $templateData['id'],
                    $templateData['title']
                )
            );
        }

        return $collection;
    }
}
