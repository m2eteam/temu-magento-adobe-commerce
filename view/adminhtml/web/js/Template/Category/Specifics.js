define([
    'jquery',
    'mage/translate',
    'Temu/Template/Category/Specifics/DynamicRowsHandler',
    'Temu/Common',
], function (jQuery, $t, DynamicRowsHandler) {
    window.TemuTemplateCategorySpecifics = Class.create(Common, {

        maxSelectedSpecifics: 45,
        specificsSnapshot: {},
        maxVariantAttrs: 2,
        minVariantAttrs: 1,

        // ---------------------------------------

        initialize: function () {
            jQuery.validator.addMethod('temu-custom-specific-attribute-id', function (value, el) {

                        var customTitleInput = el;

                        var result = true;
                        $$('.temu-dictionary-specific-attribute-id').each(function (el) {
                            if (el.value == value) {
                                result = false;
                                throw $break;
                            }
                        });

                        $$('.temu-custom-specific-attribute-id').each(function (el) {
                            if (el == customTitleInput) {
                                return;
                            }

                            if (!el.visible()) {
                                return;
                            }

                            if (trim(el.value) == value) {
                                result = false;
                                throw $break;
                            }
                        });

                        return result;
                    }, $t('Item Specifics cannot have the same Labels.')
            );

            this.addVariantsValidator();
            this.createSpecificsSnapshot();
            new DynamicRowsHandler(this);
        },

        getElementScope: function (element) {
            return jQuery(element)
                    .parents('table')
                    .attr('data-specific-scope');
        },

        // ---------------------------------------

        resetSpecifics: function () {
            $$('.input-specific-value-mode').each(function (el) {
                el.childElements()[0].selected = true;
                el.simulate('change');
            });

            $$('.remove_custom_specific_button').each(function (el) {
                el.simulate('click');
            });
        },

        // ---------------------------------------

        addVariantsValidator: function () {
            jQuery.validator.addMethod('variant-validator', function (val, element) {
                const filledCount = jQuery('.variant-validator').filter((_, e) => e.value !== '0').length;
                const shouldHighlight =
                        (filledCount > this.maxVariantAttrs && val !== '0') ||
                        (filledCount < this.minVariantAttrs && val === '0');

                if (!shouldHighlight) {
                    return true;
                }

                jQuery(element).addClass('mage-error').attr('aria-invalid', 'true');
                let messageBlock = jQuery('#messages');
                if (!messageBlock.length) {
                    const container = jQuery('#chooser_container_specific').length
                            ? '#chooser_container_specific'
                            : '.page-main-actions';
                    jQuery(container).before('<div id="messages"></div>');
                    messageBlock = jQuery('#messages');
                }

                if (!messageBlock.hasClass('variation-error')) {
                    const errorMessage = $t(
                            'Invalid variant attributes: You must select at least %1 and no more than %2 variant attributes.'
                    )
                            .replace('%1', this.minVariantAttrs)
                            .replace('%2', this.maxVariantAttrs);

                    messageBlock
                            .append(`
                            <div class="messages">
                                <div class="message message-error error variant-error">
                                    <div data-ui-id="messages-message-error">
                                        ${errorMessage}
                                    </div>
                                </div>
                            </div>
                        `)
                            .addClass('variation-error');
                }

                return false;
            }.bind(this), '');

            jQuery(document).on('change', '.variant-validator', function () {
                const messageBlock = jQuery('#messages');
                if (messageBlock && messageBlock.hasClass('variation-error')) {
                    messageBlock.removeClass('variation-error').empty();
                }
            });
        },

        // ---------------------------------------

        createSpecificsSnapshot: function () {
            this.specificsSnapshot = this.collectSpecifics();
        },

        isSpecificsChanged: function () {
            return JSON.stringify(this.specificsSnapshot) !== JSON.stringify(this.collectSpecifics());
        },

        collectSpecifics: function () {
            let specifics = {};

            let self = this;
            $$('.specific-table').each(function (table, index) {
                let attributeScope = table.getAttribute('data-specific-scope');
                if (typeof specifics[attributeScope] === 'undefined') {
                    specifics[attributeScope] = {};
                }
                table.select('.collected-attribute').each(function (collectedItem) {
                    if (collectedItem.disabled) {
                        return true;
                    }

                    let temp = collectedItem.name.match(/\[([a-z0-9_]*)\]\[([a-z_]*)\]/);
                    if (typeof specifics[attributeScope][temp[1]] === 'undefined') {
                        specifics[attributeScope][temp[1]] = {};
                    }

                    if (typeof specifics[attributeScope][temp[1]][temp[2]] === 'undefined') {
                        specifics[attributeScope][temp[1]][temp[2]] = {};
                    }

                    if (collectedItem.multiple) {
                        specifics[attributeScope][temp[1]][temp[2]] = self.getSelectValues(collectedItem);
                    } else {
                        let specific = specifics[attributeScope][temp[1]]['value_custom_value'];
                        if (typeof specific !== 'undefined' && Object.keys(specific).length !== 0) {
                            let multi_input = [];
                            if (Object.isArray(specific)) {
                                specifics[temp[1]][temp[2]].forEach(function (item) {
                                    multi_input.push(item);
                                });
                            } else {
                                multi_input.push(specifics[attributeScope][temp[1]][temp[2]]);
                            }
                            multi_input.push(collectedItem.value);
                            specifics[attributeScope][temp[1]][temp[2]] = multi_input;
                        } else {
                            specifics[attributeScope][temp[1]][temp[2]] = collectedItem.value;
                        }
                    }
                });
            });

            return specifics;
        },

        getSelectValues: function (select) {
            let result = [];
            let options = select && select.options;
            let opt;

            for (let i = 0, iLen = options.length; i < iLen; i++) {
                opt = options[i];

                if (opt.selected) {
                    result.push(opt.value || opt.text);
                }
            }

            return result;
        },

        // ---------------------------------------

        // dictionary specifics
        // ---------------------------------------

        dictionarySpecificModeChange: function (index, select) {
            let scope = this.getElementScope(select);

            let recommended = $(`${scope}_dictionary_value_temu_recommended_${index}`),
                    customValueTable = $(`${scope}_dictionary_custom_value_table_${index}`),
                    customValueInputs = $$(`[id=${scope}_dictionary_value_custom_value_${index}]`),
                    attribute = $(`${scope}_dictionary_value_custom_attribute_${index}`);

            recommended.hide().disable();
            customValueTable.hide();
            customValueInputs.invoke('disable');
            attribute.hide().disable();

            if (select.value == Temu.php.constant('\\M2E\\Temu\\Model\\Template\\Category::VALUE_MODE_TEMU_RECOMMENDED')) {
                recommended.show().enable();
            }
            if (select.value == Temu.php.constant('\\M2E\\Temu\\Model\\Template\\Category::VALUE_MODE_CUSTOM_VALUE')) {
                customValueTable.show();
                customValueInputs.invoke('enable');
            }
            if (select.value == Temu.php.constant('\\M2E\\Temu\\Model\\Template\\Category::VALUE_MODE_CUSTOM_ATTRIBUTE')) {
                attribute.show().enable();
            }
        },

        addItemSpecificsCustomValueRow: function (index, button) {
            let scope = this.getElementScope(button);

            let timestampId = new Date().getTime();
            let tbody = $(`${scope}_dictionary_custom_value_table_body_${index}`);

            let newRow = Element.clone(tbody.childElements()[0], true);
            newRow
                    .down('button.remove_item_specifics_custom_value_button')
                    .addEventListener('click', this.removeItemSpecificsCustomValue.bind(this));

            let newRowInput = newRow.select('[id*=_value_custom_value_' + index + '_]')[0];
            newRowInput.clear();

            //replacing id to unique value
            newRowInput.setAttribute('id', newRowInput.id.replace(/_\d+$/, '_' + timestampId));

            tbody.appendChild(newRow);

            let valuesCounter = tbody.childElements().length;

            if (parseInt(tbody.getAttribute('data-max_values')) > valuesCounter) {
                button.show();
            } else {
                button.hide();
            }

            if (parseInt(tbody.getAttribute('data-min_values')) >= valuesCounter) {
                $$(`[id$=custom_value_table_body_${index}] tr td.btn_value_remove`).invoke('hide');
            } else {
                $$(`[id$=custom_value_table_body_${index}] tr td.btn_value_remove`).invoke('show');
            }
        },

        removeItemSpecificsCustomValue: function (button) {
            if (button instanceof PointerEvent) {
                button = button.currentTarget;
            }

            let tbody = $(button).up('tbody');
            let addBtn = $(button).up('table').next('a');

            $(button).up('tr').remove();

            let valuesCounter = tbody.childElements().length;

            if (parseInt(tbody.getAttribute('data-max_values')) > valuesCounter) {
                addBtn.show();
            } else {
                addBtn.hide();
            }

            if (valuesCounter === 1 || parseInt(tbody.getAttribute('data-min_values')) >= valuesCounter) {
                let btnRemove = tbody.getElementsByClassName('btn_value_remove');
                for (let i = 0; i < btnRemove.length; i++) {
                    btnRemove[i].hide();
                }
            }
        },
    });
});
