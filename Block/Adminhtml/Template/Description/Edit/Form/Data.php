<?php

declare(strict_types=1);

namespace M2E\Temu\Block\Adminhtml\Template\Description\Edit\Form;

use M2E\Temu\Block\Adminhtml\Magento\Form\AbstractForm;
use M2E\Temu\Model\Policy\Description as DescriptionPolicy;
use M2E\Temu\Model\ResourceModel\Policy\Description as DescriptionResource;

class Data extends AbstractForm
{
    private \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper;

    private \M2E\Temu\Helper\Data\GlobalData $globalDataHelper;

    private array $attributes = [];
    private array $textAttributes = [];
    private array $imgAttributes = [];
    private DescriptionPolicy\BuilderFactory $templateDescriptionBuilderFactory;

    public function __construct(
        \M2E\Temu\Model\Policy\Description\BuilderFactory $templateDescriptionBuilderFactory,
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper,
        \M2E\Temu\Helper\Data\GlobalData $globalDataHelper,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->templateDescriptionBuilderFactory = $templateDescriptionBuilderFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $this->attributes = $this->magentoAttributeHelper->getAll();
        $this->textAttributes = $this->magentoAttributeHelper->filterByInputTypes(
            $this->attributes,
            ['text', 'select']
        );
        $this->imgAttributes = $this->magentoAttributeHelper->filterByInputTypes(
            $this->attributes,
            ['text', 'image', 'media_image', 'gallery', 'multiline', 'textarea', 'select', 'multiselect']
        );

        // ----------------------------------------

        $formData = $this->getFormData();

        $default = $this->getDefault();
        $formData = array_replace_recursive($default, $formData);

        $isCustomDescription = ($formData['description_mode'] == DescriptionPolicy::DESCRIPTION_MODE_CUSTOM);

        $form = $this->_formFactory->create();
        $this->setForm($form);

        $form->addField(
            'description_id',
            'hidden',
            [
                'name' => 'description[id]',
                'value' => (!$this->isCustom() && isset($formData['id'])) ? (int)$formData['id'] : '',
            ]
        );

        $form->addField(
            'description_title',
            'hidden',
            [
                'name' => 'description[title]',
                'value' => $this->getTitle(),
            ]
        );

        $form->addField(
            'description_is_custom_template',
            'hidden',
            [
                'name' => 'description[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0,
            ]
        );

        $form->addField(
            'description_editor_type',
            'hidden',
            [
                'name' => 'description[editor_type]',
                'value' => $formData['editor_type'],
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_template_description_form_data_image',
            [
                'legend' => __('Images'),
                'collapsable' => true,
            ]
        );

        $preparedAttributes = [];
        foreach ($this->imgAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (
                $formData['image_main_mode'] == DescriptionPolicy::IMAGE_MAIN_MODE_ATTRIBUTE
                && $formData['image_main_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => DescriptionPolicy::IMAGE_MAIN_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'image_main',
            self::SELECT,
            [
                'name' => 'description[image_main_mode]',
                'label' => __('Main Image'),
                'values' => [
                    DescriptionPolicy::IMAGE_MAIN_MODE_PRODUCT => __('Product Base Image'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'value' => $formData['image_main_mode'] != DescriptionPolicy::IMAGE_MAIN_MODE_ATTRIBUTE
                    ? $formData['image_main_mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        $fieldset->addField(
            'image_main_attribute',
            'hidden',
            [
                'name' => 'description[image_main_attribute]',
                'value' => $formData['image_main_attribute'],
            ]
        );

        $fieldset->addField(
            'gallery_images_limit',
            'hidden',
            [
                'name' => 'description[gallery_images_limit]',
                'value' => $formData['gallery_images_limit'],
            ]
        );

        $fieldset->addField(
            'gallery_images_attribute',
            'hidden',
            [
                'name' => 'description[gallery_images_attribute]',
                'value' => $formData['gallery_images_attribute'],
            ]
        );

        $preparedImages = [];
        for ($i = 1; $i <= DescriptionPolicy\Source::GALLERY_IMAGES_COUNT_MAX; $i++) {
            $attrs = ['attribute_code' => $i];

            if (
                $i == $formData['gallery_images_limit']
                && $formData['gallery_images_mode'] == DescriptionPolicy::GALLERY_IMAGES_MODE_PRODUCT
            ) {
                $attrs['selected'] = 'selected';
            }

            $preparedImages[] = [
                'value' => DescriptionPolicy::GALLERY_IMAGES_MODE_PRODUCT,
                'label' => $i == 1 ? $i : (__('Up to') . " $i"),
                'attrs' => $attrs,
            ];
        }

        $preparedAttributes = [];
        foreach ($this->imgAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];

            if (
                $formData['gallery_images_mode'] == DescriptionPolicy::GALLERY_IMAGES_MODE_ATTRIBUTE
                && $formData['gallery_images_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }

            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => DescriptionPolicy::GALLERY_IMAGES_MODE_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'gallery_images',
            self::SELECT,
            [
                'container_id' => 'gallery_images_mode_tr',
                'name' => 'description[gallery_images_mode]',
                'label' => __('Gallery Images'),
                'values' => [
                    DescriptionPolicy::GALLERY_IMAGES_MODE_NONE => __('None'),
                    [
                        'label' => __('Product Images'),
                        'value' => $preparedImages,
                    ],
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'tooltip' => __(
                    'Adds small thumbnails that appear under the large Base Image.
                     You can add up to 10 additional photos to each Listing on Temu.
                        <br/><b>Note:</b> Text, Multiple Select or Dropdown type Attribute can be used.
                        The value of Attribute must contain absolute urls.
                        <br/>In Text type Attribute urls must be separated with comma.
                        <br/>e.g. http://mymagentostore.com/images/baseimage1.jpg,
                        http://mymagentostore.com/images/baseimage2.jpg'
                ),
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select,multiselect');

        $fieldset = $form->addFieldset(
            'magento_block_template_description_form_data_description',
            [
                'legend' => __('Description'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'title_mode',
            'select',
            [
                'label' => __('Title'),
                'name' => 'description[title_mode]',
                'values' => [
                    DescriptionPolicy::TITLE_MODE_PRODUCT => __('Product Name'),
                    DescriptionPolicy::TITLE_MODE_CUSTOM => __('Custom Value'),
                ],
                'value' => $formData['title_mode'],
                'tooltip' => __(
                    'This is the Title that Buyers will see on Temu. A good Title ensures better visibility.'
                ),
            ]
        );

        $preparedAttributes = [];
        foreach ($this->attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $button = $this->getLayout()->createBlock(
            \M2E\Temu\Block\Adminhtml\Magento\Button\MagentoAttribute::class
        )
                       ->addData(
                           [
                               'label' => __('Insert'),
                               'destination_id' => 'title_template',
                               'class' => 'primary',
                               'style' => 'display: inline-block;',
                           ]
                       );

        $selectAttrBlock = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'values' => $preparedAttributes,
                    'class' => 'Temu-required-when-visible magento-attribute-custom-input',
                    'create_magento_attribute' => true,
                ],
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select,multiselect,boolean,price,date')
                                                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

        $selectAttrBlock->setId('selectAttr_title_template');
        $selectAttrBlock->setForm($this->_form);

        $fieldset->addField(
            'title_template',
            'text',
            [
                'container_id' => 'custom_title_tr',
                'label' => __('Title Value'),
                'value' => $formData['title_template'],
                'name' => 'description[title_template]',
                'class' => 'input-text-title',
                'required' => true,
                'after_element_html' => $selectAttrBlock->toHtml() . $button->toHtml(),
            ]
        );

        $preparedAttributes = [];
        foreach ($this->attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $button = $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label' => __('Preview'),
                'onclick' => 'TemuTemplateDescriptionObj.openPreviewPopup()',
                'class' => 'action-primary',
                'style' => 'margin-left: 70px;',
            ]
        );

        $tooltipMessage = (string)__(
            'Choose whether to use Magento <strong>Product Description</strong>
            or <strong>Product Short Description</strong> for the Temu Listing Description.'
        );
        $fieldset->addField(
            'description_mode',
            'select',
            [
                'label' => __('Description'),
                'name' => 'description[description_mode]',
                'values' => [
                    DescriptionPolicy::DESCRIPTION_MODE_PRODUCT => __('Product Description'),
                    DescriptionPolicy::DESCRIPTION_MODE_SHORT => __('Product Short Description'),
                    DescriptionPolicy::DESCRIPTION_MODE_CUSTOM => __('Custom Value'),
                ],
                'value' => $this->isEdit() ? $formData['description_mode'] : '-1',
                'class' => 'Temu-validate-description-mode',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml($tooltipMessage) . $button->toHtml(),
            ]
        );

        if ($isCustomDescription) {
            $fieldset->addField(
                'view_edit_custom_description_link',
                'link',
                [
                    'container_id' => 'view_edit_custom_description',
                    'label' => '',
                    'value' => __('View / Edit Custom Description'),
                    'onclick' => 'TemuTemplateDescriptionObj.view_edit_custom_change()',
                    'href' => 'javascript://',
                    'style' => 'text-decoration: underline;',
                ]
            );
        }

        $showHideWYSIWYGButton = '';
        if ($this->wysiwygConfig->isEnabled()) {
            $showHideWYSIWYGButtonBlock = $this
                ->getLayout()
                ->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)
                ->setData(
                    [
                        'id' => 'description_template_show_hide_wysiwyg',
                        'label' => ($formData['editor_type'] == DescriptionPolicy::EDITOR_TYPE_SIMPLE)
                            ? __('Show Editor') : __('Hide Editor'),
                        'class' => 'action-primary hidden',
                    ]
                );

            $showHideWYSIWYGButton = $showHideWYSIWYGButtonBlock->toHtml();
        }

        $openCustomInsertsButton = $this->getLayout()
                                        ->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)
                                        ->setData(
                                            [
                                                'id' => 'custom_inserts_open_popup',
                                                'label' => __('Insert Customs'),
                                                'class' => 'action-primary',
                                            ]
                                        );

        $fieldset->addField(
            'description_template',
            'editor',
            [
                'container_id' => 'description_template_tr',
                'css_class' => 'c-custom_description_tr _required',
                'label' => __('Description Value'),
                'name' => 'description[description_template]',
                'value' => $formData['description_template'],
                'class' => ' admin__control-textarea left Temu-validate-description-template',
                'wysiwyg' => $this->wysiwygConfig->isEnabled(),
                'force_load' => true,
                'config' => $this->wysiwygConfig->getConfig(
                    [
                        'hidden' => true,
                        'enabled' => false,
                        'no_display' => false,
                        'add_variables' => false,
                        'force_load' => true,
                    ]
                ),
                'after_element_html' => <<<HTML
<div id="description_template_buttons">
    {$showHideWYSIWYGButton}
    {$openCustomInsertsButton->toHtml()}
</div>
HTML
                ,
            ]
        );

        $this->addBulletPointFields($fieldset, $formData);

        // ----------------------------------------

        $this->jsUrl->addUrls(
            [
                'policy_description' => $this->getUrl(
                    '*/policy_description/saveWatermarkImage/'
                ),
            ]
        );

        $this->jsUrl->addUrls(
            [
                'policy_description/checkMagentoProductId' =>
                    $this->getUrl(
                        '*/policy_description/checkMagentoProductId/'
                    ),
                'policy_description/getRandomMagentoProductId' =>
                    $this->getUrl(
                        '*/policy_description/getRandomMagentoProductId/'
                    ),
                'policy_description/preview' =>
                    $this->getUrl(
                        '*/policy_description/preview/'
                    ),
            ]
        );

        $descriptionModeCustomValue = \M2E\Temu\Model\Policy\Description::DESCRIPTION_MODE_CUSTOM;
        $bulletPointModeCustomValue = \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_CUSTOM_VALUE;
        $bulletPointModeCustomAttribute = \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_CUSTOM_ATTRIBUTE;
        $maxBulletPointsCount = \M2E\Temu\Model\Policy\Description\BulletPoint::MAX_COUNT;

        $this->js->add(
            <<<JS
    require([
        'Temu/Template/Description',
        'Temu/Plugin/Magento/Attribute/Button'
    ], function(){
        window.TemuTemplateDescriptionObj = new TemuTemplateDescription(
            $descriptionModeCustomValue,
            $bulletPointModeCustomValue,
            $bulletPointModeCustomAttribute,
            $maxBulletPointsCount
        );
        setTimeout(function() {
            TemuTemplateDescriptionObj.initObservers();
        }, 50);

        window.MagentoAttributeButtonObj = new MagentoAttributeButton();
    });
JS
        );

        return parent::_prepareForm();
    }

    protected function _toHtml()
    {
        return parent::_toHtml()
            . $this->getCustomInsertsHtml()
            . $this->getDescriptionPreviewHtml();
    }

    public function isCustom()
    {
        if (isset($this->_data['is_custom'])) {
            return (bool)$this->_data['is_custom'];
        }

        return false;
    }

    public function isEdit(): bool
    {
        $template = $this->globalDataHelper->getValue('temu_template_description');

        if ($template === null || $template->getId() === null) {
            return false;
        }

        return true;
    }

    public function getTitle()
    {
        if ($this->isCustom()) {
            return isset($this->_data['custom_title']) ? $this->_data['custom_title'] : '';
        }

        if (!$this->isEdit()) {
            return '';
        }

        /** @var DescriptionPolicy $template */
        $template = $this->globalDataHelper->getValue('temu_template_description');

        return $template->getTitle();
    }

    public function getFormData(): array
    {
        if (!$this->isEdit()) {
            return [];
        }

        /** @var DescriptionPolicy $template */
        $template = $this->globalDataHelper->getValue('temu_template_description');

        $data = $template->getData();
        $data[DescriptionResource::COLUMN_BULLET_POINTS] = $template->getBulletPoints();

        return $data;
    }

    public function getDefault(): array
    {
        return $this->templateDescriptionBuilderFactory->create()->getDefaultData();
    }

    protected function getCustomInsertsHtml()
    {
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('custom_inserts', ['legend' => __('Attribute')]);

        $preparedAttributes = [];
        foreach ($this->attributes as $attribute) {
            $preparedAttributes[] = [
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $button = $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)->setData(
            [
                'label' => __('Insert'),
                'class' => 'action-primary',
                'onclick' => 'TemuTemplateDescriptionObj.insertProductAttribute()',
                'style' => 'margin-left: 15px;',
            ]
        );

        $fieldset->addField(
            'custom_inserts_product_attribute',
            self::SELECT,
            [
                'label' => __('Magento Product'),
                'class' => 'Temu-custom-attribute-can-be-created',
                'values' => $preparedAttributes,
                'after_element_html' => $button->toHtml(),
                'apply_to_all_attribute_sets' => 0,
            ]
        )->addCustomAttribute('apply_to_all_attribute_sets', 0);

        $TemuAttributes = [
            'title' => __('Title'),
            'fixed_price' => __('Temu Price'),
            'qty' => __('QTY'),
        ];

        $button->setData('onclick', 'TemuTemplateDescriptionObj.insertTemuAttribute()');

        $fieldset->addField(
            'custom_inserts_temu_attribute',
            'select',
            [
                'label' => __('M2E Temu'),
                'values' => $TemuAttributes,
                'after_element_html' => $button->toHtml(),
            ]
        );

        return <<<HTML
<div class="hidden">
    <div id="custom_inserts_popup" class="admin__old">{$form->toHtml()}</div>
</div>
HTML;
    }

    private function getDescriptionPreviewHtml(): string
    {
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('description_preview_fieldset', ['legend' => '']);

        $fieldset->addField(
            'description_preview_help_block',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '
                    If you would like to preview the Description data for the particular Magento Product, please,
                    provide its ID into the <strong>Magento Product ID</strong> input and select
                    a <strong>Magento Store View</strong> the values
                    should be taken from. As a result you will see the Item Description which will be sent to
                    Temu basing on the settings you specified.<br />

                    Also, you can press a <strong>Select Randomly</strong> button to allow M2E Temu
                    to automatically select the most suitable Product for its previewing.'
                ),
            ]
        );

        $button = $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)->addData(
            [
                'label' => __('Select Randomly'),
                'onclick' => 'TemuTemplateDescriptionObj.selectProductIdRandomly()',
                'class' => 'action-primary',
                'style' => 'margin-left: 25px',
            ]
        );

        $fieldset->addField(
            'description_preview_magento_product_id',
            'text',
            [
                'label' => __('Magento Product ID'),
                'after_element_html' => $button->toHtml(),
                'class' => 'Temu-required-when-visible validate-digits
                                         Temu-validate-magento-product-id',
                'css_class' => '_required',
                'style' => 'width: 200px',
                'name' => 'description_preview[magento_product_id]',
            ]
        );

        $fieldset->addField(
            'description_preview_store_id',
            self::STORE_SWITCHER,
            [
                'label' => __('Store View'),
                'name' => 'description_preview[store_id]',
            ]
        );

        $fieldset->addField(
            'description_preview_description_mode',
            'hidden',
            [
                'name' => 'description_preview[description_mode]',
            ]
        );
        $fieldset->addField(
            'description_preview_description_template',
            'hidden',
            [
                'name' => 'description_preview[description_template]',
            ]
        );

        $fieldset->addField(
            'description_preview_form_key',
            'hidden',
            [
                'name' => 'form_key',
                'value' => $this->formKey->getFormKey(),
            ]
        );

        return <<<HTML
<div class="hidden">
    <div id="description_preview_popup" class="admin__old">{$form->toHtml()}</div>
</div>
HTML;
    }

    private function addBulletPointFields(\Magento\Framework\Data\Form\Element\Fieldset $fieldset, array $formData): void
    {
        $addMoreButtonHtml = $this->getLayout()->createBlock(\M2E\Temu\Block\Adminhtml\Magento\Button::class)
                              ->addData(
                                  [
                                      'id' => 'add_bullet_point_button',
                                      'label' => __('Add'),
                                      'onclick' => 'TemuTemplateDescriptionObj.showNextBulletPoint()',
                                      'class' => 'action-primary',
                                      'style' => 'margin-left: 70px;',
                                  ]
                              )
                              ->toHtml();

        $tooltipMessage = (string)__(
            'Use up to 5 bullet points to highlight your product’s key features and benefits.
            Keep each point under 200 characters and make them clear and impactful.'
        );

        $afterElementHtml = $this->getTooltipHtml($tooltipMessage) . $addMoreButtonHtml;

        $existBulletPoints = $formData[DescriptionResource::COLUMN_BULLET_POINTS] ?? [];
        if (empty($existBulletPoints)) {
            $this->createBulletPointRow(
                0,
                \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_NOT_CONFIGURED,
                null,
                null,
                (string)__('Bullet Points'),
                $afterElementHtml,
                $fieldset,
            );

            return;
        }

        $countBulletPoints = 0;
        $isFirstBulletPoint = true;
        /** @var \M2E\Temu\Model\Policy\Description\BulletPoint $bulletPoint */
        foreach ($existBulletPoints as $bulletPoint) {
            if ($countBulletPoints >= \M2E\Temu\Model\Policy\Description\BulletPoint::MAX_COUNT) {
                break;
            }

            $this->createBulletPointRow(
                $countBulletPoints,
                $bulletPoint->getMode(),
                $bulletPoint->getCustomValue(),
                $bulletPoint->getAttribute(),
                $isFirstBulletPoint ? (string)__('Bullet Points') : null,
                $isFirstBulletPoint ? $afterElementHtml : null,
                $fieldset,
            );

            $isFirstBulletPoint = false;
            $countBulletPoints++;
        }
    }

    private function createBulletPointRow(
        int $index,
        int $mode,
        ?string $customValue,
        ?string $attributeValue,
        ?string $title,
        ?string $afterElementHtml,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset
    ): void {
        $element = $fieldset->addField(
            'bullet_point_mode_' . $index,
            'select',
            [
                'label' => $title ?? '',
                'name' => 'description[bullet_point][' . $index . '][bullet_point_mode]',
                'values' => [
                    \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_NOT_CONFIGURED => __('None'),
                    \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_CUSTOM_VALUE => __('Custom Value'),
                    \M2E\Temu\Model\Policy\Description\BulletPoint::MODE_CUSTOM_ATTRIBUTE => __('Custom Attribute'),
                ],
                'value' => $mode,
                'class' => 'bullet-point-mode-selector',
                'required' => false,
                'after_element_html' => $this->createBulletPointCustomValueInput($index, $customValue)->toHtml()
                    . $this->createBulletPointAttributeInput($index, $attributeValue)->toHtml()
                    . $afterElementHtml,
                'container_id' => 'bullet_point_row_' . $index,
            ]
        );
        $element->addCustomAttribute('bullet_point_group', 'group_' . $index);
    }

    private function createBulletPointCustomValueInput(
        int $index,
        ?string $value
    ): \Magento\Framework\Data\Form\Element\AbstractElement {
        $customTextBlock = $this->elementFactory->create(
            'text',
            [
                'data' => [
                    'name' => 'description[bullet_point][' . $index . '][bullet_point_custom_value]',
                    'value' => $value ?? '',
                    'class' => 'input-text bullet_point_custom_value',
                    'style' => 'width: 30%; margin-left: 10px',
                ],
            ]
        )
                                                ->addCustomAttribute('bullet_point_group', 'group_' . $index);

        $customTextBlock->setId('bullet_point_value_custom_' . $index);
        $customTextBlock->setForm($this->_form);

        return $customTextBlock;
    }

    private function createBulletPointAttributeInput(
        int $index,
        ?string $value
    ): \Magento\Framework\Data\Form\Element\AbstractElement {
        $preparedAttributes = [];
        foreach ($this->textAttributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($attribute['code'] == $value) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => $attribute['code'],
                'label' => $attribute['label'],
            ];
        }

        $selectAttrBlock = $this->elementFactory->create(
            self::SELECT,
            [
                'data' => [
                    'name' => 'description[bullet_point][' . $index . '][bullet_point_attribute]',
                    'values' => [
                        [
                            'value' => '',
                            'label' => __('-- Please Select --'),
                        ],
                        [
                            'label' => __('Magento Attributes'),
                            'value' => $preparedAttributes,
                            'attrs' => [
                                'is_magento_attribute' => true,
                            ],
                        ],
                    ],
                    'class' => 'select bullet_point_attribute',
                    'style' => 'width: 30%; margin-left: 10px',
                    'create_magento_attribute' => true,
                ],
            ]
        )
                                                ->addCustomAttribute('allowed_attribute_types', 'text,select')
                                                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

        $selectAttrBlock->setId('bullet_point_value_attribute_' . $index);
        $selectAttrBlock->setForm($this->_form);

        return $selectAttrBlock;
    }
}
