define([
    'mage/translate',
    'M2ECore/Plugin/Messages',
], function($t, MessagesObj) {

    window.TemuTemplateShipping = Class.create({

        selectedAccountId: null,
        shippingTemplateId: null,
        urlGetTemplates: '',

        initialize: function(config) {
            this.urlGetTemplates = config.urlGetTemplates;

            this.setAccountId($('account_id').value);
            this.setShippingTemplateId(config.shippingTemplateId);

            this.initObservers();
        },

        // ----------------------------------------

        initObservers: function() {
            const self = this;

            $('account_id').observe('change', function() {
                self.setAccountId($('account_id').value || self.selectedAccountId);
            });
        },

        hasAccountId: function() {
            return this.accountId !== null;
        },

        setAccountId: function(id) {
            this.accountId = parseInt(id) || null;

            if (this.hasAccountId()) {
                this.updateTemplates();
            }

            if (this.hasAccountId()) {
                jQuery('#refresh_templates').show();
                jQuery('.actions').show();
            }
        },

        getAccountId: function() {
            return this.accountId;
        },



        setShippingTemplateId: function(id) {
            this.shippingTemplateId = id || null;
        },

        hasShippingTemplateId: function() {
            return this.shippingTemplateId !== null;
        },

        getShippingTemplateId: function() {
            return this.shippingTemplateId;
        },

        // ----------------------------------------

        updateTemplates: function(isForce) {
            const self = this;
            MessagesObj.clear();

            new Ajax.Request(this.urlGetTemplates, {
                method: 'post',
                parameters: {
                    account_id: self.getAccountId(),
                    force: isForce ? 1 : 0
                },
                onSuccess: function(transport) {
                    const response = JSON.parse(transport.responseText);
                    if (response.result) {
                        self.renderDeliveryTemplates(
                                response.templates.each(function(template) {
                                    return {
                                        'id': template.id,
                                        'title': template.title,
                                    };
                                }),
                        );

                        return;
                    }

                    self.renderDeliveryTemplates([]);
                    MessagesObj.addError(response.message);
                },
            });
        },

        renderDeliveryTemplates: function(deliveryTemplates) {
            const select = jQuery('#shipping_template_id');
            select.find('option').remove();

            deliveryTemplates.each(function(deliveryTemplate) {
                select.append(new Option(deliveryTemplate.title, deliveryTemplate.id));
            });

            if (this.hasShippingTemplateId()) {
                select.val(this.getShippingTemplateId());
            }
        },

        // ----------------------------------------

        submitForm: function(formId, url, messageObj) {
            const form = jQuery('#' + formId);
            if (!form.validation() || !form.validation('isValid')) {
                return false;
            }

            const self = this;

            const formData = form.serialize(true);

            let result = false;
            new Ajax.Request(url, {
                method: 'post',
                asynchronous: false,
                parameters: formData,
                onSuccess: function(transport) {
                    const response = JSON.parse(transport.responseText);

                    if (response.result) {
                        result = true;

                        return;
                    }

                    messageObj.clear();
                    response.messages.each(function(message) {
                        messageObj.addError(message);
                    });
                },
            });

            return result;
        },
    });
});
