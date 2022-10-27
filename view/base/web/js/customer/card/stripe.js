define([
    'jquery',
    'TNW_Stripe/js/validator',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate',
    'stripejs',
    'jquery-ui-modules/widget'
], function ($, validator, alert, confirm, $t) {

    $.widget('tnw.stripeStoredCards', {
        options: {
            clientConfig: {},
            customerEmail: null
        },
        apiClient: null,
        paymentToken: null,
        threeDsActive: null,
        stripeCardNumber: null,
        additionalData: {},

        _create: function () {
            validator.setConfig(this.options.clientConfig)
            this.initHostedFields()
            this.bind()
        },

        bind: function () {
            this.element.find('[data-action=save-cc]').click(this.submit.bind(this))
            this.element.find('.stored-cards-item-actions .action-delete').click(this.deleteCard.bind(this))
        },

        submit: function () {
            var self = this
            $('body').trigger('processStart');

            this.createPaymentMethod('card', this.stripeCardNumber).done(function (response) {
                let card = response.paymentMethod.card

                self.additionalData = {
                    cc_exp_month: response.paymentMethod.card.exp_month,
                    cc_exp_year: response.paymentMethod.card.exp_year,
                    cc_last4: response.paymentMethod.card.last4,
                    cc_type: response.paymentMethod.card.brand
                }

                if (!card.three_d_secure_usage.supported) {
                    self.paymentToken = response.paymentMethod.id
                    self.saveToken()
                    $('body').trigger('processStop')
                    return
                }

                self.createPaymentIntent({
                    paymentMethod: response.paymentMethod,
                    amount: 1,
                    customerEmail: self.options.customerEmail,
                    currency: self.options.clientConfig.currencyCode
                }).done(function (response) {
                    if (response.skip_3ds) {
                        $('body').trigger('processStop');
                        self.paymentToken = response.paymentIntent.id
                        self.saveToken()
                        return
                    }
                    // Disable Payment Token
                    if (!response.pi) {
                        $('body').trigger('processStop')
                        return
                    }
                    self.authenticateCustomer(response.pi, function (error, response) {
                        if (error) {
                            self.error("3D Secure authentication failed.");
                        } else {
                            self.paymentToken = response.paymentIntent.id
                            self.threeDsActive = 1
                            self.saveToken()
                        }
                        $('body').trigger('processStop');
                    });
                }).fail(function () {
                    $('body').trigger('processStop');
                    self.error('Something went wrong')
                });
            })
            .fail(function () {
                self.error('Something went wrong')
                $('body').trigger('processStop')
            })
        },

        getApiClient: function () {
            if (!this.apiClient) {
                this.apiClient = Stripe(this.options.clientConfig.publishableKey);
            }

            return this.apiClient;
        },

        initHostedFields: function () {
            var stripeCardElement = this.getApiClient().elements(),
                style = {
                    base: {
                        fontSize: '17px'
                    }
                };

            this.stripeCardNumber = stripeCardElement.create('cardNumber', {style: style});
            this.stripeCardNumber.mount('#tnw_stripe_cc_number');
            this.stripeCardNumber.on('change', this.onFieldEvent.bind(this));

            stripeCardElement.create('cardExpiry', {style: style}).mount('#tnw_stripe_cc_exp');
            stripeCardElement.create('cardCvc', {style: style}).mount('#tnw_stripe_cc_cid');
        },

        /**
         * Triggers on Hosted Field changes
         * @param {Object} event
         */
        onFieldEvent: function (event) {
            // self.isValidCardNumber = event.complete;
            $('#tnw_stripe_cc_type').val(
                validator.getMageCardType(event.brand, this.options.clientConfig.availableCardTypes)
            );
        },

        /**
         * create payment method
         * @return {jQuery.Deferred}
         */
        createPaymentMethod: function () {
            var self = this,
                dfd = $.Deferred();

            this.getApiClient().createPaymentMethod.apply(this.getApiClient(), arguments)
            .then(function (response) {
                if (response.error) {
                    self.error(response.error.message);
                    dfd.reject(response);
                } else {
                    dfd.resolve(response);
                }
            });

            return dfd;
        },

        /**
         * Create payment intent
         * @returns {jQuery.Deferred}
         */
        createPaymentIntent: function () {
            this.isCreatingPaymentIntent = true;
            var self = this,
                dfd = $.Deferred();
            arguments[0].vaultEnabled = true;
            $.post(
                self.options.clientConfig.createUrl,
                {data: JSON.stringify(arguments[0])}
            ).then(function (response) {
                if (response.error) {
                    self.error(response.error.message);
                    dfd.reject(response);
                } else {
                    dfd.resolve(response);
                }
            }).always(function () {
                self.isCreatingPaymentIntent = false;
            });
            return dfd;
        },

        /**
         * Authenticate customer
         * @param paymentIntentId
         * @param done
         */
        authenticateCustomer: function (paymentIntentId, done) {
            var self = this
            try {
                this.getApiClient().retrievePaymentIntent.apply(this.getApiClient(), [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error, result);
                    }
                    if (result.paymentIntent.status === "requires_action"
                        || result.paymentIntent.status === "requires_source_action") {
                        return self.handleCardAction(paymentIntentId, done);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },

        /**
         * Run 3DS check
         * @param paymentIntentId
         * @param done
         */
        handleCardAction: function (paymentIntentId, done) {
            try {
                this.getApiClient().handleCardAction.apply(this.getApiClient(), [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error.message, result);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },

        /**
         * Save payment token
         */
        saveToken: function () {
            $.post(
                this.options.clientConfig.saveCustomerCardUrl,
                {
                    token: this.paymentToken,
                    additionalData: this.additionalData,
                    threeDsActive: this.threeDsActive
                }
            )
            .done(function (response) {
                $('body').trigger('processStop')
                if (response.success === true) {
                    window.location.reload()
                } else {
                    this.error(response.message)
                }
            }.bind(this))
            .fail(function () {
                $('body').trigger('processStop')
                this.error('Something went wrong')
            }.bind(this))
        },

        /**
         * Show alert message
         * @param {String} message
         */
        error: function (message) {
            alert({
                content: message
            });
        },

        /**
         * Delete stored card
         * @param event
         */
        deleteCard: function (event) {
            event.preventDefault();
            var form = $(event.target).parent()

            confirm({
                title: $t("Attention"),
                content: $t("Are you sure you want to delete this?"),
                actions: {
                    confirm: function () {
                        $.post(
                            form.attr('action'),
                            form.serialize(),
                            function (event, data) {
                                if (data.success) {
                                    $(event.target).closest('li').slideUp();
                                } else if (typeof data.message != 'undefined') {
                                    this.error(data.message)
                                }
                            }.bind(this, event)
                        );
                    }.bind(this)
                }
            })
        },
    })

    return $.tnw.stripeStoredCards;
})
