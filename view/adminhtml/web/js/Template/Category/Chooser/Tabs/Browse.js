define([
    'M2ECore/Plugin/Messages',
    'Temu/Common'
], function (messages) {
    window.TemuTemplateCategoryChooserTabsBrowse = Class.create(Common, {

        // ---------------------------------------

        initialize: function () {
            this.messages = Object.create(messages);
            this.messages.setContainer('#chooser_container');

            this.region = null;
            this.accountId = null;
            this.observers = {
                "leaf_selected": [],
                "not_leaf_selected": [],
                "any_selected": []
            };
        },

        // ---------------------------------------

        setRegion: function (region) {
            this.region = region;
        },

        getRegion: function () {
            if (this.region === null) {
                alert('You must set Region');
            }

            return this.region;
        },

        //----------------------------------------

        setAccountId: function (accountId) {
            this.accountId = accountId;
        },

        getAccountId: function () {
            if (this.accountId === null) {
                alert('You must set Account');
            }

            return this.accountId;
        },

        //----------------------------------------

        getCategoriesSelectElementId: function (categoryId) {
            if (categoryId === null) categoryId = 0;
            return 'category_chooser_select_' + categoryId;
        },

        getCategoryChildrenElementId: function (categoryId) {
            if (categoryId === null) categoryId = 0;
            return 'category_chooser_children_' + categoryId;
        },

        getSelectedCategories: function () {
            var self = TemuTemplateCategoryChooserTabsBrowseObj;

            var categoryId = 0;
            var selectedCategories = [];
            var isLastCategory = false;

            while (!isLastCategory) {
                var categorySelect = $(self.getCategoriesSelectElementId(categoryId));
                if (!categorySelect || categorySelect.selectedIndex == -1) {
                    break;
                }

                categoryId = categorySelect.options[categorySelect.selectedIndex].value;
                const isLeaf = categorySelect.options[categorySelect.selectedIndex].getAttribute('is_leaf');
                const invaiteOnly = categorySelect.options[categorySelect.selectedIndex].getAttribute('data-invite_only');
                selectedCategories[selectedCategories.length] = {
                    'value': categoryId,
                    'invite_only': invaiteOnly,
                }

                if (isLeaf == 1) {
                    isLastCategory = true;
                }
            }

            return selectedCategories;
        },

        // ---------------------------------------

        renderTopLevelCategories: function (containerId) {
            this.prepareDomStructure(null, $(containerId));
            this.renderChildCategories(null);
        },

        renderChildCategories: function (parentCategoryId) {
            let self = this;

            this.messages.clearAll();

            new Ajax.Request(Temu.url.get('category/getChildCategories'), {
                method: 'post',
                asynchronous: true,
                parameters: {
                    "parent_category_id": parentCategoryId,
                    "region": self.getRegion()
                },
                onSuccess: (transport)=> {
                    const response = JSON.parse(transport.responseText);
                    if (!response.success) {
                        this.messages.addError(response.message);

                        return;
                    }

                    let optionsHtml = '';
                    response.categories.each(function (category) {
                        let title   = category.title

                        if (parseInt(category.invite_only) === 1) {
                            title += ' [INVITE ONLY]'
                        }

                        if (parseInt(category.is_leaf) === 0) {
                            title += ' >'
                        }

                        optionsHtml += `<option data-invite_only="${category.invite_only}" is_leaf="${category.is_leaf}" value="${category.category_id}">`;
                        optionsHtml += title;
                        optionsHtml += '</option>';
                    });

                    $(self.getCategoriesSelectElementId(parentCategoryId)).innerHTML = optionsHtml;
                    $(self.getCategoriesSelectElementId(parentCategoryId)).style.display = 'inline-block';
                    $('chooser_browser').scrollLeft = $('chooser_browser').scrollWidth;
                },
            });
        },

        onSelectCategory: function (select) {
            var self = TemuTemplateCategoryChooserTabsBrowseObj;

            var parentCategoryId = select.id.replace(self.getCategoriesSelectElementId(""), "");
            var categoryId = select.options[select.selectedIndex].value;
            var is_leaf = select.options[select.selectedIndex].getAttribute('is_leaf');
            const invite_only = select.options[select.selectedIndex].getAttribute('data-invite_only');

            var selectedCategories = self.getSelectedCategories();

            var parentDiv = $(self.getCategoryChildrenElementId(parentCategoryId));
            parentDiv.innerHTML = '';

            if (invite_only == 1) {
                jQuery('#chooser_browser-message_wrapper .invite-only-notification').show()
            } else {
                jQuery('#chooser_browser-message_wrapper .invite-only-notification').hide()
            }

            self.simulate('any_selected', selectedCategories);

            if (is_leaf == 1) {
                self.simulate('leaf_selected', selectedCategories);
                return;
            }

            self.simulate('not_leaf_selected', selectedCategories);

            self.prepareDomStructure(categoryId, parentDiv);
            self.renderChildCategories(categoryId);
        },

        prepareDomStructure: function (categoryId, parentDiv) {
            var self = TemuTemplateCategoryChooserTabsBrowseObj;

            var childrenSelect = document.createElement('select');
            childrenSelect.id = self.getCategoriesSelectElementId(categoryId);
            childrenSelect.style.minWidth = '200px';
            childrenSelect.style.maxHeight = 'none';
            childrenSelect.size = 10;
            childrenSelect.className = 'multiselect admin__control-multiselect';
            childrenSelect.onchange = function () {
                TemuTemplateCategoryChooserTabsBrowseObj.onSelectCategory(this);
            };
            childrenSelect.style.display = 'none';
            parentDiv.appendChild(childrenSelect);

            var childrenDiv = document.createElement('div');
            childrenDiv.id = self.getCategoryChildrenElementId(categoryId);
            childrenDiv.className = 'category-children-block';
            parentDiv.appendChild(childrenDiv);
        },

        // ---------------------------------------

        observe: function (event, observer) {
            var self = TemuTemplateCategoryChooserTabsBrowseObj;

            if (typeof observer != 'function') {
                self.alert('Observer must be a function!');
                return;
            }

            if (typeof self.observers[event] == 'undefined') {
                self.alert('Event does not supported!');
                return;
            }

            self.observers[event][self.observers[event].length] = observer;
        },

        simulate: function (event, parameters) {
            var self = TemuTemplateCategoryChooserTabsBrowseObj;

            parameters = parameters || null;

            if (typeof self.observers[event] == 'undefined') {
                self.alert('Event does not supported!');
                return;
            }

            if (self.observers[event].length == 0) {
                return;
            }

            self.observers[event].each(function (observer) {
                if (parameters == null) {
                    (observer)();
                } else {
                    (observer)(parameters);
                }
            });
        }

        // ---------------------------------------
    });
});
