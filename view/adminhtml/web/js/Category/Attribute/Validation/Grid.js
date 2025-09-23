define([
    'jquery',
    'mage/storage',
    'mage/translate',
    'Temu/Plugin/ProgressBar',
    'Magento_Ui/js/modal/modal'
], function ($, storage, $t, ProgressBar, modal) {
    'use strict';

    return function (options) {

        const validator = {
            validateUrl: options.validate_url,
            reloadGridUrl: options.reload_grid_url,

            totalItems: 0,
            processedItems: 0,
            errorItems: 0,

            ProgressBar: null,
            isWaiterActive: false,
            isInProgress: true,

            startProcess: function (progressBar) {
                this.ProgressBar = progressBar;

                this.ProgressBar.reset();
                this.ProgressBar.setTitle($t('Validate Products'));
                this.ProgressBar.setStatus($t('Validation in process. Please wait...'));
                this.ProgressBar.show();

                this.updateProgressBar();
                this.sendRequest();
            },

            endProcess: function () {
                this.stopWaiter();

                this.ProgressBar.setPercents(100, 1);
                this.ProgressBar.setStatus(
                        this.errorItems > 0
                                ? $t('Validation completed with errors.')
                                : $t('Validation completed successfully.')
                );

                setTimeout(() => {
                    this.ProgressBar.hide();
                    if (options.grid_id) {
                        this.reloadGrid()
                    }
                }, 500);
            },

            sendRequest: function () {
                this.startWaiter();

                storage.get(this.validateUrl, {})
                        .done(this.processResponse.bind(this))
                        .fail(this.processError.bind(this));
            },

            processResponse: function (response) {
                this.isInProgress = !response.is_all_complete;
                this.totalItems = response.total_product_count;
                this.processedItems += response.processed_product_count;
                this.errorItems += response.error_product_count;

                this.updateProgressBar();

                if (this.isInProgress) {
                    setTimeout(this.sendRequest.bind(this), 500);
                    return;
                }

                this.endProcess();
            },

            processError: function (e) {
                console.error(e.responseText);
                this.endProcess();
            },

            // ----------------------------------------

            startWaiter: function () {
                if (this.isWaiterActive) {
                    return;
                }

                $("body").trigger('processStart');
                this.isWaiterActive = true;
            },

            stopWaiter: function () {
                if (!this.isWaiterActive) {
                    return;
                }

                $("body").trigger('processStop');
                this.isWaiterActive = false;
            },

            // ----------------------------------------

            updateProgressBar: function () {
                this.ProgressBar.setPercents(this.getProcessPercent(), 0);

                this.ProgressBar.setStatus(
                        $t('%1 of %2 products validated. Errors: %3')
                                .replace('%1', this.processedItems)
                                .replace('%2', this.totalItems)
                                .replace('%3', this.errorItems)
                );
            },

            getProcessPercent: function () {
                if (this.totalItems === 0) {
                    return 0;
                }
                return (this.processedItems / this.totalItems) * 100;
            },

            reloadGrid: function () {
                const gridContainer = $('#' + options.grid_id);

                storage.get(this.reloadGridUrl, {})
                        .done((html) => {
                            gridContainer.html(html);
                        })

            },

            reValidate: function (resetUrl) {
                storage.get(resetUrl, {})
                        .done(() => {
                            this.startProcess(new window.ProgressBar(options.progress_bar_id));
                        })
                        .fail(this.processError.bind(this))
            }
        };

        try {
            validator.startProcess(new window.ProgressBar(options.progress_bar_id));

            window.TemuCategoryAttributeValidatorObj = validator;
        } catch (e) {
            console.error(e);
        }
    };
});


