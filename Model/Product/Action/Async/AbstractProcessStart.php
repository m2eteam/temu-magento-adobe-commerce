<?php

declare(strict_types=1);

namespace M2E\Temu\Model\Product\Action\Async;

use M2E\Temu\Model\Product\Action\Async;
use M2E\Temu\Model\Product\Action\Type;
use M2E\Temu\Model\Product\Action\ActionLoggerTrait;

abstract class AbstractProcessStart
{
    use ActionLoggerTrait;

    private \M2E\Temu\Model\Product\Action\TagManager $tagManager;
    private \M2E\Temu\Model\Product\LockManager $lockManager;
    private \M2E\Temu\Model\Product $listingProduct;
    private \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator;
    private \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings;
    private \M2E\Temu\Model\Processing\Runner $processingRunner;
    private Async\Processing\InitiatorFactory $processingInitiatorFactory;
    private array $params;
    private int $statusChanger;

    public function __construct(
        \M2E\Temu\Model\Product\Action\TagManager $tagManager
    ) {
        $this->tagManager = $tagManager;
    }

    public function initialize(
        \M2E\Temu\Model\Product\Action\Logger $actionLogger,
        \M2E\Temu\Model\Product\LockManager $lockManager,
        \M2E\Temu\Model\Product $listingProduct,
        \M2E\Temu\Model\Product\Action\Configurator $actionConfigurator,
        \M2E\Temu\Model\Product\Action\VariantSettings $variantSettings,
        \M2E\Temu\Model\Processing\Runner $processingRunner,
        Async\Processing\InitiatorFactory $processingInitiatorFactory,
        \M2E\Temu\Model\Product\Action\LogBuffer $logBuffer,
        array $params,
        int $statusChanger
    ): void {
        $this->actionLogger = $actionLogger;
        $this->lockManager = $lockManager;
        $this->listingProduct = $listingProduct;
        $this->actionConfigurator = $actionConfigurator;
        $this->variantSettings = $variantSettings;
        $this->processingRunner = $processingRunner;
        $this->processingInitiatorFactory = $processingInitiatorFactory;
        $this->logBuffer = $logBuffer;
        $this->params = $params;
        $this->statusChanger = $statusChanger;
    }

    /**
     * @return \M2E\Core\Helper\Data::STATUS_SUCCESS | \M2E\Core\Helper\Data::STATUS_ERROR
     */
    public function process(): int
    {
        if ($this->lockManager->isLocked($this->listingProduct)) {
            $this->actionLogger->logListingProductMessage(
                $this->listingProduct,
                \M2E\Core\Model\Response\Message::createError(
                    'Another Action is being processed. Try again when the Action is completed.',
                ),
            );

            return \M2E\Core\Helper\Data::STATUS_ERROR;
        }

        $this->lockManager->lock($this->listingProduct, $this->getActionNick());

        if (!$this->validateListingProduct()) {
            $this->flushActionLogs();
            $this->lockManager->unlock($this->listingProduct);

            return \M2E\Core\Helper\Data::STATUS_ERROR;
        }

        try {
            // order is important
            $command = $this->getCommand();
            $processParams = $this->getProcessingParams($this->getRequestMetadata());
            $initiator = $this->processingInitiatorFactory->create($command, $processParams);

            $this->beforeProcessingRun();

            $this->processingRunner->run($initiator);
        } catch (\Throwable $e) {
            $this->actionLogger->logListingProductMessage(
                $this->listingProduct,
                \M2E\Core\Model\Response\Message::createError($e->getMessage())
            );
            $this->lockManager->unlock($this->listingProduct);

            return \M2E\Core\Helper\Data::STATUS_ERROR;
        }

        return \M2E\Core\Helper\Data::STATUS_SUCCESS;
    }

    private function validateListingProduct(): bool
    {
        $validationResult = $this->getActionValidator()->validate(
            $this->getListingProduct(),
            $this->getActionConfigurator(),
            $this->getVariantSettings()
        );

        foreach ($this->getActionValidator()->getMessages() as $message) {
            $this->addActionLogMessage(
                \M2E\Core\Model\Response\Message::create(
                    $message->getText(),
                    $message->getType()
                ),
            );
        }

        if ($validationResult) {
            return true;
        }

        $this->tagManager->addErrorTags($this->listingProduct, $this->getActionValidator()->getMessages());

        return false;
    }

    abstract protected function getActionValidator(): Type\AbstractValidator;

    abstract protected function getCommand(): \M2E\Core\Model\Connector\CommandProcessingInterface;

    private function getProcessingParams(
        array $requestMetadata
    ): \M2E\Temu\Model\Product\Action\Async\Processing\Params {
        $actionLogger = $this->getActionLogger();

        return new \M2E\Temu\Model\Product\Action\Async\Processing\Params(
            $this->getListingProduct()->getId(),
            $actionLogger->getActionId(),
            $actionLogger->getAction(),
            $actionLogger->getInitiator(),
            $this->getActionNick(),
            $this->getParams(),
            $requestMetadata,
            $this->getActionConfigurator()->getSerializedData(),
            $this->getVariantSettings()->toArray(),
            $this->getStatusChanger(),
            $this->logBuffer->getWarningMessages()
        );
    }

    abstract protected function getActionNick(): string;

    abstract protected function getRequestMetadata(): array;

    protected function getParams(): array
    {
        return $this->params;
    }

    protected function getListingProduct(): \M2E\Temu\Model\Product
    {
        return $this->listingProduct;
    }

    protected function getAccount(): \M2E\Temu\Model\Account
    {
        return $this->listingProduct->getAccount();
    }

    protected function getActionConfigurator(): \M2E\Temu\Model\Product\Action\Configurator
    {
        return $this->actionConfigurator;
    }

    protected function getVariantSettings(): \M2E\Temu\Model\Product\Action\VariantSettings
    {
        return $this->variantSettings;
    }

    public function setStatusChanger(int $statusChanger): void
    {
        $this->statusChanger = $statusChanger;
    }

    protected function getStatusChanger(): int
    {
        return $this->statusChanger;
    }

    // ----------------------------------------

    protected function beforeProcessingRun(): void
    {
        // do something
    }
}
