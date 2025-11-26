define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/modal'
], function ($, $t, confirm, modal) {

    window.TemuCategoryEditTitle = Class.create({

        gridId: undefined,
        modal: undefined,
        oldTitle: undefined,

        // ---------------------------------------

        initialize: function (gridId) {
            this.gridId = gridId;
        },

        openPopup: function (id) {
            new Ajax.Request(Temu.url.get('category/changeTitleForm'), {
                parameters: {
                    dictionary_id: id,
                },
                onSuccess: (transport) => {
                    this.modal = this.createModal();
                    this.modal.html(transport.responseText);
                    this.oldTitle = this.modal.find('#category_title').val()
                    this.modal.modal('openModal');
                }
            })
        },

        saveListingTitle: function () {
            const form = this.modal.find('form')
            const newTitle = form.find('#category_title').val();
            if (this.oldTitle === newTitle) {
                this.modal.modal('closeModal');
                return;
            }

            if (!form.valid()) {
                return false;
            }

            confirm({
                content: $t('Are you sure?'),
                actions: {
                    confirm: () => {
                        new Ajax.Request(Temu.url.get('category/changeTitle'), {
                            parameters: form.serialize(true),
                            onSuccess: (transport) => {
                                this.modal.modal('closeModal');
                                window[this.gridId + 'JsObject'].reload();
                            }
                        });
                    },
                    cancel: () => {
                        this.modal.modal('closeModal');
                        return false;
                    }
                }
            });
        },

        createModal: function () {
            if (this.modal) {
                return this.modal;
            }

            let modalBlock = $('#edit_title_modal');
            if (modalBlock.length === 0) {
                modalBlock = $('<div id="edit_title_modal">')
                $('#html-body').prepend(modalBlock);
            }

            modalBlock.modal({
                title: $t('Edit Category Title'),
                type: 'popup',
                modalClass: 'width-50',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function () {
                        modalBlock.modal('closeModal');
                    }
                }, {
                    text: $t('Save'),
                    class: 'action-primary action-accept',
                    click: () => {
                        this.saveListingTitle();
                    }
                }]
            });

            return modalBlock;
        }
    });
});
