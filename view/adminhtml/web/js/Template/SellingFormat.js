define([
    'Magento_Ui/js/modal/modal',
    'Temu/Common'
], function (modal) {

    window.TemuTemplateSellingFormat = Class.create(Common, {


        priceChangeIndex: 0,
        priceChangeTpl: '',

        constAbsoluteIncrease: Temu.php.constant(
                '\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE'
        ),
        constAbsoluteDecrease: Temu.php.constant(
                '\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE'
        ),
        constPercentageIncrease: Temu.php.constant(
                '\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE'
        ),
        constPercentageDecrease: Temu.php.constant(
                '\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE'
        ),
        constAttribute: Temu.php.constant(
                '\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_ATTRIBUTE'
        ),

        // ---------------------------------------

        initialize: function () {
            var self = this;
            jQuery.validator.addMethod('Temu-validate-price-coefficient', function (value, el) {

                var tempEl = el;

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                var coefficient = el.up().next().down('input');

                coefficient.removeClassName('price_unvalidated');

                if (!coefficient.up('div').visible()) {
                    return true;
                }

                if (coefficient.value == '') {
                    return false;
                }

                var floatValidator = Validation.get('Temu-validation-float');
                if (floatValidator.test($F(coefficient), coefficient) && parseFloat(coefficient.value) <= 0) {
                    coefficient.addClassName('price_unvalidated');
                    return false;
                }

                return true;
            }, Temu.translator.translate('Price Change is not valid.'));

            jQuery.validator.addMethod('Temu-validate-price-modifier', function (value, el) {
                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                var coefficient = el.up().next().down('input');

                coefficient.removeClassName('price_unvalidated');

                if (!coefficient.visible()) {
                    return true;
                }

                if (coefficient.value == '') {
                    return false;
                }

                var floatValidator = Validation.get('Temu-validation-float');
                if (floatValidator.test($F(coefficient), coefficient) && parseFloat(coefficient.value) <= 0) {
                    coefficient.addClassName('price_unvalidated');
                    return false;
                }

                return true;
            }, Temu.translator.translate('Price Change is not valid.'));

            jQuery.validator.addMethod('Temu-validate-qty', function (value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                if (value.match(/[^\d]+/g) || value <= 0) {
                    return false;
                }

                return true;
            }, Temu.translator.translate('Wrong value. Only integer numbers.'));

            var priceChangeRowTemplate = $('fixed_price_change_row_template');
            if (priceChangeRowTemplate) {
                this.priceChangeTpl = priceChangeRowTemplate.innerHTML;
                priceChangeRowTemplate.remove();
            }
        },

        initObservers: function () {

            $('qty_mode')
                    .observe('change', this.qty_mode_change)
                    .simulate('change');

            $('qty_modification_mode')
                    .observe('change', this.qtyPostedMode_change)
                    .simulate('change');

            $('fixed_price_mode')
                    .observe('change', this.fixed_price_mode_change)
                    .simulate('change');

            $('item_tax_code_mode')
                    .observe('change', this.item_tax_code_mode_change)
                    .simulate('change')
        },


        updateQtyMode: function () {
            var qtyMode = $('qty_mode'),
                    qtyModeTr = $('qty_mode_tr'),
                    qtyCustomValue = $('qty_custom_value'),
                    customValueTr = $('qty_mode_cv_tr');

            qtyModeTr.show();
            qtyMode.simulate('change');

        },

        updateQtyPercentage: function () {
            var qtyPercentageTr = $('qty_percentage_tr');

            qtyPercentageTr.hide();

            var qtyMode = $('qty_mode').value;

            if (qtyMode == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_NUMBER')) {
                return;
            }

            qtyPercentageTr.show();
        },


        // ---------------------------------------

        qty_mode_change: function () {
            var self = TemuTemplateSellingFormatObj,

                    customValueTr = $('qty_mode_cv_tr'),
                    attributeElement = $('qty_custom_attribute'),

                    maxPostedValueTr = $('qty_modification_mode_tr'),
                    maxPostedValueMode = $('qty_modification_mode');

            customValueTr.hide();
            attributeElement.value = '';

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_NUMBER')) {
                customValueTr.show();
            } else if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_ATTRIBUTE')) {
                self.selectMagentoAttribute(this, attributeElement);
            }

            maxPostedValueTr.hide();
            maxPostedValueMode.value = Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODIFICATION_MODE_OFF');

            if (self.isMaxPostedQtyAvailable(this.value)) {

                maxPostedValueTr.show();
                maxPostedValueMode.value = Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODIFICATION_MODE_ON');

                if (self.isMaxPostedQtyAvailable(Temu.formData.qty_mode)) {
                    maxPostedValueMode.value = Temu.formData.qty_modification_mode;
                }
            }

            maxPostedValueMode.simulate('change');

            self.updateQtyPercentage();
        },

        isMaxPostedQtyAvailable: function (qtyMode) {
            return qtyMode == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_PRODUCT') ||
                    qtyMode == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_ATTRIBUTE') ||
                    qtyMode == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODE_PRODUCT_FIXED');
        },

        qtyPostedMode_change: function () {
            var minPosterValueTr = $('qty_min_posted_value_tr'),
                    maxPosterValueTr = $('qty_max_posted_value_tr');

            minPosterValueTr.hide();
            maxPosterValueTr.hide();

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::QTY_MODIFICATION_MODE_ON')) {
                minPosterValueTr.show();
                maxPosterValueTr.show();
            }
        },

        fixed_price_mode_change: function (self) {
            let attributeElement = $('fixed_price_custom_attribute');

            attributeElement.value = '';
            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_MODE_ATTRIBUTE')) {
                TemuTemplateSellingFormatObj.selectMagentoAttribute(this, attributeElement);
            }
        },

        item_tax_code_mode_change: function () {
            const itemTaxCodeMode = parseInt(this.value);

            const modeNone = Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::ITEM_TAX_CODE_MODE_NONE');
            const modeAttribute = Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::ITEM_TAX_CODE_MODE_ATTRIBUTE');
            const modeCustomValue = Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::ITEM_TAX_CODE_MODE_CUSTOM_VALUE');

            let attributeField = $('item_tax_code_attribute');
            let customValueField = $('item_tax_code_custom_value');
            let modeSelect = $('item_tax_code_mode')

            let customValueBlock = $('item_tax_code_custom_value_tr');

            if (itemTaxCodeMode === modeNone) {
                attributeField.value = null;
                customValueField.value = null;
                customValueBlock.hide();
            }

            if (itemTaxCodeMode === modeAttribute) {
                attributeField.value = modeSelect
                        .options[this.selectedIndex]
                        .getAttribute('attribute_code')
                customValueField.value = null;
                customValueBlock.hide();
            }

            if (itemTaxCodeMode === modeCustomValue) {
                attributeField.value = null;
                customValueBlock.show();
            }
        },

        price_coefficient_mode_change: function () {
            var coefficientInputDiv = $('fixed_price_input_div'),
                    signSpan = $('fixed_price_sign_span'),
                    percentSpan = $('fixed_price_percent_span'),
                    examplesContainer = $('fixed_price_example_container');

            // ---------------------------------------

            coefficientInputDiv.show();
            examplesContainer.show();

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_NONE')) {
                coefficientInputDiv.hide();
                examplesContainer.hide();
            }
            // ---------------------------------------

            // ---------------------------------------
            signSpan.innerHTML = '';
            percentSpan.innerHTML = '';
            $$('.' + this.id.replace('coefficient_mode', '') + 'example').invoke('hide');

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_INCREASE')) {
                signSpan.innerHTML = '+';

                if (typeof Temu.formData.currency != 'undefined') {
                    percentSpan.innerHTML = Temu.formData.currency;
                }

                $(this.id.replace('coefficient_mode', '') + 'example_absolute_increase').show();
            }

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE')) {
                signSpan.innerHTML = '-';

                if (typeof Temu.formData.currency != 'undefined') {
                    percentSpan.innerHTML = Temu.formData.currency;
                }

                $(this.id.replace('coefficient_mode', '') + 'example_absolute_decrease').show();
            }

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE')) {
                signSpan.innerHTML = '+';
                percentSpan.innerHTML = '%';

                $(this.id.replace('coefficient_mode', '') + 'example_percentage_increase').show();
            }

            if (this.value == Temu.php.constant('\\M2E\\Temu\\Model\\Policy\\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE')) {
                signSpan.innerHTML = '-';
                percentSpan.innerHTML = '%';

                $(this.id.replace('coefficient_mode', '') + 'example_percentage_decrease').show();
            }
            // ---------------------------------------
        },

        // ---------------------------------------

        selectMagentoAttribute: function (elementSelect, elementAttribute) {
            var attributeCode = elementSelect.options[elementSelect.selectedIndex].getAttribute('attribute_code');
            elementAttribute.value = attributeCode;
        },


        checkMessages: function (data, container) {
            if (typeof TemuListingTemplateSwitcherObj == 'undefined') {
                // not inside template switcher
                return;
            }

            var id = '',
                    nick = Temu.php.constant('M2E_Temu_Model_Temu_Template_Manager::TEMPLATE_SELLING_FORMAT'),
                    storeId = TemuListingTemplateSwitcherObj.storeId,
                    callback = function () {
                        var refresh = $(container).down('a.refresh-messages');
                        if (refresh) {
                            refresh.observe('click', function () {
                                this.checkMessages(data, container);
                            }.bind(this))
                        }
                    }.bind(this);

            TemplateManagerObj.checkMessages(
                    id,
                    nick,
                    data,
                    storeId,
                    container,
                    callback
            );
        },


        renderFixedPriceChangeRows: function (data) {
            var self = this;
            for (var i = 0; i < data.length; i++) {
                self.addFixedPriceChangeRow(data[i]);
            }

            this.priceChangeCalculationUpdate();
        },

        addFixedPriceChangeRow: function (priceChangeData) {
            var priceChangeContainer = $('fixed_price_change_container');

            priceChangeData = priceChangeData || {};
            this.priceChangeIndex++;

            var tpl = this.priceChangeTpl;
            tpl = tpl.replace(/%i%/g, this.priceChangeIndex);
            priceChangeContainer.insert(tpl);
            var modeElement = $('fixed_price_modifier_mode_' + this.priceChangeIndex),
                    valueElement = $('fixed_price_modifier_value_' + this.priceChangeIndex),
                    removeButtonElement = $('fixed_price_modifier_row_remove_button_' + this.priceChangeIndex);

            var handlerObj = new AttributeCreator('fixed_price_modifier_mode_' + this.priceChangeIndex);
            handlerObj.setSelectObj(modeElement);
            handlerObj.injectAddOption();

            if (priceChangeData.mode) {
                for (var i = 0; i < modeElement.options.length; i++) {
                    if (modeElement.options[i].value != priceChangeData.mode) {
                        continue;
                    }

                    if (modeElement.options[i].value < this.constAttribute) {
                        modeElement.selectedIndex = i;
                        valueElement.value = priceChangeData['value'];
                        break;
                    } else {
                        if (modeElement.options[i].getAttribute('attribute_code') == priceChangeData['attribute_code']) {
                            modeElement.selectedIndex = i;
                            valueElement.hide();
                            break;
                        }
                    }
                }

                this.priceChangeCalculationUpdate();
            }

            var selectOnChangeHandler = function () {
                this.priceChangeSelectUpdate(modeElement)
            }.bind(this);
            modeElement
                    .observe('change', selectOnChangeHandler)
                    .simulate('change');

            var inputOnKeyUpHandler = function () {
                this.priceChangeCalculationUpdate();
            }.bind(this);
            valueElement.observe('keyup', inputOnKeyUpHandler);

            var buttonOnClickHandler = function () {
                this.removeFixedPriceChangeRow(removeButtonElement);
            }.bind(this);
            removeButtonElement.observe('click', buttonOnClickHandler);
        },

        removeFixedPriceChangeRow: function (element) {
            element.up('.fixed-price-change-row').remove();
            this.priceChangeCalculationUpdate();
        },


        priceChangeSelectUpdate: function (element) {
            var valueElement = $('fixed_price_modifier_value_' + element.dataset.priceChangeIndex),
                    attributeElement = $('fixed_price_modifier_attribute_' + element.dataset.priceChangeIndex);

            if (element.options[element.selectedIndex].value == this.constAttribute) {
                valueElement.hide();
                this.selectMagentoAttribute(element, attributeElement);
            } else {
                valueElement.show();
                attributeElement.value = '';
            }

            this.priceChangeCalculationUpdate();
        },

        priceChangeCalculationUpdate: function () {
            var select, input, selectedOption, currentValue, result = 100, operations = ['$100'];

            $$('#fixed_price_change_container > *').each(function (element) {
                select = element.select('select').first();
                input = element.select('input').first();

                if (select.selectedIndex == -1) {
                    return;
                }

                selectedOption = select.options[select.selectedIndex];
                if (selectedOption.value == this.constAttribute) {
                    result += 7.5;
                    operations.push('+ $7.5');
                    return;
                }

                currentValue = Number.parseFloat(input.value);
                if (isNaN(currentValue) || currentValue < 0) {
                    return;
                }

                switch (Number.parseInt(selectedOption.value)) {
                    case this.constAbsoluteIncrease:
                        if (!isNaN(input.value)) {
                            result += currentValue;
                            operations.push(`+ $${currentValue}`);
                        }
                        break;
                    case this.constAbsoluteDecrease:
                        if (!isNaN(input.value)) {
                            result -= currentValue;
                            operations.push(`- $${currentValue}`);
                        }
                        break;
                    case this.constPercentageIncrease:
                        if (!isNaN(input.value)) {
                            result *= 1 + currentValue / 100;
                            operations.push(`+ ${currentValue}%`);
                        }
                        break;
                    case this.constPercentageDecrease:
                        if (!isNaN(input.value)) {
                            result *= 1 - currentValue / 100;
                            operations.push(`- ${currentValue}%`);
                        }
                        break;
                }
            }.bind(this));

            const calculationExampleElement = $('fixed_price_calculation_example');
            if (operations.length <= 1) {
                calculationExampleElement.hide();
                return;
            }

            calculationExampleElement.show();
            calculationExampleElement.innerHTML = 'Ex. ' + operations.join(' ') + ' = '
                    + this.formatPrice(Math.round(result * 100) / 100, '$');

            if (result <= 0) {
                calculationExampleElement.style.color = 'red';
            } else {
                calculationExampleElement.style.color = 'black';
            }
        },

        formatPrice: function (price, currency) {
            if (isNaN(price)) {
                return currency + 0;
            }

            if (price >= 0) {
                return currency + price;
            } else {
                return '-' + currency + -price;
            }
        }


        // ---------------------------------------
    });
});
