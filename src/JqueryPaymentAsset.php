<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @copyright Copyright David J Eddy, 2016
 * @license https://github.com/davidjeddy/yii2-stripe/LICENSE
 * @link https://github.com/davidjeddy/yii2-stripe#README
 */

namespace davidjeddy\stripe;

use yii\web\AssetBundle;

/**
 * Asset bundle for the Jquery Payment Library js.
 *
 * @author Victor Demin <demin@trabeja.com>
 */
class JqueryPaymentAsset extends AssetBundle {

    public $sourcePath = '@bower/jquery.payment';
    public $js = [
        'lib/jquery.payment.js',
    ];

}
