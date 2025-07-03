<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Channel\Shipping;

class TemplateRetriever
{
    private \M2E\Temu\Model\Connector\Client\Single $serverClient;

    public function __construct(
        \M2E\Temu\Model\Connector\Client\Single $serverClient
    ) {
        $this->serverClient = $serverClient;
    }

    /**
     * @param \M2E\Temu\Model\Account $account
     *
     * @throws \M2E\Temu\Model\Exception
     */
    public function retrieve(
        \M2E\Temu\Model\Account $account
    ): Template\Collection {
        $command = new \M2E\Temu\Model\Channel\Connector\Shipping\GetTemplatesCommand(
            $account->getServerHash()
        );

        /** @var Template\Collection */
        return $this->serverClient->process($command);
    }
}
