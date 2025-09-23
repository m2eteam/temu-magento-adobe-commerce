define([
    'jquery',
    'mage/translate',
    'Temu/Plugin/Storage'
], function ($, $t, localStorage) {
    'use strict';

    class TemuCategoryAttributeValidationPopup {
        constructor(config) {
            this.openUrl = config.modalOpenUrl;
            this.templateCategoryIdLocalStorageKey = 'specific_validate_category_id';
            this.closePopupCallback = undefined;
            this.closePopupCallbackArguments = [];

            let templateCategoryId = this.getAndRemoveTemplateCategoryId();
            if (templateCategoryId) {
                setTimeout(() => {
                    this.open(templateCategoryId);
                }, 100);
            }
        }

        setTemplateCategoryId(templateCategoryId) {
            localStorage.set(this.templateCategoryIdLocalStorageKey, templateCategoryId);
        }

        getAndRemoveTemplateCategoryId() {
            let value = localStorage.get(this.templateCategoryIdLocalStorageKey);
            localStorage.remove(this.templateCategoryIdLocalStorageKey);
            return value;
        }

        open(templateCategoryId) {
            const self = this;

            let $modal = $('#modal_temu_category_attribute_validation');
            if (!$modal.length) {
                $modal = $('<div>', {id: 'modal_temu_category_attribute_validation'});
                $('body').append($modal);
            } else {
                $modal.empty();
            }

            $.ajax({
                url: this.openUrl,
                type: 'POST',
                data: {
                    template_category_id: templateCategoryId
                },
                success: function (response) {
                    if (!response) {
                        self.executeClosePopupCallback();
                        return;
                    }

                    $modal.html(response);

                    window.CategoryAttributeValidation = $modal.modal({
                        title: $t('Category Attribute Validation'),
                        type: 'slide',
                        buttons: [],
                        modalCloseBtnHandler: function () {
                            self.executeClosePopupCallback();
                            window.temuGridInitialized = false;
                            $modal.modal('closeModal');
                        }
                    });

                    window.CategoryAttributeValidation.modal('openModal');
                }
            });
        }

        executeClosePopupCallback() {
            if (typeof this.closePopupCallback !== 'undefined') {
                setTimeout(() => {
                    this.closePopupCallback.apply(this, this.closePopupCallbackArguments);
                }, 1);
            }
        }
    }

    return TemuCategoryAttributeValidationPopup;
});
