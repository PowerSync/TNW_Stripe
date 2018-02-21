define(
    [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
    'use strict';
    rendererList.push(
        {
        type: 'tnw_stripe',
        component: 'TNW_Stripe/js/view/payment/method-renderer/tnw_stripe'
        }
    );
    /** Add view logic here if needed */
    return Component.extend({});
    }
);
