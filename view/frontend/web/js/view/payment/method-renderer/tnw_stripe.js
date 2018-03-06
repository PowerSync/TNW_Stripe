define([
    'jquery',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/model/full-screen-loader',
    'TNW_Stripe/js/validator',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/model/quote',
    'stripejs'
], function (
    $,
    Component,
    fullScreenLoader,
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
        token: null,
        isValidCardNumber: false,

        imports: {
          onActiveChange: 'active'
        }
      },

      /**
       * @returns {exports}
       */
      initialize: function () {
        this._super();
        this.vaultEnabler = new VaultEnabler();
        this.vaultEnabler.setPaymentCode(this.getVaultCode());

        return this;
      },

      /**
       * Set list of observable attributes
       *
       * @returns {exports}
       */
      initObservable: function () {
        validator.setConfig(window.checkoutConfig.payment[this.getCode()]);
        this._super()
            .observe(['active']);

        return this;
      },

      initStripe: function () {
        var self = this;

        var intervalId = setInterval(function () {
          // stop loader when frame will be loaded
          if ($('#tnw_stripe_cc_number').length) {
            clearInterval(intervalId);

            self.stripe = Stripe(self.getPublishableKey());
            self.stripeCardElement = self.stripe.elements();

            var style = {
              base: {
                fontSize: '17px'
              }
            };

            self.stripeCardNumber = self.stripeCardElement.create('cardNumber', {style: style});
            self.stripeCardNumber.mount('#tnw_stripe_cc_number');
            self.stripeCardNumber.on('change', function (event) {
              self.isValidCardNumber = event.complete;
              self.selectedCardType(
                validator.getMageCardType(event.brand, self.getCcAvailableTypes())
              );
            });

            self.stripeCardExpiry = self.stripeCardElement.create('cardExpiry', {style: style});
            self.stripeCardExpiry.mount('#tnw_stripe_expiration');

            self.stripeCardExpiry = self.stripeCardElement.create('cardCvc', {style: style});
            self.stripeCardExpiry.mount('#tnw_stripe_cc_cid');
          }
        }, 500);
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

        if (this.token) {
          var card = this.token.card;

          data.additional_data.cc_exp_month = card.exp_month;
          data.additional_data.cc_exp_year = card.exp_year;
          data.additional_data.cc_last4 = card.last4;
          data.additional_data.cc_type = card.brand;
          data.additional_data.cc_token = this.token.id;
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
       * Address
       * @return {{name: string, address_country: string, address_line1: *}}
       */
      getAddressData: function () {
        var billingAddress = quote.billingAddress();

        var stripeData = {
          name: billingAddress.firstname + ' ' + billingAddress.lastname,
          address_country: billingAddress.countryId,
          address_line1: billingAddress.street[0]
        };

        if (billingAddress.street.length === 2) {
          stripeData.address_line2 = billingAddress.street[1];
        }

        if (billingAddress.hasOwnProperty('postcode')) {
          stripeData.address_zip = billingAddress.postcode;
        }

        if (billingAddress.hasOwnProperty('regionCode')) {
          stripeData.address_state = billingAddress.regionCode;
        }

        return stripeData;
      },

      /**
       * Get full selector name
       *
       * @param {String} field
       * @returns {String}
       */
      getSelector: function (field) {
        return '#' + this.getCode() + '_' + field;
      },

      /**
       * Validate current credit card type
       * @returns {Boolean}
       */
      validateCardType: function () {
        var $selector = $(this.getSelector('cc_number')),
            invalidClass = 'stripe-hosted-fields-invalid';

        $selector.removeClass(invalidClass);

        if (this.selectedCardType() === null || !this.isValidCardNumber) {
          $(this.getSelector('cc_number')).addClass(invalidClass);
          return false;
        }

        return true;
      },

      /**
       * Get list of available CC types
       *
       * @returns {Object}
       */
      getCcAvailableTypes: function () {
        var availableTypes = validator.getAvailableCardTypes(),
            billingAddress = quote.billingAddress(),
            billingCountryId;

        this.lastBillingAddress = quote.shippingAddress();

        if (!billingAddress) {
          billingAddress = this.lastBillingAddress;
        }

        billingCountryId = billingAddress.countryId;
        if (billingCountryId && validator.getCountrySpecificCardTypes(billingCountryId)) {
          return validator.collectTypes(
            availableTypes, validator.getCountrySpecificCardTypes(billingCountryId)
          );
        }

        return availableTypes;
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
        if (this.validateCardType()) {
          this.isPlaceOrderActionAllowed(false);

          var self = this;
          self.stripe.createToken(self.stripeCardNumber, this.getAddressData())
            .then(function (response) {
              if (response.error) {
                self.isPlaceOrderActionAllowed(true);
                self.messageContainer.addErrorMessage({
                  'message': response.error.message
                });
              } else {
                self.token = response.token;
                self.placeOrder();
              }
            });
        }
      }
    });
});
