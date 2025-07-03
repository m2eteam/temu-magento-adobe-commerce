define([
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'Temu/Common',
    'mage/adminhtml/wysiwyg/tiny_mce/setup'
], function ($t) {
    window.TemuTemplateDescription = Class.create(Common, {

        descriptionModeCustomValue: 0,

        currentBulletPointIndex: 0,

        bulletPointModeCustomValue: 1,
        bulletPointModeCustomAttribute: 2,
        bulletPointMaxCount: 0,

        // ---------------------------------------

        initialize: function(
                descriptionModeCustomValue,
                bulletPointModeCustomValue,
                bulletPointModeCustomAttribute,
                bulletPointsaMaxCount,
        ) {
            this.descriptionModeCustomValue = descriptionModeCustomValue;
            this.bulletPointModeCustomValue = bulletPointModeCustomValue;
            this.bulletPointModeCustomAttribute = bulletPointModeCustomAttribute;
            this.bulletPointMaxCount = bulletPointsaMaxCount;

            self = this;

            jQuery.validator.addMethod('Temu-validate-description-mode', function(value, el) {

                if (value === '-1') {
                    return false;
                }

                return Validation.get('required-entry').test(value, el);
            }, $t('This is a required field.'));

            jQuery.validator.addMethod('Temu-validate-description-template', function(value, el) {

                if ($('description_mode').value != self.descriptionModeCustomValue) {
                    return true;
                }

                return Validation.get('required-entry').test(value, el);
            }, $t('This is a required field.'));

            jQuery.validator.addMethod('Temu-validate-magento-product-id', function(value) {

                var isValidMagentoProductId = false;

                new Ajax.Request(Temu.url.get('policy_description/checkMagentoProductId'), {
                    method: 'post',
                    asynchronous: false,
                    parameters: {
                        product_id: value,
                    },
                    onSuccess: function(transport) {
                        var response = transport.responseText.evalJSON();
                        isValidMagentoProductId = response.result;
                    },
                });

                return isValidMagentoProductId;
            }, $t('Please enter a valid Magento product ID.'));
        },

        initObservers: function () {
            let self = this;

            $('image_main')
                    .observe('change', function () {
                        self.updateHiddenValue(this, $('image_main_attribute'))
                    })
                    .simulate('change');

            $('gallery_images')
                    .observe('change', self.gallery_images_change)
                    .simulate('change');

            $('title_mode')
                    .observe('change', TemuTemplateDescriptionObj.title_mode_change)
                    .simulate('change');

            $('description_mode')
                    .observe('change', this.description_mode_change.bind(this))
                    .simulate('change');

            $('custom_inserts_open_popup')
                    .observe('click', TemuTemplateDescriptionObj.customInsertsOpenPopup);


            $('description_template_tr')
                    .down('.admin__field-control')
                    .down('.admin__field')
                    .appendChild($('description_template_buttons'));

            $$(".bullet-point-mode-selector").each(function (element) {
                element.observe('change', self.bulletPointModeChange.bind(self));
                element.simulate('change');
            });

            this.initCustomInsertsPopup();
            this.initPreviewPopup();
        },

        // ---------------------------------------

        gallery_images_change: function () {
            let galleryImagesInput = this;
            let galleryImagesInputValue = parseInt(galleryImagesInput.value);
            let attributeCodeInput = $('gallery_images_attribute');
            let imagesLimitInput = $('gallery_images_limit');

            // None
            if (galleryImagesInputValue === 0) {
                attributeCodeInput.value = null;
                imagesLimitInput.value = null;
            }

            // Up to ...
            if (galleryImagesInputValue === 1) {
                attributeCodeInput.value = null;
                imagesLimitInput.value = galleryImagesInput
                        .options[galleryImagesInput.selectedIndex]
                        .getAttribute('attribute_code');
            }

            // Attribute
            if (galleryImagesInputValue === 2) {
                attributeCodeInput.value = galleryImagesInput
                        .options[galleryImagesInput.selectedIndex]
                        .getAttribute('attribute_code');
                imagesLimitInput.value = null;
            }
        },

        title_mode_change: function () {
            var self = TemuTemplateDescriptionObj;
            self.setTextVisibilityMode(this, 'custom_title_tr');
        },

        description_mode_change: function (event) {
            const element = event.target;
            if (element.value !== '-1' && element.options[0].value === '-1') {
                element.removeChild(element.options[0]);
            }

            const viewEditCustomDescription = $('view_edit_custom_description');

            if (viewEditCustomDescription) {
                viewEditCustomDescription.hide();
            }

            $$('.c-custom_description_tr').invoke('hide');

            if (element.value == this.descriptionModeCustomValue) {
                if (viewEditCustomDescription) {
                    viewEditCustomDescription.show();
                    $$('.c-custom_description_tr').invoke('hide');
                    return;
                }

                $$('.c-custom_description_tr').invoke('show');
            } else {
                if (viewEditCustomDescription) {
                    viewEditCustomDescription.remove();
                }
            }
        },

        bulletPointModeChange: function (event) {
            const element = event.target;
            const container = element.parentElement;
            let selectedValue = element.value;

            const customValueEl = container.querySelector('.bullet_point_custom_value');
            const attributeValueEl = container.querySelector('.bullet_point_attribute');

            this.setBulletPointMode(0, customValueEl);
            this.setBulletPointMode(0, attributeValueEl);

            if (selectedValue == this.bulletPointModeCustomValue) {
                this.setBulletPointMode(selectedValue, customValueEl);
            }

            if (selectedValue == this.bulletPointModeCustomAttribute) {
                this.setBulletPointMode(selectedValue, attributeValueEl);
            }
        },

        setBulletPointMode: function (value, element) {
            if (!element) {
                return;
            }

            if (
                value == this.bulletPointModeCustomValue ||
                value == this.bulletPointModeCustomAttribute
            ) {
                element.show();
            } else {
                element.hide();
            }
        },

        showNextBulletPoint: function () {
            if (this.isBulletPointCountMax()) {
                this.hideBulletPointAddMoreButton();

                return;
            }

            const newIndex = this.getBulletPointsCount();

            const anyRow = jQuery(this.getLastBulletPointRow());

            const parentContainer = anyRow.parent().parent();

            const newRow = anyRow.parent().clone(true, true);
            newRow.css('margin-top', '30px');
            // ----------------------------------------
            newRow.find('.admin__field-tooltip').remove();
            newRow.find('#add_bullet_point_button').remove();

            newRow.find('[id]').each(function() {
                jQuery(this).removeAttr('id');
            });
            // ----------------------------------------
            newRow.find('[name]').each(function() {
                const name = jQuery(this).attr('name');
                const newName = name.replace(/\[bullet_point\]\[(\d+)\]/, function(str, p1) {
                    return `[bullet_point][${newIndex}]`;
                });
                jQuery(this).attr('name', newName);
            });
            // ----------------------------------------

            const newSelect = newRow.children().first();
            newSelect.val(0);

            newRow.find('.bullet_point_custom_value').val('');
            newRow.find('.bullet_point_attribute').val('');

            // ----------------------------------------

            parentContainer.append(newRow);

            newSelect.on('change', this.bulletPointModeChange.bind(this));
            newSelect.trigger('change');

            // ----------------------------------------

            if (this.isBulletPointCountMax()) {
                this.hideBulletPointAddMoreButton();
            }
        },

        getLastBulletPointRow: function () {
            return $$(".bullet-point-mode-selector").last();
        },

        isBulletPointCountMax: function () {
            return this.getBulletPointsCount() >= this.bulletPointMaxCount;
        },

        getBulletPointsCount: function () {
            return $$(".bullet-point-mode-selector").length;
        },

        hideBulletPointAddMoreButton: function () {
            const button = $('add_bullet_point_button');
            if (button) {
                button.style.display = 'none';
            }
        },

        view_edit_custom_change: function () {
            $$('.c-custom_description_tr').invoke('show');
            $('view_edit_custom_description').hide();
        },

        setTextVisibilityMode: function (obj, elementName) {
            var elementObj = $(elementName);

            if (!elementObj) {
                return;
            }

            elementObj.hide();

            if (obj.value == 1) {
                elementObj.show();
            }
        },

        // ---------------------------------------

        initCustomInsertsPopup: function () {
            var popup = jQuery('#custom_inserts_popup');
            if (!popup.find('form').length) {
                popup.wrapInner('<form id="description_custom_inserts_form"></form>');
                CommonObj.initFormValidation('#description_custom_inserts_form');
            }

            popup.modal({
                title: $t('Custom Insertions'),
                type: 'slide',
                buttons: [],
                closed: function () {
                    TemuTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            });
        },

        customInsertsOpenPopup: function () {
            jQuery('#custom_inserts_popup').modal('openModal');
        },

        customInsertsOnClosePopup: function () {
            jQuery('#description_custom_inserts_form').trigger('reset').validate().resetForm();
        },

        // ---------------------------------------

        customInsertsClosePopup: function (callback) {
            jQuery('#custom_inserts_popup').modal({
                closed: function () {
                    callback && callback();

                    // prevent callback closure
                    callback = undefined;

                    TemuTemplateDescriptionObj.customInsertsOnClosePopup();
                }
            }).modal('closeModal');
        },

        insertProductAttribute: function () {
            var self = this;

            self.customInsertsClosePopup(function () {
                self.appendToTextarea('#' + $('custom_inserts_product_attribute').value + '#');
            });
        },

        insertTemuAttribute: function () {
            var self = this;

            self.customInsertsClosePopup(function () {
                self.appendToTextarea('#value[' + $('custom_inserts_temu_attribute').value + ']#');
            });
        },

        // ---------------------------------------

        initPreviewPopup: function () {
            var popup = jQuery('#description_preview_popup');
            if (!popup.find('form').length) {
                popup.wrapInner(new Element('form', {
                    id: 'description_preview_form',
                    method: 'post',
                    target: '_blank',
                    action: Temu.url.get('policy_description/preview')
                }));
                this.initFormValidation('#description_preview_form');
            }

            popup.modal({
                title: $t('Description Preview'),
                type: 'popup',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: $t('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        if (!jQuery('#description_preview_form').valid()) {
                            return;
                        }

                        $('description_preview_description_mode').value = $('description_mode').value;
                        $('description_preview_description_template').value = $('description_template').value;

                        $('description_preview_form').submit();

                        this.closeModal(event);
                    }
                }],
                closed: function () {
                    jQuery('#description_preview_form').trigger('reset').validate().resetForm();
                }
            });
        },

        openPreviewPopup: function () {
            if (
                    $('description_mode').value == this.descriptionModeCustomValue
                    && !$('description_template').value.length
            ) {
                this.alert($t('Please enter Description Value.'));

                return;
            }

            jQuery('#description_preview_popup').modal('openModal');
        },

        selectProductIdRandomly: function () {
            var self = this;

            new Ajax.Request(Temu.url.get('policy_description/getRandomMagentoProductId'), {
                method: 'post',
                parameters: {
                    store_id: $('description_preview_store_id').value
                },
                onSuccess: function (transport) {
                    var response = transport.responseText.evalJSON();

                    if (response.success) {
                        $('description_preview_magento_product_id').value = response.product_id;
                    } else {
                        self.alert(response.message);
                    }
                }
            });
        },

        appendToTextarea: function (value) {
            if (value == '') {
                return;
            }

            if (typeof tinymce != 'undefined' && typeof tinymce.get('description_template') != 'undefined'
                    && tinymce.get('description_template') != null) {

                var data = tinymce.get('description_template').getContent();
                tinymce.get('description_template').setContent(data + value);

                return;
            }

            var element = $('description_template');

            if (document.selection) {

                /* IE */
                element.focus();
                document.selection.createRange().text = value;
                element.focus();

            } else if (element.selectionStart || element.selectionStart == '0') {

                /* Webkit */
                var startPos = element.selectionStart;
                var endPos = element.selectionEnd;
                var scrollTop = element.scrollTop;
                element.value = element.value.substring(0, startPos) + value + element.value.substring(endPos, element.value.length);
                element.focus();
                element.selectionStart = startPos + value.length;
                element.selectionEnd = startPos + value.length;
                element.scrollTop = scrollTop;

            } else {

                element.value += value;
                element.focus();
            }
        }
        // ---------------------------------------
    });
});
