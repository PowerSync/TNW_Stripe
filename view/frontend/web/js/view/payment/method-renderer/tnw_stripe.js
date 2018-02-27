define([
    'jquery',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/quote',
    'stripejs'
], function (
    $,
    Component,
    placeOrderAction,
    fullScreenLoader,
    additionalValidators,
    validator,
    redirectOnSuccessAction,
    VaultEnabler,
    quote
) {
    'use strict';

    return Component.extend({
      defaults: {
        active: false,
        template: 'TNW_Stripe/payment/form',
        stripe: null,
        stripeCardElement: null,
        stripeCard: null,
        ccCode: null,
        ccMessageContainer: null,
        source: null,

        imports: {
          onActiveChange: 'active'
        }
      },

      initialize: function () {
        this._super();
        this.stripe = Stripe(this.getPublishableKey());
        this.vaultEnabler = new VaultEnabler();
        this.vaultEnabler.setPaymentCode(this.getVaultCode());
      },

      /**
       * Set list of observable attributes
       *
       * @returns {exports.initObservable}
       */
      initObservable: function () {
        this._super()
            .observe(['active']);

        return this;
      },

      initStripe: function () {
        var self = this;
        self.stripeCardElement = self.stripe.elements();
        self.stripeCard = self.stripeCardElement.create('card', {
          hidePostalCode: true,
          style: {
            base: {
              fontSize: '20px'
            }
          }
        });
        self.stripeCard.mount('#stripe-card-element');
      },

      getCode: function () {
        return 'tnw_stripe';
      },

      /**
       * Check if payment is active
       *
       * @returns {Boolean}
       */
      isActive: function () {
        var active = this.getCode() === this.isChecked();

        this.active(active);

        return active;
      },

      /**
       * Triggers when payment method change
       * @param {Boolean} isActive
       */
      onActiveChange: function (isActive) {
        if (!isActive) {
          return;
        }

        this.restoreMessageContainer();
        this.restoreCode();

        this.initStripe();
      },

      /**
       * Restore original message container for cc-form component
       */
      restoreMessageContainer: function () {
        this.messageContainer = this.ccMessageContainer;
      },

      /**
       * Restore original code for cc-form component
       */
      restoreCode: function () {
        this.code = this.ccCode;
      },

      /**
       * @inheritdoc
       */
      initChildren: function () {
        this._super();
        this.ccMessageContainer = this.messageContainer;
        this.ccCode = this.code;

        return this;
      },

      getData: function () {
        var data = this._super();

        if (this.source) {
          var card = this.source.card;

          data.additional_data.cc_exp_month = card.exp_month;
          data.additional_data.cc_exp_year = card.exp_year;
          data.additional_data.cc_last4 = card.last4;
          data.additional_data.cc_type = card.brand;
          data.additional_data.cc_token = this.source.id;
        }

        this.vaultEnabler.visitAdditionalData(data);

        return data;
      },

      getPublishableKey: function () {
        return window.checkoutConfig.payment[this.getCode()].publishableKey;
      },

      validate: function () {
        var $form = $('#' + this.getCode() + '-form');
        return $form.validation() && $form.validation('isValid');
      },

      isVaultEnabled: function () {
        return this.vaultEnabler.isVaultEnabled();
      },

      getVaultCode: function () {
        return window.checkoutConfig.payment[this.getCode()].vaultCode;
      },

      /**
       * Returns state of place order button
       * @returns {Boolean}
       */
      isButtonActive: function () {
        return this.isActive() && this.isPlaceOrderActionAllowed();
      },

      /**
       * Triggers order placing
       */
      placeOrderClick: function () {
        var self = this;
        this.isPlaceOrderActionAllowed(false);

        self.stripe.createSource(self.stripeCard, {
          usage: 'reusable',
          flow: 'none'
        }).then(function (response) {
          if (response.error) {
            self.isPlaceOrderActionAllowed(true);

            self.messageContainer.addErrorMessage({
                'message': response.error.message
            });
          } else {
            self.source = response.source;
            self.placeOrder();
          }
        });
      }
    });
});