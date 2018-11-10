# Magento 2 Stripe Integration
Accept credit card payments through the Stripe payment gateway.

[![CircleCI](https://circleci.com/gh/PowerSync/TNW_Stripe.svg?style=svg&circle-token=73b2820d4a6adf1d5280bb9f5b267f1fa021748b)](https://circleci.com/gh/PowerSync/TNW_Stripe)

* Supports Magento Instant Purchase for One Click Checkout
* Securely accept customer payments using the Stripe.js tokenization when
collecting all payments.
* Provide customers option of storing payment information for future 
transactions.
* Stored customer card information can be used for orders created in the
frontend or backend.
* Stored cards deleted by customer in Magento are also removed from the
corresponding Stripe customer profile.
* New payments can be authorize or authorize and capture.
* Authorized payments can be captured online during invoice creation.
* Full and partial refund support when creating credit memos.

## Installation
#### Composer
In your Magento 2 root directory run  
`composer require tnw/module-stripe`  
`bin/magento setup:upgrade`  

#### Manual
The module can be installed without Composer by downloading the desired
release from https://github.com/tnw/module-stripe/releases and placing
the contents in `app/code/TNW/Stripe/`  
The module depends on the Stripe PHP-SDK which should be added to your
project via composer by running `composer require stripe/stripe-php:5.2.0`
With the module files in place and the Stripe SDK installed,
run `bin/magento setup:upgrade`

## Configuration
The configuration can be found in the Magento 2 admin panel under  
Store->Configuration->Sales->Payment Methods->Stripe

## Additonal Documentation
[Integration with Stripe Gateway - Wiki](https://technweb.atlassian.net/wiki/spaces/SG/overview)

## License
[Open Software License v. 3.0](https://opensource.org/licenses/OSL-3.0)
