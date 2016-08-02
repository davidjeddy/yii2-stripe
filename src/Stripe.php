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
 * Yii stripe component.
 *
 * @author Victor Demin <demmbox@gmail.com>
 */
class Stripe extends \yii\base\Component {

    /**
     * @see Stripe
     * @var string Stripe's public key
     */
    public $publicKey;

    /**
     * @see Stripe
     * @var string Stripe's private key
     */
    public $privateKey;

    /**
     * @see Init extension default
     */
    public function init() {
        if (!$this->publicKey) {
            throw new Exception("Stripe's public key is not set.");
        } elseif (!$this->privateKey) {
            throw new Exception("Stripe's private key is not set.");
        }
        parent::init();
    }

}

