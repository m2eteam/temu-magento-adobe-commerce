define([
    'jquery',
    'mage/translate'
], function (jQuery, $t) {
    'use strict';

    return function (specificContext) {
        const instance = {
            specific: specificContext,

            init: function () {
                this.addButton();
                this.initListeners();
            },

            addButton: function () {
                const self = this;
                jQuery('.M2E-category-product-attribute-add-more').each(function (_, element) {
                    const referenceRow = self.findLastVariantRow(element) ?? element;
                    const maxRows = self.getMaxRows(element);
                    const currentRows = self.findAllVariantRow(element).length + 1;
                    let additionalClass = currentRows >= maxRows ? 'final' : '';

                    const buttonHtml = `
                        <div class="admin__field-control control M2E-add-more-attribute-btn-container ${additionalClass}">
                            <button type="button" class="action-primary add_row">
                                <span>${$t('Add More')}</span>
                            </button>
                            <button type="button" class="action-primary remove_row">
                                <span>${$t('Remove Row')}</span>
                            </button>
                        </div>`;

                    const buttonContainer = jQuery(referenceRow).find('td').eq(2);
                    if (buttonContainer.find('.add_row').length === 0) {
                        buttonContainer.append(buttonHtml);
                        self.processBtnVisibility(referenceRow);
                    }
                });
            },

            initListeners: function () {
                jQuery(document)
                        .on('click', '.add_row', (event) => this.addRow(event))
                        .on('click', '.remove_row', (event) => this.removeRow(event))
                        .on('change', '[name*="[value_mode]"]', (event) => this.processBtnVisibility(event.target));
            },

            addRow: function (event) {
                const idSuffix = Date.now();
                const baseRow = jQuery(event.target).closest('tr');
                const lastVariant = this.findLastVariantRow(baseRow);
                const clonedRow = this.initNewRow(baseRow, idSuffix);

                baseRow.find('.admin__field-control.control').remove();
                clonedRow.find('.admin__field-control.control').remove();
                lastVariant ? lastVariant.after(clonedRow.hide()) : baseRow.after(clonedRow.hide());

                clonedRow.fadeIn(300);
                this.addButton();

                // Initialize custom attribute inputs and trigger changes
                window.initializationCustomAttributeInputs?.();
                this.getModeSelectAttribute(clonedRow, idSuffix).trigger('change');
            },

            removeRow: function (event) {
                const trRow = jQuery(event.target).closest('tr');
                const index = this.getRowIndex(trRow);

                [
                    `#real_attributes_dictionary_value_temu_recommended_${index}`,
                    `#real_attributes_dictionary_custom_value_table_${index}`,
                    `[id=real_attributes_dictionary_value_custom_value_${index}]`,
                    `#real_attributes_dictionary_value_custom_attribute_${index}`
                ].forEach((selector) => jQuery(selector).val(''));

                trRow.fadeOut(200, () => {
                    trRow.remove();
                    this.addButton();
                });
            },

            initNewRow: function (baseRow, id) {
                let row = baseRow.clone()
                        .removeClass('M2E-category-product-attribute-add-more')
                        .addClass('M2E-category-product-attribute-variant');

                jQuery(row).find('[name*="[attribute_id]"]').val((_, val) => {
                    return val.includes('~') ? val.replace(/~.+$/, `~${id}`) : `${val}~${id}`;
                });
                jQuery(row).find('[id^="real_attributes_dictionary_custom_value_table_"]')
                        .attr('id', (_, name) => name.replace(/_\d+/g, `_${id}`));

                jQuery(row).find('[name*="dictionary_"]').each(function () {
                    const $this = jQuery(this);
                    $this.attr('name', (_, name) => name.replace(/dictionary_\d+/g, `dictionary_${id}`));
                    if ($this.attr('id')) {
                        $this.attr('id', (_, name) => name.replace(/_\d+/g, `_${id}`));
                    }
                });

                jQuery(row).find('[name*="[value_custom_value]"]').val('');
                jQuery(row).find('[name*="[value_custom_attribute]"]')
                        .removeAttr('option_injected')
                        .find('option[value="new-one-attribute"]').remove();

                this.processSelectModeAttribute(row, id);

                return row;
            },

            processSelectModeAttribute: function (row, id) {
                const select = this.getModeSelectAttribute(row, id);
                select.on('change', (event) => {
                    this.specific.dictionarySpecificModeChange(id, event.target);
                });
                const firstNonEmptyOption = jQuery(select).find('option').filter(function () {
                    return jQuery(this).val().trim() !== '' && jQuery(this).val() !== '0';
                }).first();

                if (firstNonEmptyOption.length) {
                    firstNonEmptyOption.prop('selected', true);
                }
            },

            getModeSelectAttribute: function (row, idSuffix) {
                return jQuery(row).find('#real_attributes_dictionary_value_mode_' + idSuffix);
            },

            findLastVariantRow: function (row) {
                const variants = this.findAllVariantRow(row);
                return variants.length
                        ? jQuery(variants[variants.length - 1])
                        : null;
            },

            findAllVariantRow: function (row) {
                return jQuery(row)
                        .nextUntil(
                                '.M2E-category-product-attribute-add-more',
                                '.M2E-category-product-attribute-variant:visible'
                        )
                        .toArray();
            },

            processBtnVisibility: function (obj) {
                if (obj) {
                    const trRow = jQuery(obj).closest('tr');
                    const btnInRow = jQuery(trRow).find('.admin__field-control.control');
                    let index = this.getRowIndex(trRow);

                    let recommended = $(`real_attributes_dictionary_value_temu_recommended_${index}`),
                            customValueTable = $(`real_attributes_dictionary_custom_value_table_${index}`),
                            customValueInputs = $$(`[id=real_attributes_dictionary_value_custom_value_${index}]`),
                            attribute = $(`real_attributes_dictionary_value_custom_attribute_${index}`);

                    if (
                            this.isElementVisible(customValueTable)
                            || this.isElementVisible(customValueInputs)
                            || this.isElementVisible(attribute)
                            || this.isElementVisible(recommended)
                    ) {
                        btnInRow.show();
                    } else {
                        btnInRow.hide();
                    }
                }
            },

            isElementVisible: function (element) {
                return element && element.style && element.style.display !== 'none';
            },

            getRowIndex: function (row) {
                return jQuery(row).find('[name*="[value_mode]"]')
                        .attr('id')
                        .split('_')
                        .pop();
            },

            getMaxRows: function (element) {
                const $modeValue = jQuery(element).closest('tr').find('[name*="[value_mode]"]');

                return parseInt($modeValue.attr('data-max-rows') || '0', 10);
            },
        };

        instance.init();
    };
});
