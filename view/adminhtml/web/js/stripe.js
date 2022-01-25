/*browser:true*/
/*global define*/
define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'mage/translate',
    'TNW_Stripe/js/validator'
], function ($, Class, alert, domObserver, $t, validator) {
    'use strict';

    return Class.extend({

        defaults: {
            $selector: null,
            selector: 'edit_form',
            container: 'payment_form_tnw_stripe',
            active: false,
            scriptLoaded: false,
            stripe: null,
            token: null,
            selectedCardType: null,
            createUrl: false,
            imports: {
                onActiveChange: 'active'
            }
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            var self = this;

            validator.setConfig(this);

            self.$selector = $('#' + self.selector);
            this._super()
            .observe([
                'active',
                'scriptLoaded',
                'selectedCardType'
            ]);

            // re-init payment method events
            self.$selector.off('changePaymentMethod.' + this.code)
            .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));

            // listen block changes
            domObserver.get('#' + self.container, function () {
                if (self.scriptLoaded()) {
                    self.$selector.off('submit');
                    self.initStripe();
                }
            });

            return this;
        },

        /**
         * Enable/disable current payment method
         * @param {Object} event
         * @param {String} method
         * @returns {exports.changePaymentMethod}
         */
        changePaymentMethod: function (event, method) {
            this.active(method === this.code);
            return this;
        },

        /**
         * Triggered when payment changed
         * @param {Boolean} isActive
         */
        onActiveChange: function (isActive) {
            if (!isActive) {
                this.$selector.off('submitOrder.tnw_stripe');
                return;
            }

            this.disableEventListeners();
            window.order.addExcludedPaymentMethod(this.code);

            if (!this.publishableKey) {
                this.error($.mage.__('This payment is not available'));
                return;
            }

            this.enableEventListeners();

            if (!this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Load external Stripe SDK
         */
        loadScript: function () {
            var self = this;
            var state = self.scriptLoaded;

            $('body').trigger('processStart');
            require(['https://js.stripe.com/v3/'], function () {
                state(true);
                self.stripe = window.Stripe(self.publishableKey);
                self.initStripe();
                $('body').trigger('processStop');
            });
        },

        /**
         * Create and mount card Stripe
         */
        initStripe: function () {
            var self = this,
                stripeCardElement;

            try {
                stripeCardElement = self.stripe.elements();

                var style = {
                    base: {
                        fontSize: '17px'
                    }
                };

                self.stripeCardNumber = stripeCardElement.create('cardNumber', {style: style});
                self.stripeCardNumber.mount(this.getSelector('cc_number'));
                self.stripeCardNumber.on('change', function (event) {
                    if (event.empty === false) {
                        self.validateCardType();
                    }

                    self.selectedCardType(
                        validator.getMageCardType(event.brand, self.getCcAvailableTypes())
                    );
                });

                stripeCardElement
                .create('cardExpiry', {style: style})
                .mount(this.getSelector('cc_exp'));

                stripeCardElement
                .create('cardCvc', {style: style})
                .mount(this.getSelector('cc_cid'));
            } catch (e) {
                self.error(e.message);
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
        },

        /**
         * Enable form event listeners
         */
        enableEventListeners: function () {
            if ($('#payment_form_braintree').length) {
                /**
                 * Workaround for braintree.js, which attaches submitOrder event when disabled,
                 * if loaded after stripe.js and stripe is default
                 */
                setTimeout(function () {
                    this.disableEventListeners();
                    this.$selector.on('submitOrder.tnw_stripe', this.submitOrder.bind(this));
                }.bind(this), 1000)
            }
            this.$selector.on('submitOrder.tnw_stripe', this.submitOrder.bind(this));
        },

        /**
         * Disable form event listeners
         */
        disableEventListeners: function () {
            this.$selector.off('submitOrder');
            this.$selector.off('submit');
        },

        /**
         * Trigger order submit
         */
        submitOrder: function () {
            var self = this;
            this.$selector.validate().form();
            this.$selector.trigger('afterValidate.beforeSubmit');
            $('body').trigger('processStop');

            // validate parent form
            if (this.$selector.validate().errorList.length) {
                return false;
            }

            $('body').trigger('processStart');

            this.createPaymentMethod('card', this.stripeCardNumber, this.getOwnerData())
            .done(function (response) {
                let card = response.paymentMethod.card,
                    currencyCode = $('#currency_switcher').val(),
                    amount = $('#grand-total .price').text().replace(/([^0-9.])/g, '')

                if (!card.three_d_secure_usage.supported) {
                    self.setPaymentMethodToken(response.paymentMethod.id);
                    self.placeOrder();
                    $('body').trigger('processStop');
                    return;
                }

                self.createPaymentIntent({
                    paymentMethod: response.paymentMethod,
                    amount: amount,
                    currency: currencyCode
                }).done(function (response) {
                    if (response.skip_3ds) {
                        $('body').trigger('processStop');
                        self.setPaymentMethodToken(response.paymentIntent.id);
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
                            self.setPaymentMethodToken(response.paymentIntent.id);
                            self.setPaymentMethodThreeDs(1);
                            self.placeOrder();
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
                $('body').trigger('processStop');
            })
        },

        /**
         * create payment method
         * @return {jQuery.Deferred}
         */
        createPaymentMethod: function () {
            var self = this,
                dfd = $.Deferred();

            this.stripe
            .createPaymentMethod.apply(this.stripe, arguments)
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
            var self = this,
                dfd = $.Deferred();
            if ($("#tnw_stripe_vault").length) {
                arguments[0].vaultEnabled = $('#tnw_stripe_vault').is(':checked');
            }
            $.post(
                self.createUrl,
                {data: JSON.stringify(arguments[0])}
            ).then(function (response) {
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
         * Get customer billing address details
         * @returns {{billing_details: {address: {country: string, city: string, line1: string}, name: string}}}
         */
        getOwnerData: function () {
            var stripeData = {
                name: $('#order-billing_address_firstname').val() + ' ' + $('#order-billing_address_lastname').val(),
                address: {
                    country: $('#order-billing_address_country_id').val(),
                    line1: $('#order-billing_address_street0').val(),
                    city: $('#order-billing_address_city').val()
                }
            };

            if ($('#order-billing_address_street1').length) {
                stripeData.address.line2 = $('#order-billing_address_street1').val();
            }

            if ($('#order-billing_address_postcode:visible').length) {
                stripeData.address.postal_code = $('#order-billing_address_postcode').val();
            }

            if ($('#order-billing_address_region_id:visible').length) {
                stripeData.address.state = $('#order-billing_address_region_id').val();
            }

            return { 'billing_details': stripeData };
        },

        /**
         * Set payment method token
         * @param token
         */
        setPaymentMethodToken: function (token) {
            $('#' + this.container).find('#' + this.code + '_cc_token').val(token);
        },

        /**
         * Set card 3DS flag
         * @param threedsactive
         */
        setPaymentMethodThreeDs: function (threedsactive) {
            $('#' + this.container).find('#' + this.code + '_cc_3ds').val(threedsactive);
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
         * Place order
         */
        placeOrder: function () {
            $('#' + this.selector).trigger('realOrder');
        },

        /**
         * Get list of currently available card types
         * @returns {Array}
         */
        getCcAvailableTypes: function () {
            var types = [],
                $options = $(this.getSelector('cc_type')).find('option');

            $.map($options, function (option) {
                types.push($(option).val());
            });

            return types;
        },

        /**
         * Validate current entered card type
         * @returns {Boolean}
         */
        validateCardType: function () {
            var $input = $(this.getSelector('cc_number'));
            $input.removeClass('stripe-shosted-fields-invalid');

            if (!this.selectedCardType()) {
                $input.addClass('stripe-shosted-fields-invalid');
                return false;
            }

            $(this.getSelector('cc_type')).val(this.selectedCardType());
            return true;
        },

        /**
         * Get jQuery selector
         * @param {String} field
         * @returns {String}
         */
        getSelector: function (field) {
            return '#' + this.code + '_' + field;
        }
    });
});
