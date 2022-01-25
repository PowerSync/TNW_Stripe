define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/modal/alert'
], function ($, Class, alert) {
    'use strict';

    return Class.extend({
        defaults: {
            $selector: null,
            selector: 'edit_form',
            $container: null,
            createUrl: false,
            scriptLoaded: false,
            stripe: null
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            var self = this;

            self.$selector = $('#' + self.selector);
            self.$container =  $('#' + self.container);
            self.$selector.on(
                'setVaultNotActive.' + self.getCode(),
                function () {
                    self.$selector.off('submitOrder.' + self.getCode());
                }
            );

            this._super().observe([
                'scriptLoaded'
            ]);

            this.initEventHandlers();

            return self;
        },

        /**
         * Get payment code
         * @returns {String}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Init event handlers
         */
        initEventHandlers: function () {
            $('#' + this.container).find('[name="payment[token_switcher]"]')
            .on('click', this.selectPaymentMethod.bind(this));
            if ($('#' + this.container).find('[name="payment[token_switcher]"]:checked').val() === 'on'
                && $('#p_method_' + this.getCode()).attr('checked') === 'checked'
            ) {
                this.selectPaymentMethod()
            }
        },

        /**
         * Select current payment token
         */
        selectPaymentMethod: function () {
            this.disableEventListeners();
            this.enableEventListeners();
        },

        /**
         * Enable form event listeners
         */
        enableEventListeners: function () {
            if ($('#payment_form_braintree').length) {
                /**
                 * Workaround for braintree.js, which attaches submitOrder event when disabled,
                 * if loaded after stripe/vault.js and stripe vault is default
                 */
                setTimeout(function () {
                    this.disableEventListeners();
                    this.$selector.on('submitOrder.' + this.getCode(), this.submitOrder.bind(this));
                }.bind(this), 1000)
            }
            this.$selector.on('submitOrder.' + this.getCode(), this.submitOrder.bind(this));
            if (!this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Disable form event listeners
         */
        disableEventListeners: function () {
            this.$selector.off('submitOrder');
        },

        /**
         * Store payment details
         * @param {String} nonce
         */
        setPaymentDetails: function (nonce) {
            this.createPublicHashSelector();

            this.$selector.find('[name="payment[public_hash]"]').val(this.publicHash);
            this.$selector.find('[name="payment[payment_method_nonce]"]').val(nonce).prop('disabled', false);
        },

        /**
         * Creates public hash selector
         */
        createPublicHashSelector: function () {
            var $input;

            if (this.$selector.find('[name="payment[payment_method_nonce]"]').size() === 0) {
                $input = $('<input>').attr(
                    {
                        type: 'hidden',
                        id: 'nonce_' + this.getCode(),
                        name: 'payment[payment_method_nonce]'
                    }
                );

                $input.appendTo(this.$selector);
                $input.prop('disabled', false);
            }
        },

        /**
         * Pre submit for order
         * @returns {Boolean}
         */
        submitOrder: function () {
            this.$selector.validate().form();
            this.$selector.trigger('afterValidate.beforeSubmit');
            $('body').trigger('processStop');

            // validate parent form
            if (this.$selector.validate().errorList.length) {
                return false;
            }
            this.getPaymentIntent();
        },

        /**
         * Place order
         */
        placeOrder: function () {
            this.$selector.trigger('realOrder');
        },

        /**
         * Get payment intent from public hash, run 3DS check if needed and place order
         */
        getPaymentIntent() {
            var self = this,
                data = {
                    public_hash: this.publicHash,
                    currency : $('#currency_switcher').val(),
                    amount : $('#grand-total .price').text().replace(/([^0-9.])/g, '')
                }

            $('body').trigger('processStart');

            $.getJSON(this.createUrl, {
                data: JSON.stringify(data)
            }).done(function (response) {
                if (response.skip_3ds) {
                    $('body').trigger('processStop');
                    self.placeOrder();
                    return;
                }
                // Disable Payment Token
                if (!response.pi) {
                    $('body').trigger('processStop');
                    return;
                }
                self.authenticateCustomer(response.pi, function (error, response) {
                    if (error) {
                        self.error("3D Secure authentication failed.");
                    } else {
                        self.setPaymentDetails(response.paymentIntent.id);
                        self.placeOrder();
                    }
                    $('body').trigger('processStop');
                });
            }).fail(function () {
                self.error('Something went wrong');
            }).always(function () {
                $('body').trigger('processStop');
            })
        },

        /**
         * Load external Stripe SDK
         */
        loadScript: function () {
            var self = this;

            $('body').trigger('processStart');
            require(['https://js.stripe.com/v3/'], function () {
                self.scriptLoaded(true);
                self.stripe = window.Stripe(self.publishableKey);
                $('body').trigger('processStop');
            });
        },

        /**
         * Run 3DS check
         * @param paymentIntentId
         * @param done
         */
        handleCardAction: function (paymentIntentId, done) {
            try {
                this.stripe.handleCardAction.apply(this.stripe, [paymentIntentId]).then(function (result) {
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
         * Authenticate customer
         * @param paymentIntentId
         * @param done
         */
        authenticateCustomer: function (paymentIntentId, done) {
            var self = this
            try {
                this.stripe.retrievePaymentIntent.apply(this.stripe, [paymentIntentId]).then(function (result) {
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
         * Show alert message
         * @param {String} message
         */
        error: function (message) {
            alert({
                content: message
            });
        }
    });
});
