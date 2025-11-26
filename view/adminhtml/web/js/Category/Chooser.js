define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Temu/Plugin/Messages',
    'mage/translate',
    'Temu/Common',
    'Temu/Plugin/Magento/AttributeCreator'
], function (jQuery, modal, MessageObj, $t) {
    window.TemuCategoryChooser = Class.create(Common, {

        // ---------------------------------------

        region: null,
        accountId: null,

        categoryInfoBlockMessages: null,
        categoryChangeBlockMessages: null,
        modalHeaderBlockMessages: null,

        selectedCategory: {},
        selectedSpecifics: {},

        // ---------------------------------------

        resetSelectedData: function () {
            this.selectedCategory = {};
            this.selectedSpecifics = {};
        },

        initialize: function (region, accountId) {
            this.region = region;
            this.accountId = accountId;

            this.categoryInfoBlockMessages = Object.create(MessageObj);
            this.categoryInfoBlockMessages.setContainer('#category_info_messaged_container');

            this.categoryChangeBlockMessages = Object.create(MessageObj);
            this.categoryChangeBlockMessages.setContainer('#change_category_messaged_container');

            this.modalHeaderBlockMessages = Object.create(MessageObj);
            this.categoryChangeBlockMessages.setContainer('.modal-header');
        },

        initObservers: function () {
            const self = this;

            jQuery('#edit_category').on('click', function () {
                self.showEditCategoryPopUp();
            });

            jQuery('#edit_attributes').on('click', function () {
                self.editAttributes();
            });

            jQuery('#edit_title').on('click', function () {
                jQuery('.dictionary_title_wrapper').hide();
                jQuery('.dictionary_title_edit_wrapper').show();

                const currentTitle = jQuery('#dictionary_title').text();
                jQuery('#dictionary_title_input').val(currentTitle).focus();
            });

            jQuery('#cancel_save_title').on('click', function () {
                jQuery('.dictionary_title_edit_wrapper').hide();
                jQuery('.dictionary_title_wrapper').show();
            });

            jQuery('#save_title').on('click', () => {
                this.messagesClearOnModalHeaderBlock();
                const newTitle = jQuery('#dictionary_title_input').val();

                new Ajax.Request(Temu.url.get('category/changeTitle'), {
                    method: 'post',
                    parameters: {
                        dictionary_id: this.selectedCategory.dictionaryId,
                        title: newTitle,
                    },
                    onSuccess: (transport) => {
                        let response = transport.responseText.evalJSON();
                        console.log(response)
                        if (!response.success) {
                            this.messageAddErrorToModalHeaderBlock(response.message);
                            return;
                        }

                        this.selectedCategory.dictionaryTitle = newTitle;
                        jQuery('#dictionary_title').text(newTitle);
                        jQuery('.dictionary_title_edit_wrapper').hide();
                        jQuery('.dictionary_title_wrapper').show();
                    }
                });
            });
        },

        // ---------------------------------------

        getRegion: function () {
            return this.region;
        },

        getAccountId: function () {
            return this.accountId;
        },

        setSelectedCategory: function (category, dictionaryId) {
            this.selectedCategory.value = category;
            this.selectedCategory.dictionaryId = dictionaryId;
        },

        getSelectedCategory: function () {
            if (this.isCategorySelected()) {
                return this.selectedCategory;
            }

            return {
                value: '',
                path: '',
            };
        },

        // ---------------------------------------

        showEditCategoryPopUp: function () {
            this.messagesClearAll();
            const self = this;
            const selected = self.getSelectedCategory();

            new Ajax.Request(Temu.url.get('category/getChooserEditHtml'), {
                method: 'post',
                parameters: {
                    region: self.region,
                    account_id: self.accountId,
                    selected_value: selected.value,
                    selected_path: selected.path,
                    dictionary_id: selected.dictionaryId,
                    view_mode: 'with_tabs',
                },
                onSuccess: function (transport) {
                    self.openPopUp($t('Change Category'), transport.responseText);
                    self.renderRecent();

                    let categoryPathElement = $('selected_category_container').down('#selected_category_path');
                    categoryPathElement.innerHTML = self.cutDownLongPath(categoryPathElement.innerHTML.trim(), 130, '&gt;');
                }
            });
        },

        openPopUp: function (title, html) {
            const self = this;
            let chooserContainer = $('chooser_container');

            if (chooserContainer) {
                chooserContainer.remove();
            }

            $('html-body').insert({bottom: html});

            jQuery('#category_search').applyBindings();

            let content = jQuery('#chooser_container');

            modal({
                title: title,
                type: 'slide',
                buttons: [{
                    class: 'template_category_chooser_cancel',
                    text: $t('Cancel'),
                    click: function () {
                        self.cancelPopUp();
                    }
                }, {
                    class: 'action primary template_category_chooser_confirm',
                    text: $t('Confirm'),
                    click: function () {
                        self.confirmCategory();
                    }
                }]
            }, content);

            content.modal('openModal');
        },

        // ---------------------------------------

        cancelPopUp: function () {
            jQuery('#chooser_container').modal('closeModal');
        },

        // ---------------------------------------

        selectCategory: function (categoryId, dictionaryId = undefined) {
            this.messagesClearAll();
            const self = this;

            new Ajax.Request(Temu.url.get('category/getSelectedCategoryDetails'), {
                method: 'post',
                parameters: {
                    region: self.region,
                    category_id: categoryId,
                    dictionary_id: dictionaryId
                },
                onSuccess: function (transport) {
                    const response = transport.responseText.evalJSON();

                    self.selectedCategory = {
                        value: categoryId,
                        path: response.path,
                        dictionaryId: dictionaryId,
                    };

                    let pathElement = $('selected_category_path');
                    if (pathElement) {
                        let interfacePath = response.interface_path;
                        pathElement.setAttribute('title', interfacePath);
                        pathElement.innerHTML = self.cutDownLongPath(interfacePath, 130, '>');
                    }

                    let resetLink = $('category_reset_link');
                    if (resetLink) {
                        resetLink.show();
                    }
                }
            });
        },

        unSelectCategory: function () {

            this.selectedCategory = {};

            let selectedCategoryPath = $('selected_category_path'),
                    resetLink = $('category_reset_link');

            if (resetLink) {
                resetLink.hide();
            }

            if (selectedCategoryPath) {
                selectedCategoryPath.innerHTML = '<span style="color: grey; font-style: italic">'
                        + $t('Not Selected')
                        + '</span>';
            }
        },

        confirmCategory: function () {
            const self = this;

            self.messagesClearAll();

            if (self.isCategorySelected() && self.selectedCategory.value) {
                new Ajax.Request(Temu.url.get('category/getEditedCategoryInfo'), {
                    method: 'post',
                    parameters: {
                        region: self.region,
                        category_id: self.selectedCategory.value,
                        dictionary_id: self.selectedCategory.dictionaryId ?? null,
                    },
                    onSuccess: function (transport) {
                        let response = transport.responseText.evalJSON();

                        if (response.hasOwnProperty('success') && !response.success) {
                            self.messageAddErrorToCategoryInfoBlock(response.message);

                            return;
                        }

                        self.selectedCategory.dictionaryId = response.dictionary_id;
                        self.selectedCategory.dictionaryTitle = response.dictionary_title;
                        self.selectedCategory.is_all_required_attributes_filled = response.is_all_required_attributes_filled;
                        self.selectedCategory.path = response.path;

                        self.reload();
                    }
                });
            } else {
                self.messageAddErrorToCategoryChangeBlock($t('Please select a category to continue.'));

                return;
            }

            jQuery('#chooser_container').modal('closeModal');
        },

        reload: function () {
            if (!this.isCategorySelected()) {
                jQuery('#category_path').text('Not selected')
                this.unsetIsRequiredAttributes();
                jQuery('#attributes_wrapper').addClass('hidden');

                return;
            }

            if (this.selectedCategory.dictionaryTitle) {
                jQuery('#dictionary_title').text(this.selectedCategory.dictionaryTitle);
            }

            if (this.selectedCategory.path) {
                jQuery('#category_path').text(this.selectedCategory.path)
                jQuery('#attributes_wrapper').removeClass('hidden');
            }

            if (this.selectedCategory.dictionaryId) {
                this.getCountsOfSpecifics(this.selectedCategory.dictionaryId, function (used, total) {
                    jQuery('#attributes_counts').text(used + '/' + total);
                });
            } else {
                jQuery('#attributes_counts').text('0/0');
            }

            if (this.selectedCategory.is_all_required_attributes_filled) {
                this.unsetIsRequiredAttributes();
                return;
            }

            if (this.selectedCategory.is_all_required_attributes_filled) {
                this.unsetIsRequiredAttributes();
            } else {
                this.setIsRequiredAttributes();
            }
        },

        setIsRequiredAttributes: function () {
            jQuery('#attributes_required').removeClass('hidden');
        },

        unsetIsRequiredAttributes: function () {
            jQuery('#attributes_required').addClass('hidden');
        },

        attributesIsRequired: function () {
            return !jQuery('#attributes_required').hasClass('hidden');
        },

        isCategorySelected: function () {
            return Object.keys(this.selectedCategory).length !== 0;
        },

        isSpecificsSelected: function () {
            return Object.keys(this.selectedSpecifics).length !== 0;
        },

        getCountsOfSpecifics: function (dictionaryId, callback) {
            new Ajax.Request(Temu.url.get('category/getCountsOfAttributes'), {
                method: 'post',
                parameters: {
                    dictionary_id: dictionaryId,
                },
                onSuccess: function (transport) {
                    const counts = transport.responseText.evalJSON();
                    callback(counts.used, counts.total);
                }
            });
        },

        // ---------------------------------------

        renderRecent: function () {
            const self = this;

            if (!$('chooser_recent_table')) {
                return;
            }

            new Ajax.Request(Temu.url.get('category/getRecent'), {
                method: 'post',
                parameters: {
                    region: self.region,
                    selected_category: null
                },
                onSuccess: function (transport) {
                    const categories = transport.responseText.evalJSON();
                    let html = '';

                    if (transport.responseText.length > 2) {
                        // html += '<tr><td width="730px"></td><td width="70px"></td></tr>';
                        categories.each(function (category) {
                            if (!category.is_valid) {
                                return;
                            }

                            const selectLink = jQuery('<a>')
                                    .attr('href', 'javascript:void(0)')
                                    .attr('onclick', `TemuCategoryChooserObj.selectCategory('${category.category_id}', '${category.dictionary_id}')`)
                                    .text($t('Select'))
                                    .prop('outerHTML');

                            const crateCopyLink = jQuery('<a>')
                                    .attr('href', 'javascript:void(0)')
                                    .attr('onclick', `TemuCategoryChooserObj.selectCategory('${category.category_id}')`)
                                    .text($t('Create Copy'))
                                    .prop('outerHTML');

                            html += '<tr>';
                            html += `<td>${category.title}<br>${category.path}</td>`;
                            html += `<td style="width: 70px; text-align: center">${selectLink}</td>`;
                            html += `<td style="width: 125px; text-align: center">${crateCopyLink}</td>`;
                            html += '</tr>';

                        });
                    } else {
                        html += '<tr><td colspan="2" style="padding-left: 200px"><strong>' + $t('No saved Categories') + '</strong></td></tr>';
                    }

                    $('chooser_recent_table').innerHTML = html;
                }
            });
        },

        // ---------------------------------------

        cutDownLongPath: function (path, length, sep) {
            if (path.length > length && sep) {

                var parts = path.split(sep),
                        isNeedSeparator = false;

                var shortPath = '';
                parts.each(function (part, index) {
                    if ((part.length + shortPath.length) >= length) {

                        var lenDiff = (parts[parts.length - 1].length + shortPath.length) - length;
                        if (lenDiff > 0) {
                            shortPath = shortPath.slice(0, shortPath.length - lenDiff + 1);
                        }

                        shortPath = shortPath.slice(0, shortPath.length - 3) + '...';

                        shortPath += parts[parts.length - 1];
                        throw $break;
                    }

                    shortPath += part + (isNeedSeparator ? sep : '');
                    isNeedSeparator = true;
                });

                return shortPath;
            }

            return path;
        },

        // ---------------------------------------

        editAttributes: function () {
            const self = this;

            self.messagesClearAll();

            let selectedCategory = this.getSelectedCategory();

            new Ajax.Request(Temu.url.get('category/getCategoryAttributesHtml'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    dictionary_id: selectedCategory.dictionaryId,
                },
                onSuccess: function (transport) {
                    self.openSpecificsPopUp($t('Specifics'), transport.responseText);
                }.bind(this)
            });
        },

        openSpecificsPopUp: function (title, html) {
            const self = this;
            if ($('chooser_container_specific')) {
                $('chooser_container_specific').remove();
            }

            $('html-body').insert({bottom: html});

            const content = jQuery('#chooser_container_specific');

            modal({
                title: title,
                type: 'slide',
                buttons: [{
                    class: 'template_category_specific_chooser_cancel',
                    text: $t('Cancel'),
                    click: function () {
                        this.closeModal();
                    }
                }, {
                    class: 'action primary template_category_specific_chooser_reset',
                    text: $t('Reset'),
                    click: function () {
                        TemuTemplateCategorySpecificsObj.resetSpecifics();
                    }
                }, {
                    class: 'action primary template_category_specific_chooser_save',
                    text: $t('Save'),
                    click: function () {
                        self.confirmSpecifics();
                    }
                }]
            }, content);

            content.modal('openModal');
        },

        confirmSpecifics: function () {
            this.initFormValidation('#edit_specifics_form');
            if (!jQuery('#edit_specifics_form').valid()) {
                return;
            }

            const self = TemuCategoryChooserObj;

            this.selectedSpecifics = TemuTemplateCategorySpecificsObj.collectSpecifics();

            new Ajax.Request(Temu.url.get('category/saveCategoryAttributesAjax'), {
                method: 'post',
                parameters: {
                    dictionary_id: self.selectedCategory.dictionaryId,
                    attributes: JSON.stringify(this.selectedSpecifics),
                },
                onSuccess: function (transport) {

                    const response = transport.responseText.evalJSON();

                    self.messagesClearAll();
                    if (response.success) {
                        self.selectedCategory.is_all_required_attributes_filled = true;
                        self.messageAddSuccessToCategoryInfoBlock($t('Attributes was saved'));
                    } else {
                        if (response.messages && response.messages[0] && response.messages[0].error) {
                            self.messageAddErrorToCategoryInfoBlock(response.messages[0].error);
                        } else {
                            self.messageAddErrorToCategoryInfoBlock($t('Attributes not saved'));
                        }
                    }

                    self.reload();
                    jQuery('#chooser_container_specific').modal('closeModal');
                }
            });
        },

        resetSpecificsToDefault: function () {
            const self = TemuCategoryChooserObj,
                    selectedCategory = this.getSelectedCategory();

            new Ajax.Request(Temu.url.get('category/getSelectedCategoryDetails'), {
                method: 'post',
                parameters: {
                    region: self.region,
                    value: selectedCategory.value,
                },
                onSuccess: function (transport) {

                    self.selectedSpecifics = {};

                    self.reload();
                }
            });
        },

        // ----------------------------------------

        messagesClearAll: function () {
            this.messagesClearOnCategoryInfoBlock();
            this.messagesClearOnCategoryChangeBlock();
            this.messagesClearOnModalHeaderBlock();
        },

        // ----------------------------------------

        messagesClearOnCategoryInfoBlock: function () {
            this.categoryInfoBlockMessages.clearAll();
        },

        messagesClearOnCategoryChangeBlock: function () {
            this.categoryChangeBlockMessages.clearAll();
        },

        messagesClearOnModalHeaderBlock: function () {
            this.categoryChangeBlockMessages.clearAll();
        },

        messageAddErrorToCategoryInfoBlock: function (message) {
            this.messagesClearOnCategoryInfoBlock();
            this.categoryInfoBlockMessages.addError(message);
        },

        messageAddSuccessToCategoryInfoBlock: function (message) {
            this.messagesClearOnCategoryInfoBlock();
            this.categoryInfoBlockMessages.addSuccess(message);
        },

        messageAddErrorToCategoryChangeBlock: function (message) {
            this.messagesClearOnCategoryChangeBlock();
            this.categoryChangeBlockMessages.addError(message);
        },

        messageAddErrorToModalHeaderBlock: function (message) {
            this.categoryChangeBlockMessages.addError(message);
        }
    });
});
