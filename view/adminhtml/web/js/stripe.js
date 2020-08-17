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

      $.when(this.createToken()).done(function () {
        $('body').trigger('processStop');
        if (self.validateCardType()) {
          self.placeOrder();
        }
      }).fail(function (result) {
        $('body').trigger('processStop');
        self.error(result);

        return false;
      });
    },

    /**
     * Convert card information to stripe token
     */
    createToken: function () {
      var self = this;
      var container = $('#' + this.container);

      var defer = $.Deferred();

      self.stripe.createSource(self.stripeCardNumber).then(function (response) {
        if (response.error) {
          defer.reject(response.error.message);
        } else {
          container.find('#' + self.code + '_cc_token').val(response.source.id);
          defer.resolve();
        }
      });

      return defer.promise();
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
