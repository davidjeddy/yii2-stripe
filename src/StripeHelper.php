<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @copyright Copyright David J Eddy, 2016
 * @license https://github.com/davidjeddy/yii2-stripe/LICENSE
 * @link https://github.com/davidjeddy/yii2-stripe#README
 */

namespace davidjeddy\stripe;

use yii\base\Exception;

/**
 * Yii Stripe helper class.
 *
 * @author Victor Demin <demmbox@gmail.com>
 */
class StripeHelper {

    /**
     * If the value is boolean, then it must be in quotes.
     * @param boolean|string $value
     */
    public static function prepareBoolean(&$value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
    }

    /**
     * Check Stripe's documentation. You should not use name tag for card inputs.
     * @param array $options
     * @throws Exception
     */
    public static function secCheck($options) {
        if (isset($options['name'])) {
            throw new Exception("Do not use 'name' tag for number/cvc/month/year inputs.");
        }
    }

}

