<?php

declare(strict_types=1);

namespace M2E\Temu\Model\ControlPanel\Module\Integration;

use M2E\Temu\Model\Product\Action;

class RequestData
{
    private const PARAM_PRODUCT_MAGENTO_SKU = 'listing_product_magento_sku';
    private const PARAM_CALCULATOR_ACTION = 'calculator_action';
    private const PARAM_PRINT = 'print';

    private \M2E\Temu\Model\Product\Repository $productRepository;
    private \M2E\Temu\Model\Product\Action\Type\Revise\RequestFactory $reviseRequestFactory;
    private \M2E\Temu\Model\Product\Action\Type\Relist\RequestFactory $relistRequestFactory;
    private \M2E\Temu\Model\Product\Action\Type\Stop\RequestFactory $stopRequestFactory;
    private \M2E\Temu\Model\Product\ActionCalculator $actionCalculator;
    private \Magento\Framework\Data\Form\FormKey $formKey;
    private \Magento\Framework\UrlInterface $url;
    private \Magento\Framework\Escaper $escaper;
    private \M2E\Temu\Model\Product\VariantSku\ActionCalculator $variantActionCalculator;
    private \M2E\Temu\Model\Product\Action\LogBufferFactory $logBufferFactory;
    private \M2E\Temu\Model\Product\Action\Type\ListAction\RequestFactory $listRequestFactory;

    public function __construct(
        \M2E\Temu\Model\Product\Repository $productRepository,
        \M2E\Temu\Model\Product\Action\Type\ListAction\RequestFactory $listRequestFactory,
        \M2E\Temu\Model\Product\Action\Type\Revise\RequestFactory $reviseRequestFactory,
        \M2E\Temu\Model\Product\Action\Type\Relist\RequestFactory $relistRequestFactory,
        \M2E\Temu\Model\Product\Action\Type\Stop\RequestFactory $stopRequestFactory,
        \M2E\Temu\Model\Product\ActionCalculator $actionCalculator,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\UrlInterface $url,
        \M2E\Temu\Model\Product\VariantSku\ActionCalculator $variantActionCalculator,
        \M2E\Temu\Model\Product\Action\LogBufferFactory $logBufferFactory,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->productRepository = $productRepository;
        $this->reviseRequestFactory = $reviseRequestFactory;
        $this->relistRequestFactory = $relistRequestFactory;
        $this->stopRequestFactory = $stopRequestFactory;
        $this->actionCalculator = $actionCalculator;
        $this->formKey = $formKey;
        $this->url = $url;
        $this->variantActionCalculator = $variantActionCalculator;
        $this->logBufferFactory = $logBufferFactory;
        $this->escaper = $escaper;
        $this->listRequestFactory = $listRequestFactory;
    }

    public function execute(\Magento\Framework\App\RequestInterface $request): string
    {
        $productMagentoSku = $request->getParam(self::PARAM_PRODUCT_MAGENTO_SKU, '');
        $calculatorAction = $request->getParam(self::PARAM_CALCULATOR_ACTION, 'auto');

        $body = $this->printFormForCalculateAction($productMagentoSku, $calculatorAction);

        if ($request->getParam(self::PARAM_PRINT)) {
            try {
                $listingProducts = $this->productRepository->findProductsByMagentoSku($productMagentoSku);

                foreach ($listingProducts as $listingProduct) {
                    if ($calculatorAction === 'list') {
                        $variantSettings = $this->calculateVariants($listingProduct, false);
                        $action = \M2E\Temu\Model\Product\Action::createList(
                            $listingProduct,
                            (new Action\Configurator())->enableAll(),
                            $variantSettings
                        );
                    } elseif ($calculatorAction === 'revise') {
                        $variantSettings = $this->calculateVariants($listingProduct, false);
                        $action = \M2E\Temu\Model\Product\Action::createRevise(
                            $listingProduct,
                            (new Action\Configurator())->enableAll(),
                            $variantSettings
                        );
                    } elseif ($calculatorAction === 'relist') {
                        $variantSettings = $this->calculateVariants($listingProduct, false);
                        $action = \M2E\Temu\Model\Product\Action::createRelist(
                            $listingProduct,
                            (new Action\Configurator())->enableAll(),
                            $variantSettings
                        );
                    } elseif ($calculatorAction === 'stop') {
                        $action = \M2E\Temu\Model\Product\Action::createStop(
                            $listingProduct,
                        );
                    } else {
                        $action = $this->actionCalculator->calculate(
                            $listingProduct,
                            true,
                            \M2E\Temu\Model\Product::STATUS_CHANGER_USER,
                        );
                    }

                    $body .= '<div>' . $this->printProductInfo($listingProduct, $action) . '</div>';
                }
            } catch (\Throwable $exception) {
                $errorMessage = sprintf(
                    '<p style="margin-bottom: 20px">Error "%s" in %s:%s</p>',
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                );

                $body .= sprintf(
                    '<div style="margin: 20px 0">%s<pre>%s</pre></div>',
                    $errorMessage,
                    $exception->getTraceAsString()
                );
            }
        }

        return $this->renderHtml($body);
    }

    private function calculateVariants(
        \M2E\Temu\Model\Product $product,
        bool $needForceRevise
    ): \M2E\Temu\Model\Product\Action\VariantSettings {
        $variantSettingsBuilder = new \M2E\Temu\Model\Product\Action\VariantSettingsBuilder(
            $needForceRevise
        );
        foreach ($product->getVariants() as $variant) {
            $action = $this->variantActionCalculator->process($variant, $product);

            $variantSettingsBuilder->add($variant->getId(), $action, $variant->getStatus());
        }

        return $variantSettingsBuilder->build();
    }

    private function printFormForCalculateAction(
        string $productMagentoSku = '',
        string $selectedAction = 'auto'
    ): string {
        $formKey = $this->formKey->getFormKey();
        $actionUrl = $this->url->getUrl('*/*/*', ['action' => 'getRequestData']);

        $actionsList = [
            ['value' => 'auto', 'label' => 'Auto'],
            ['value' => 'list', 'label' => 'List'],
            ['value' => 'revise', 'label' => 'Revise'],
            ['value' => 'relist', 'label' => 'Relist'],
            ['value' => 'stop', 'label' => 'Stop'],
        ];

        $actionsOptions = '';
        foreach ($actionsList as $action) {
            $actionsOptions .= sprintf(
                '<option value="%s" %s>%s</option>',
                $action['value'],
                $selectedAction === $action['value'] ? 'selected' : '',
                $action['label']
            );
        }

        return <<<HTML
<div class="sticky-form-wrapper">
    <form method="get" enctype="multipart/form-data" action="$actionUrl">
        <input name="form_key" value="$formKey" type="hidden" />
        <input name="print" value="1" type="hidden" />

        <div class="form-row">
            <label for="product_id">Magento Product Sku:</label>
            <input id="product_id" name="listing_product_magento_sku" required value="$productMagentoSku">
        </div>
        <div class="form-row">
            <label for="calculator_action">Action:</label>
            <select id="calculator_action" name="calculator_action">$actionsOptions</select>
        </div>
        <div class="form-row">
            <button class="run" type="submit">Run</button>
        </div>
    </form>
</div>
HTML;
    }

    private function printProductInfo(
        \M2E\Temu\Model\Product $product,
        \M2E\Temu\Model\Product\Action $action
    ): ?string {
        if ($action->isActionList()) {
            $calculateAction = 'List';
            $request = $this->listRequestFactory->create();
        } elseif ($action->isActionRevise()) {
            $calculateAction = sprintf(
                'Revise (Reason (%s))',
                implode(' | ', $action->getConfigurator()->getAllowedDataTypes()),
            );
            $request = $this->reviseRequestFactory->create();
        } elseif ($action->isActionStop()) {
            $calculateAction = 'Stop';
            $request = $this->stopRequestFactory->create();
        } elseif ($action->isActionRelist()) {
            $calculateAction = 'Relist';
            $request = $this->relistRequestFactory->create();
        } else {
            $request = null;
            $calculateAction = 'Nothing action allowed.';
        }

        $requestMessagesHtml = 'Nothing action allowed.';
        $requestDataHtml = 'Nothing action allowed.';
        $requestMetaDataHtml = 'Nothing action allowed.';

        if ($request !== null) {
            $logBuffer = $this->logBufferFactory->create();
            $requestData = $request->build(
                $product,
                $action->getConfigurator(),
                $action->getVariantSettings(),
                $logBuffer,
                []
            )->toArray();

            $requestMessagesHtml = $this->printRequestMessages($logBuffer);
            $requestDataHtml = $this->printRequestData($requestData);
            $requestMetaDataHtml = $this->printRequestMetaData($request->getMetadata());
        }

        $currentStatusTitle = \M2E\Temu\Model\Product::getStatusTitle($product->getStatus());
        $productSku = $product->getMagentoProduct()->getSku();
        $listingTitle = $product->getListing()->getTitle();

        return <<<HTML
<table>
    <tr>
        <td>Listing</td>
        <td>$listingTitle</td>
    </tr>
    <tr>
        <td>Product (SKU)</td>
        <td>$productSku</td>
    </tr>
    <tr>
        <td>Current Product Status</td>
        <td>$currentStatusTitle</td>
    </tr>
    <tr>
        <td>Calculate Action</td>
        <td>$calculateAction</td>
    </tr>
    <tr>
        <td>Request Messages</td>
        <td>$requestMessagesHtml</td>
    </tr>
    <tr>
        <td>Request Data</td>
        <td>$requestDataHtml</td>
    </tr>
    <tr>
        <td>Request MetaData</td>
        <td>$requestMetaDataHtml</td>
    </tr>
</table>
HTML;
    }

    private function printRequestData(array $requestData): string
    {
        return sprintf(
            '<pre class="white-space_pre-wrap">%s</pre>',
            $this->escaper->escapeHtml(
                json_encode(
                    $requestData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
            ),
        );
    }

    private function printRequestMetaData(array $metaData): string
    {
        return sprintf(
            '<pre class="white-space_pre-wrap">%s</pre>',
            $this->escaper->escapeHtml(
                json_encode(
                    $metaData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
            ),
        );
    }

    private function printRequestMessages(\M2E\Temu\Model\Product\Action\LogBuffer $logBuffer): string
    {
        $logsArray = [];
        foreach ($logBuffer->getLogs() as $log) {
            $logsArray[] = [
                'type' => $log->getSeverity(),
                'text' => $log->getMessage(),
            ];
        };

        return sprintf(
            '<pre class="white-space_pre-wrap">%s</pre>',
            $this->escaper->escapeHtml(
                json_encode(
                    $logsArray,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
                ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
            ),
        );
    }

    private function renderHtml(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Temu Module Tools | Print Request Data</title>
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    .sticky-form-wrapper {
        background: #d3d3d3;
        position: sticky;
        top: 0;
        width: 100%
    }

    form {
        padding: 10px;
        font-size: 16px;
        position: relative
    }

    .form-row:not(:last-child) {
        margin-bottom: 10px
    }

    .form-row label {
        display: inline-block;
        min-width: 100px
    }

    .form-row input, .form-row select {
        min-width: 200px
    }

    button.run {
        padding: 7px 15px; font-weight: 700
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    td:first-child {
        width: 200px;
    }

    .white-space_pre-wrap {
        white-space: pre-wrap;
    }

    td, th {
      border: 1px solid #dddddd;
      text-align: left;
      padding: 8px;
    }

    tr:nth-child(even) {
      background-color: #f2f2f2;
    }
    </style>
  </head>
  <body>$body</body>
</html>
HTML;
    }
}
