<?php

namespace M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element;

use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element\Dictionary\Multiselect;
use M2E\Temu\Block\Adminhtml\Template\Category\Chooser\Specific\Form\Element\Dictionary\Select;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use M2E\Temu\Model\Category\CategoryAttribute;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Dictionary extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    use \M2E\Temu\Block\Adminhtml\Traits\BlockTrait;

    private const DEFAULT_MAX_MULTIPLE_VALUES_COUNT = 10;

    private const PROVINCE_REGION = [
        'id'    => '1000001001',
        'title' => 'Province/Region (for China)',
    ];

    public \Magento\Framework\View\LayoutInterface $layout;
    private \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper;
    /** @var array */
    private array $magentoAttributes;
    private \M2E\Temu\Block\Adminhtml\Magento\Renderer\JsRenderer $js;

    public function __construct(
        \M2E\Core\Helper\Magento\Attribute $magentoAttributeHelper,
        \M2E\Temu\Block\Adminhtml\Magento\Context\Template $context,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        \M2E\Temu\Block\Adminhtml\Magento\Renderer\JsRenderer $js,
        $data = []
    ) {
        $this->layout = $context->getLayout();
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->js = $js;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->setType('specifics');
    }

    //########################################

    public function getElementHtml()
    {
        return '';
    }

    public function toHtml()
    {
        if ($this->getData('attribute_type') === 'real_attributes') {
            $json = $this->getRecommendedJsonRelations();
            $this->js->addRequireJs(
                [
                    'etcs' => 'Temu/Template/Category/Specifics',
                    'childSelectUpdater' => 'Temu/Template/Category/AttributesRelation',
                ],
                <<<JS
        childSelectUpdater({$json});
JS
            );
        }

        return parent::toHtml();
    }

    //########################################

    public function getSpecifics()
    {
        return $this->getData('specifics');
    }

    private function makeInputName(int $index, string $key): string
    {
        return sprintf(
            '%s[dictionary_%s][%s]',
            $this->getId(),
            $index,
            $key
        );
    }

    private function makeElementId(int $index, string $key, string $customIndex = ''): string
    {
        $id = sprintf(
            '%s_dictionary_%s_%s',
            $this->getId(),
            $key,
            $index
        );

        if ($customIndex !== '') {
            $id .= "_$customIndex";
        }

        return $id;
    }

    //########################################

    public function getAttributeIdHiddenHtml($index, $specific): string
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => $this->makeInputName($index, 'attribute_id'),
                'class' => 'temu-dictionary-specific-attribute-id collected-attribute',
                'value' => $specific['id'],
            ],
        ]);
        $element->setForm($this->getForm());

        return $element->getElementHtml();
    }

    public function getAttributeNameHiddenHtml($index, $specific): string
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => $this->makeInputName($index, 'attribute_name'),
                'class' => 'temu-dictionary-specific-attribute-id collected-attribute',
                'value' => $specific['title'],
            ],
        ]);
        $element->setForm($this->getForm());

        return $element->getElementHtml();
    }

    public function getAttributeTypeHiddenHtml(int $index, $attribute)
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => $this->makeInputName($index, 'attribute_type'),
                'class' => 'temu-dictionary-specific-attribute-id collected-attribute',
                'value' => $attribute['attribute_type'],
            ],
        ]);
        $element->setForm($this->getForm());

        return $element->getElementHtml();
    }

    public function getModeHtml($index): string
    {
        $element = $this->_factoryElement->create('hidden', [
            'data' => [
                'name' => $this->makeInputName($index, 'mode'),
                'class' => 'specific_mode collected-attribute',
                'value' => \M2E\Temu\Model\Template\Category::MODE_ITEM_SPECIFICS,
            ],
        ]);

        $element->setForm($this->getForm());
        $element->setId($this->makeElementId($index, 'mode'));

        return $element->getElementHtml();
    }

    public function getAttributeTitleLabelHtml($index, $specific): string
    {
        $required = '';
        if ($specific['required']) {
            $required = '&nbsp;<span class="required">*</span>';
        }

        return sprintf(
            '<span id="%s">%s%s</span>',
            $this->makeElementId($index, 'attribute_title_label'),
            $specific['title'],
            $required
        );
    }

    public function getTitleTooltip($specific): string
    {
        if (
            $specific['id'] !== self::PROVINCE_REGION['id']
            || $specific['title'] !== self::PROVINCE_REGION['title']
        ) {
            return '';
        }

        $text = (string)__('Must be specified if "Country of Origin" is China.');

        return $this->getTooltipHtml($text, true);
    }

    public function getValueModeSelectHtml($index, $specific): string
    {
        $values = [
            \M2E\Temu\Model\Template\Category::VALUE_MODE_NONE => [
                'value' => \M2E\Temu\Model\Template\Category::VALUE_MODE_NONE,
                'label' => __('None'),
            ],
            \M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED => [
                'value' => \M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED,
                'label' => __('Temu Recommended'),
            ],
        ];

        if ($specific['is_customized']) {
            $values[\M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_ATTRIBUTE] = [
                'value' => \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_ATTRIBUTE,
                'label' => __('Custom Attribute'),
            ];
            $values[\M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_VALUE] = [
                'value' => \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_VALUE,
                'label' => __('Custom Value'),
            ];
        }

        if ($specific['required']) {
            $values[\M2E\Temu\Model\Template\Category::VALUE_MODE_NONE] = [
                'label' => '',
                'value' => '',
                'style' => 'display: none',
            ];
        }

        if ($specific['type'] === \M2E\Temu\Model\Template\Category::RENDER_TYPE_TEXT) {
            unset($values[\M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED]);
        }

        if (empty($specific['values'])) {
            unset($values[\M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED]);
        }

        /** @var \Magento\Framework\Data\Form\Element\Select $element */
        $element = $this->_factoryElement->create(Select::class, [
            'data' => [
                'name' => $this->makeInputName($index, 'value_mode'),
                'style' => 'width: 100%',
                'onchange' => "TemuTemplateCategorySpecificsObj.dictionarySpecificModeChange('{$index}', this);",
                'value' => !empty($specific['template_attribute']) ?
                    $specific['template_attribute']['value_mode'] : null,
                'values' => $values,
                'data-max-rows' => $specific['multiple_selected_max_count']
                    ?? self::DEFAULT_MAX_MULTIPLE_VALUES_COUNT,
            ],
        ]);

        $variantValidator = ($specific['attribute_type'] === 'sales') ? 'variant-validator' : '';
        $element->setNoSpan(true);
        $element->setClass(
            'Temu-required-when-visible input-specific-value-mode collected-attribute ' . $variantValidator
        );
        $element->setForm($this->getForm());
        $element->setId($this->makeElementId($index, 'value_mode'));

        return $element->getElementHtml();
    }

    public function getValueTemuRecommendedHtml($index, $specific): string
    {
        $values = [];
        foreach ($specific['values'] as $value) {
            $values[] = [
                'label' => $value['value'],
                'value' => $value['id'],
            ];
        }

        $display = 'display: none;';
        $disabled = true;
        if (
            isset($specific['template_attribute']['value_mode']) &&
            $specific['template_attribute']['value_mode']
            == \M2E\Temu\Model\Template\Category::VALUE_MODE_TEMU_RECOMMENDED
        ) {
            $display = '';
            $disabled = false;
        }

        if (
            $specific['type'] == \M2E\Temu\Model\Template\Category::RENDER_TYPE_SELECT_MULTIPLE ||
            $specific['type'] == \M2E\Temu\Model\Template\Category::RENDER_TYPE_SELECT_MULTIPLE_OR_TEXT
        ) {
            /** @var \Magento\Framework\Data\Form\Element\Select $element */
            $element = $this->_factoryElement->create(
                Multiselect::class,
                [
                    'data' => [
                        'class' => 'collected-attribute',
                        'name' => $this->makeInputName($index, 'value_temu_recommended'),
                        'style' => 'width: 100%;' . $display,
                        'value' => empty($specific['template_attribute']['value_temu_recommended'])
                            ? []
                            : $specific['template_attribute']['value_temu_recommended'],
                        'values' => $values,
                        'data-min_values' => $specific['min_values'],
                        'data-max_values' => $specific['max_values'],
                        'disabled' => $disabled,
                    ],
                ]
            );
        } else {
            array_unshift(
                $values,
                [
                    'label' => '',
                    'value' => '',
                    'style' => 'display: none',
                ]
            );

            /** @var \Magento\Framework\Data\Form\Element\Select $element */
            $element = $this->_factoryElement->create('select', [
                'data' => [
                    'name' => $this->makeInputName($index, 'value_temu_recommended'),
                    'style' => 'width: 100%;' . $display,
                    'value' => empty($specific['template_attribute']['value_temu_recommended'])
                        ? ''
                        : $specific['template_attribute']['value_temu_recommended'],
                    'values' => $values,
                    'disabled' => $disabled,
                    'class' => 'collected-attribute',
                ],
            ]);
        }

        $element->addClass('Temu-required-when-visible');
        $element->setNoSpan(true);
        $element->setForm($this->getForm());
        $element->setId($this->makeElementId($index, 'value_temu_recommended'));

        return $element->getElementHtml();
    }

    /**
     * @throws \M2E\Temu\Model\Exception\Logic
     */
    public function getValueCustomValueHtml($index, $specific): string
    {
        if (empty($specific['template_attribute']['value_custom_value'])) {
            $customValues = '';
        } else {
            $customValues = $specific['template_attribute']['value_custom_value'];
        }

        $display = 'display: none;';
        $disabled = true;
        if (
            !empty($specific['template_attribute']['value_mode']) &&
            $specific['template_attribute']['value_mode']
            == \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_VALUE
        ) {
            $display = '';
            $disabled = false;
        }

        /** @var \Magento\Framework\Data\Form\Element\Text $element */
        $element = $this->_factoryElement->create('text', [
            'data' => [
                'name' => $this->makeInputName($index, 'value_custom_value'),
                'style' => 'width: 100%;',
                'class' => 'Temu-required-when-visible item-specific collected-attribute',
                'value' => $customValues,
                'disabled' => $disabled,
            ],
        ]);
        $element->setNoSpan(true);
        $element->setForm($this->getForm());
        $element->setId($this->makeElementId($index, 'value_custom_value'));

        $customValueRows = <<<HTML
    <tr>
        <td style="border: none; width: 100%; vertical-align:top; text-align: left; padding: 0 0 2px; 0">
            {$element->getHtml()}
        </td>
    </tr>
HTML;

        $divId = $this->makeElementId($index, 'custom_value_table');
        $tableBodyId = $this->makeElementId($index, 'custom_value_table_body');

        return <<<HTML
    <div id="$divId" style="$display">
        <table style="width: 100%">
            <tbody id="$tableBodyId">
                {$customValueRows}
            </tbody>
        </table>
    </div>
HTML;
    }

    public function getValueCustomAttributeHtml($index, $specific): string
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (!isset($this->magentoAttributes)) {
            $this->magentoAttributes = $this->magentoAttributeHelper->getAll();
        }
        $attributes = $this->magentoAttributes;

        foreach ($attributes as &$attribute) {
            $attribute['value'] = $attribute['code'];
            unset($attribute['code']);
        }

        $display = 'display: none;';
        $disabled = true;
        if (
            isset($specific['template_attribute']['value_mode']) &&
            $specific['template_attribute']['value_mode']
            == \M2E\Temu\Model\Template\Category::VALUE_MODE_CUSTOM_ATTRIBUTE
        ) {
            $display = '';
            $disabled = false;
        }

        /** @var \Magento\Framework\Data\Form\Element\Select $element */
        $element = $this->_factoryElement->create('select', [
            'data' => [
                'name' => $this->makeInputName($index, 'value_custom_attribute'),
                'style' => 'width: 100%;' . $display,
                'class' => 'Temu-custom-attribute-can-be-created collected-attribute',
                'value' => empty($specific['template_attribute']['value_custom_attribute']) ?
                    '' :
                    $specific['template_attribute']['value_custom_attribute'],
                'values' => $attributes,
                'apply_to_all_attribute_sets' => 0,
                'disabled' => $disabled,
            ],
        ]);

        $element->setNoSpan(true);
        $element->setForm($this->getForm());
        $element->setId($this->makeElementId($index, 'value_custom_attribute'));

        return $element->getElementHtml();
    }

    public function getRecommendedJsonRelations(): string
    {
        $formattedData = [];

        foreach ($this->getSpecifics() as $index => $specific) {
            $formattedData[] = [
                'id' => $specific['id'],
                'html_id' => $this->makeElementId($index, 'value_temu_recommended'),
                'custom_html_id' => $this->makeElementId($index, 'value_custom_value'),
                'attr_html_id' => $this->makeElementId($index, 'value_custom_attribute'),
                'mode_value_html_id' => $this->makeElementId($index, 'value_mode'),
                'parent_template_pid' => $specific['parent_template_pid'] ?? null,
                'values' => $this->formatAttributeValues($specific),
                'has_child' => $this->hasChild($specific),
            ];
        }

        return json_encode($formattedData, JSON_PRETTY_PRINT);
    }

    public function isProductChildAttribute(array $specific): bool
    {
        return $this->getData('attribute_type') === 'real_attributes'
            && !empty($specific['parent_template_pid']);
    }

    public function getAdditionalClass($specific): string
    {
        $result = '';
        if ($this->isProductChildAttribute($specific)) {
            $result .= ' child-relation-row ';
        }

        if ($this->shouldShowAddMoreBtn($specific)) {
            if (CategoryAttribute::getCleanAttributeId($specific['id']) !== $specific['id']) {
                $result .= ' M2E-category-product-attribute-variant ';
            } else {
                $result .= ' M2E-category-product-attribute-add-more ';
            }
        }

        return $result;
    }

    private function formatAttributeValues(array $specific): array
    {
        $selected = $specific['template_attribute']['value_temu_recommended'] ?? [];
        $formattedValues = [];

        foreach ($specific['values'] as $value) {
            $relations = [];
            foreach ($value['children_relation'] as $rel) {
                $relations[$rel['child_template_pid']] = [];
                foreach ($rel['values_ids'] as $relId) {
                    $relations[$rel['child_template_pid']][] = (string)$relId;
                }
            }

            $formattedValues[] = [
                'id' => $value['id'],
                'name' => $value['value'],
                'selected' => $selected,
                'children_relation' => $relations,
            ];
        }

        return $formattedValues;
    }

    private function hasChild(array $specific): bool
    {
        foreach ($specific['values'] as $value) {
            if (!empty($value['children_relation'])) {
                return true;
            }
        }

        return false;
    }

    private function shouldShowAddMoreBtn(array $specific): bool
    {
        return $specific['attribute_type'] === CategoryAttribute::ATTRIBUTE_TYPE_PRODUCT
            && $specific['type'] == \M2E\Temu\Model\Template\Category::RENDER_TYPE_SELECT_MULTIPLE
            && empty($specific['parent_template_pid'])
            && !$this->hasChild($specific);
    }
}
