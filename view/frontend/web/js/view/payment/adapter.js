/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(["jquery", "stripejs", "Magento_Ui/js/model/messageList", "mage/translate"], function (
    $,
    stripejs,
    globalMessageList,
    $t
) {
    "use strict"

    return {
        apiClient: null,
        config: {},
        checkout: null,
        stripeCardNumber: null,
        stripeExpiryDate: null,
        stripeCVC: null,

        /**
         * Get Stripe api client
         * @returns {Object}
         */
        getApiClient: function () {
            if (!this.apiClient) {
                this.apiClient = Stripe(this.getPublishableKey())
            }

            return this.apiClient
        },

        /**
         * Set configuration
         * @param {Object} config
         */
        setConfig: function (config) {
            this.config = config
        },

        setup: function () {
            var stripeCardElement = this.getApiClient().elements()

            var style = {
                base: {
                    fontSize: "17px",
                },
            }

            this.stripeCardNumber = stripeCardElement.create("cardNumber", { style: style })
            this.stripeCardNumber.mount(this.config.hostedFields.number.selector)
            this.stripeCardNumber.on("change", this.config.hostedFields.onFieldEvent)

            this.stripeExpiryDate = stripeCardElement.create("cardExpiry", { style: style })
            this.stripeExpiryDate.mount(this.config.hostedFields.expiry.selector)
            if (typeof this.config.hostedFields.onExpiryEvent === "function") {
                this.stripeExpiryDate.on("change", this.config.hostedFields.onExpiryEvent)
            }

            this.stripeCVC = stripeCardElement.create("cardCvc", { style: style })
            this.stripeCVC.mount(this.config.hostedFields.cvc.selector)
            if (typeof this.config.hostedFields.onCVCEvent === "function") {
                this.stripeCVC.on("change", this.config.hostedFields.onCVCEvent)
            }
        },

        /**
         * create payment method by cart
         * @return {jQuery.Deferred}
         */
        createPaymentMethodByCart: function (sourceData) {
            return this.createPaymentMethod.call(this, "card", this.stripeCardNumber, sourceData)
        },

        /**
         * create source
         * @return {jQuery.Deferred}
         */
        createPaymentMethod: function () {
            var self = this,
                dfd = $.Deferred()

            this.getApiClient()
                .createPaymentMethod.apply(this.getApiClient(), arguments)
                .then(function (response) {
                    if (response.error) {
                        dfd.reject(response)
                    } else {
                        dfd.resolve(response)
                    }
                })

            return dfd
        },

        createPaymentIntent: function () {
            var self = this,
                dfd = $.Deferred()
            if ($("#tnw_stripe_enable_vault").length) {
                arguments[0].vaultEnabled = $("#tnw_stripe_enable_vault").is(":checked")
            }
            $.post(self.getCreateUrl(), { data: JSON.stringify(arguments[0]) }).then(function (response) {
                if (response.error) {
                    dfd.reject(response)
                } else {
                    dfd.resolve(response)
                }
            })
            return dfd
        },

        /**
         * Get payment name
         * @returns {String}
         */
        getCode: function () {
            return "tnw_stripe"
        },

        /**
         * Get publishable key
         * @returns {String|*}
         */
        getPublishableKey: function () {
            return window.checkoutConfig.payment[this.getCode()].publishableKey
        },

        /**
         * Show error message
         *
         * @param {String} errorMessage
         */
        showError: function (errorMessage) {
            globalMessageList.addErrorMessage({
                message: errorMessage,
            })
        },

        getCreateUrl: function () {
            return window.checkoutConfig.payment[this.getCode()].createUrl
        },
        handleCardAction: function (paymentIntentId, done) {
            try {
                this.getApiClient()
                    .handleCardAction.apply(this.getApiClient(), [paymentIntentId])
                    .then(function (result) {
                        if (result.error) return done(result.error.message, result)
                        return done(false, result)
                    })
            } catch (e) {
                done(e.message)
            }
        },
        authenticateCustomer: function (paymentIntentId, done) {
            var self = this
            try {
                this.getApiClient()
                    .retrievePaymentIntent.apply(this.getApiClient(), [paymentIntentId])
                    .then(function (result) {
                        if (result.error) return done(result.error, result)
                        if (
                            result.paymentIntent.status == "requires_action" ||
                            result.paymentIntent.status == "requires_source_action"
                        ) {
                            return self.handleCardAction(paymentIntentId, done)
                        }
                        return done(false, result)
                    })
            } catch (e) {
                done(e.message)
            }
        },
    }
})
