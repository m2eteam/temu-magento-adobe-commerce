<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Wizard\InstallationTemu;

class AfterToken extends Installation
{
    private \M2E\Temu\Helper\Module\Exception $exceptionHelper;
    private \M2E\Temu\Model\Account\Create $accountCreate;
    private \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage;

    public function __construct(
        \M2E\Temu\Model\Account\AuthCodeSessionStorage $authCodeSessionStorage,
        \M2E\Temu\Model\Account\Create $accountCreate,
        \M2E\Temu\Helper\Module\Exception $exceptionHelper,
        \M2E\Core\Helper\Magento $magentoHelper,
        \M2E\Temu\Helper\Module\Wizard $wizardHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \M2E\Core\Model\LicenseService $licenseService
    ) {
        parent::__construct(
            $magentoHelper,
            $wizardHelper,
            $nameBuilder,
            $licenseService,
        );

        $this->authCodeSessionStorage = $authCodeSessionStorage;
        $this->exceptionHelper = $exceptionHelper;
        $this->accountCreate = $accountCreate;
    }

    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $authCode = $this->getRequest()->getParam('code');
        $region = $this->getRequest()->getParam('region');
        /** @var string|null $referrer */
        $referrer = $this->getRequest()->getParam('referrer');
        /** @var string|null $callbackHost */
        $callbackHost = $this->getRequest()->getParam('callback_host');

        if (!$authCode) {
            $this->messageManager->addError(__('Auth Code is not defined'));

            return $this->_redirect('*/*/installation');
        }

        try {
            if ($this->authCodeSessionStorage->getAccount($authCode) !== null) {
                return $this->_redirect('*/*/installation');
            }

            $this->accountCreate->create($authCode, $region, $referrer, $callbackHost);
            $this->setStep($this->getNextStep());
        } catch (\Throwable $throwable) {
            $this->exceptionHelper->process($throwable);
            $this->messageManager->addError(__('Account Add Entity failed.'));
        }

        return $this->_redirect('*/*/installation');
    }
}
