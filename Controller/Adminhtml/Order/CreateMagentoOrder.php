<?php

namespace M2E\Temu\Controller\Adminhtml\Order;

class CreateMagentoOrder extends AbstractOrder
{
    private string $message;
    private \M2E\Temu\Model\Order\MagentoProcessor $magentoCreate;
    private \M2E\Temu\Model\Order\Repository $orderRepository;

    public function __construct(
        \M2E\Temu\Model\Order\Repository $orderRepository,
        \M2E\Temu\Model\Order\MagentoProcessor $magentoCreate
    ) {
        parent::__construct();
        $this->magentoCreate = $magentoCreate;
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {
        $orderIds = $this->getRequestIds();
        $warnings = 0;
        $errors = 0;

        foreach ($orderIds as $orderId) {
            $order = $this->orderRepository->find((int)$orderId);
            if ($order === null) {
                continue;
            }

            if ($order->getMagentoOrderId() !== null) {
                $this->message = __(
                    '%count Magento order(s) are already created for the selected %channel_title order(s).',
                    [
                        'count' => $warnings,
                        'channel_title' => \M2E\Temu\Helper\Module::getChannelTitle(),
                    ]
                );
                $warnings++;
                continue;
            }

            // Create magento order
            // ---------------------------------------

            if ($order->canCreateMagentoOrder()) {
                try {
                    $this->magentoCreate->process($order, \M2E\Core\Helper\Data::INITIATOR_USER, false, false);
                } catch (\Throwable $e) {
                    $errors++;
                }
            } else {
                $warnings++;
                $this->message = __('Magento order creation rules are not met.');
            }
        }

        if (!$errors && !$warnings) {
            $this->messageManager->addSuccess(__('Magento Order(s) were created.'));
        }

        if ($errors) {
            $this->messageManager->addError(
                __(
                    '%count Magento order(s) were not created. Please <a target="_blank" href="%url">view Log</a>
                for the details.',
                    ['count' => $errors, 'url' => $this->getUrl('*/log_order')]
                )
            );
        }

        if ($warnings) {
            $this->messageManager->addWarning($this->message);
        }

        if (count($orderIds) == 1) {
            return $this->_redirect('*/*/view', ['id' => $orderIds[0]]);
        } else {
            return $this->_redirect($this->redirect->getRefererUrl());
        }
    }
}
