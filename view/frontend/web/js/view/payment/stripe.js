define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList ) {
    'use strict';

    var config = window.checkoutConfig.payment,
        stripeType = 'tnw_stripe';

    if (config[stripeType].isActive) {
        rendererList.push(
            {
                type: stripeType,
                component: 'TNW_Stripe/js/view/payment/method-renderer/stripe'
            }
        );
    }

    /** Add view logic here if needed */
    return Component.extend({});
});
