define([
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "TNW_Stripe/js/view/payment/adapter",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/model/full-screen-loader",
    "Magento_Checkout/js/action/redirect-on-success",
], function (VaultComponent, adapter, quote, fullScreenLoader, redirectOnSuccessAction) {
    "use strict"

    return VaultComponent.extend({
        defaults: {
            template: "Magento_Vault/payment/form",
            paymentMethodToken: false,
        },

        /**
         * Place order
         */
        placeOrderClick: function () {
            var self = this

            fullScreenLoader.startLoader()
            this.isPlaceOrderActionAllowed(false)

            adapter
                .createPaymentIntent({
                    public_hash: this.publicHash,
                    amount: quote.totals()["base_grand_total"],
                    currency: quote.totals()["base_currency_code"],
                })
                .done(function (response) {
                    if (response.skip_3ds) {
                        self.setPaymentMethodToken(response.paymentIntent.id)
                        self.placeOrder()
                        return
                    }
                    // Disable Payment Token
                    if (!response.pi) {
                        fullScreenLoader.stopLoader(true)
                        self.setPaymentMethodToken(false)
                        return
                    }
                    adapter.authenticateCustomer(response.pi, function (error, response) {
                        if (error) {
                            self.isPlaceOrderActionAllowed(true)
                            self.setPaymentMethodToken(false)
                            self.messageContainer.addErrorMessage({
                                message: "3D Secure authentication failed.",
                            })
                        } else {
                            self.setPaymentMethodToken(response.paymentIntent.id)
                            self.placeOrder()
                        }
                    })
                })
                .fail(function (e) {
                    self.messageContainer.addErrorMessage({
                        message: e.responseJSON.message || $t("An error occurred on the server."),
                    })
                    fullScreenLoader.stopLoader(true)
                    self.isPlaceOrderActionAllowed(true)
                    self.setPaymentMethodToken(false)
                })
        },

        /**
         * Place order.
         */
        placeOrder: function (data, event) {
            var self = this

            if (event) {
                event.preventDefault()
            }
            this.getPlaceOrderDeferredObject()
                .done(function () {
                    self.afterPlaceOrder()

                    if (self.redirectAfterPlaceOrder) {
                        redirectOnSuccessAction.execute()
                    }
                })
                .fail(function (e) {
                    self.messageContainer.addErrorMessage({
                        message: e.responseJSON.message || $t("An error occurred on the server."),
                    })
                    self.isPlaceOrderActionAllowed(true)
                })
                .always(function () {
                    fullScreenLoader.stopLoader(true)
                })
        },

        getMaskedCard: function () {
            return this.details.maskedCC
        },

        getExpirationDate: function () {
            return this.details.expirationDate
        },

        getCardType: function () {
            return this.details.type
        },

        getToken: function () {
            return this.publicHash
        },

        /**
         * @returns {*}
         */
        getData: function () {
            var data = {
                method: this.getCode(),
            }

            data["additional_data"] = {}
            data["additional_data"]["public_hash"] = this.getToken()
            data["additional_data"]["payment_method_token"] = this.paymentMethodToken

            return data
        },

        /**
         * Set payment method token
         * @param token
         */
        setPaymentMethodToken: function (token) {
            this.paymentMethodToken = token
        },
    })
})
