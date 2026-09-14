<?php

declare(strict_types=1);

namespace M2E\Temu\Controller\Adminhtml\Account;

class Save extends \M2E\Temu\Controller\Adminhtml\AbstractAccount
{
    private \M2E\Temu\Helper\Module\Exception $helperException;
    private \M2E\Core\Helper\Url $urlHelper;
    private \M2E\Temu\Model\Account\Update $accountUpdate;
    private \M2E\Temu\Model\Account\Repository $accountRepository;

    public function __construct(
        \M2E\Temu\Model\Account\Update $accountUpdate,
        \M2E\Temu\Model\Account\Repository $accountRepository,
        \M2E\Temu\Helper\Module\Exception $helperException,
        \M2E\Core\Helper\Url $urlHelper
    ) {
        parent::__construct();

        $this->helperException = $helperException;
        $this->urlHelper = $urlHelper;
        $this->accountUpdate = $accountUpdate;
        $this->accountRepository = $accountRepository;
    }

    public function execute()
    {
        $post = $this->getRequest()->getPost();
        $shippingProviderMapping = $post['shipping_provider_mapping'] ?? [];

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = (int)$this->getRequest()->getParam('id');
        $account = $this->accountRepository->get($id);

        $data = $post->toArray();
        $unmanagedRelatedStore = $data['related_store_id'] ?? null;

        $unmanagedListingSettings = $account
            ->getUnmanagedListingSettings()
            ->createWithSync((bool)(int)$data['other_listings_synchronization'])
            ->createWithMapping((bool)(int)$data['other_listings_mapping_mode'])
            ->createWithMappingSettings(
                $data['other_listings_mapping']['sku'],
                $data['other_listings_mapping']['title'],
            )
            ->createWithRelatedStoreId((int)$unmanagedRelatedStore);

        $orderSettings = $account
            ->getOrdersSettings()
            ->createWith($data['magento_orders_settings']);

        $invoicesAndShipmentSettings = $account
            ->getInvoiceAndShipmentSettings()
            ->createWithMagentoShipment((bool)(int)$data['create_magento_shipment'])
            ->createWithMagentoInvoice((bool)(int)$data['create_magento_invoice'])
            ->createWithMapShippingProviderByCustomCarrierTitle((bool)(int)$data['map_shipping_provider_by_custom_carrier_title'])
        ;

        try {
            $this->accountUpdate->updateSettings(
                $account,
                $data['title'],
                $unmanagedListingSettings,
                $orderSettings,
                $invoicesAndShipmentSettings,
                $shippingProviderMapping,
            );
        } catch (\Throwable $exception) {
            $this->helperException->process($exception);

            $message = __(
                'We were unable to save your account settings because of an error (%error_message). ' .
                'Please review your information and try again',
                ['error_message' => $exception->getMessage()],
            );

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $message,
                ]);

                return $this->getResult();
            }

            $this->messageManager->addError($message);

            return $this->_redirect('*/account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess(__('Account was saved'));

        return $this->_redirect(
            $this->urlHelper->getBackUrl(
                'list',
                [],
                [
                    'edit' => [
                        'id' => $account->getId(),
                        '_current' => true,
                    ],
                ],
            ),
        );
    }
}
