<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @copyright Copyright David J Eddy, 2016
 * @link https://github.com/davidjeddy/yii2-stripe#README
 */

namespace davidjeddy\stripe;

use yii;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Yii stripe custom form.
 * https://stripe.com/docs/tutorials/forms
 *
 * @author Victor Demin <demmbox@gmail.com>
 * @author David J Eddy <me@davidjeddy.com>
 */
class StripeForm extends \yii\widgets\ActiveForm
{
    /* Stripe constants */
    const NUMBER_ID = 'number';
    const CVC_ID = 'cvc';
    const MONTH_ID = 'exp-month';
    const YEAR_ID = 'exp-year';
    const MONTH_YEAR_ID = 'exp-month-year';

    /* Auto fill spec. @see https://html.spec.whatwg.org/multipage/forms.html */
    const AUTO_CC_ATTR = 'cc-number';
    const AUTO_EXP_ATTR = 'cc-exp';
    const AUTO_MONTH_ATTR = 'cc-exp-month';
    const AUTO_YEAR_ATTR = 'cc-exp-year';

    /**
     * @see Stripe's javascript location
     * @var string url to stripe's javascript
     */
    public $stripeJs = 'https://js.stripe.com/v2/';

    /**
     * Js Expression that will handle the response.
     * If not over written the default behavior from within init() will be used
     *
     * @var JsExpression
     */
    public $stripeResponseHandler;

    /**
     * Js Expression that will handle the request.
     * If not over written the default behavior from within init() will be used
     *
     * @var JsExpression
     */
    public $stripeRequestHandler;

    /**
     * Input id and name tags of the hidden token input that will be sent to PayAction.
     * @var string
     */
    public $tokenInputName = 'stripeToken';

    /**
     * If the default behavior for the response is used, then you can set the id of error's container.
     * Note! this property is useless if you set your own response handler.
     * @var string
     */
    public $errorContainerId = "payment-errors";

    /**
     * Apply Jquery Payment format to the inputs
     * @see https://github.com/stripe/jquery.payment.
     * @var boolean
     */
    public $applyJqueryPaymentFormat = true;

    /**
     * Perform Jquery Payment client validation.
     * @var boolean
     */
    public $applyJqueryPaymentValidation = true;

    /**
     * Class applied to .form-group when Jquery Payment Validation didn't pass
     * @var string
     */
    public $errorClass = 'has-error';

    /**
     * Brand container used when Jquery Payment identify the brand by card number
     * @var string
     */
    public $brandContainerId = 'cc-brand';

    /**
     * @see Init extension default
     */
    public function init() {
        parent::init();

        //Set default response behavior
        if (!isset($this->stripeResponseHandler)) {
            $this->stripeResponseHandler = 'function stripeResponseHandler(status, response) {
                var $form = $("#' . $this->options['id'] . '");
                if (response.error) {
                    $form.find("#' . $this->errorContainerId . '").text(response.error.message);
                    $form.find("button").prop("disabled", false);
                } else {
                    var token = response.id;
                    $form.append($("<input type=\"hidden\" name=\"' . $this->tokenInputName . '\" id=\"' . $this->tokenInputName . '\" />").val(token));
                    $form.get(0).submit();
                }
            };';
        }
    }

    /**
     * Will show the Stripe's simple form modal
     */
    public function run() {
        parent::run();

        $this->registerFormScripts();
        if ($this->applyJqueryPaymentFormat || $this->applyJqueryPaymentValidation) {
            $this->registerJqueryPaymentScripts();
        }
    }

    /**
     * Will register mandatory javascripts to work
     */
    public function registerFormScripts() {
        $view = $this->getView();
        $view->registerJsFile($this->stripeJs, ['position' => \yii\web\View::POS_HEAD]);

        //form scripts
        $view->registerJs("Stripe.setPublishableKey('" . Yii::$app->stripe->publicKey . "');", \yii\web\View::POS_BEGIN);

        $view->registerJs($this->stripeRequestHandler, \yii\web\View::POS_READY);
        $view->registerJs($this->stripeResponseHandler, \yii\web\View::POS_READY);
    }

    /**
     * Will register Jquery Payment scripts
     */
    public function registerJqueryPaymentScripts() {
        $view = $this->getView();
        JqueryPaymentAsset::register($view);

        if ($this->applyJqueryPaymentFormat) {
            $js = "jQuery(function($) {
                $('input[data-stripe=" . self::NUMBER_ID . "]').payment('formatCardNumber');
                $('input[data-stripe=" . self::CVC_ID . "]').payment('formatCardCVC');
                $('input[data-stripe=" . self::MONTH_YEAR_ID . "]').payment('formatCardExpiry');
                $('input[data-stripe=" . self::MONTH_ID . "]').payment('restrictNumeric');
                $('input[data-stripe=" . self::YEAR_ID . "]').payment('restrictNumeric');
            });";
            $view->registerJs($js);
        }

        //Jquery client validation submit
        if ($this->applyJqueryPaymentValidation) {
            $js = 'jQuery(function($) {
                $.fn.toggleInputError = function(erred) {
                    this.closest(".form-group").toggleClass("' . $this->errorClass . '", erred);
                    return this;
                };

                $("#' . $this->options['id'] . '").on("beforeSubmit", function(e) {
                    var $form = $("#' . $this->options['id'] . '");
                    var $number = $("input[data-stripe=' . self::NUMBER_ID . ']");
                    var $cvc = $("input[data-stripe=' . self::CVC_ID . ']");
                    var $exp = $("input[data-stripe=' . self::MONTH_YEAR_ID . ']");
                    var $month = $("input[data-stripe=' . self::MONTH_ID . ']");
                    var $year = $("input[data-stripe=' . self::YEAR_ID . ']");

                    var cardType = $.payment.cardType($number.val());
                    $("#' . $this->brandContainerId . '").text(cardType);

                    $number.toggleInputError(!$.payment.validateCardNumber($number.val()));
                    $cvc.toggleInputError(!$.payment.validateCardCVC($cvc.val(), cardType));

                    if ($exp.length) {
                        $exp.toggleInputError(!$.payment.validateCardExpiry($exp.payment("cardExpiryVal")));
                        var fullDate = $exp.val();
                        var res = fullDate.split(" / ", 2);
                        $month.val(res[0]);
                        $year.val(res[1]);
                    }else{
                        $month.toggleInputError(!$.payment.validateCardExpiry($month.val(), $year.val()));
                        $year.toggleInputError(!$.payment.validateCardExpiry($month.val(), $year.val()));
                    }

                    $(this).find("button").prop("disabled", true);
                    Stripe.card.createToken($form, stripeResponseHandler);
                    return false;
                });
            });';
            $view->registerJs($js);
        }
    }

    /**
     * Will generate card number input
     * @param array $options
     * @return string genetared input tag
     */
    public function numberInput($options = []) {
        $defaultOptions = [
            'id' => self::NUMBER_ID,
            'class' => 'form-control',
            'autocomplete' => self::AUTO_CC_ATTR,
            'placeholder' => '•••• •••• •••• ••••',
            'required' => true,
            'type' => 'tel',
            'size' => 20
        ];
        $mergedOptions = array_merge($defaultOptions, $options);
        StripeHelper::secCheck($mergedOptions);
        $mergedOptions['data-stripe'] = self::NUMBER_ID;
        return Html::input('text', null, null, $mergedOptions);
    }

    /**
     * Will generate cvc input
     * @param array $options
     * @return string genetared input tag
     */
    public function cvcInput($options = []) {
        $defaultOptions = [
            'id' => self::CVC_ID,
            'class' => 'form-control',
            'autocomplete' => 'off',
            'placeholder' => '•••',
            'required' => true,
            'type' => 'tel',
            'size' => 4
        ];
        $mergedOptions = array_merge($defaultOptions, $options);
        StripeHelper::secCheck($mergedOptions);
        $mergedOptions['data-stripe'] = self::CVC_ID;
        return Html::input('text', null, null, $mergedOptions);
    }

    /**
     * Will generate year input
     * @param array $options
     * @return string genetared input tag
     */
    public function yearInput($options = []) {
        $defaultOptions = [
            'id' => self::YEAR_ID,
            'class' => 'form-control',
            'autocomplete' => self::AUTO_YEAR_ATTR,
            'placeholder' => '••••',
            'required' => true,
            'type' => 'tel',
            'maxlength' => 4,
            'size' => 4
        ];
        $mergedOptions = array_merge($defaultOptions, $options);
        StripeHelper::secCheck($mergedOptions);
        $mergedOptions['data-stripe'] = self::YEAR_ID;
        return Html::input('text', null, null, $mergedOptions);
    }

    /**
     * Will generate month input
     * @param array $options
     * @return string genetared input tag
     */
    public function monthInput($options = []) {
        $defaultOptions = [
            'id' => self::MONTH_ID,
            'class' => 'form-control',
            'autocomplete' => self::AUTO_MONTH_ATTR,
            'placeholder' => '••',
            'required' => true,
            'type' => 'tel',
            'maxlength' => 2,
            'size' => 2
        ];
        $mergedOptions = array_merge($defaultOptions, $options);
        StripeHelper::secCheck($mergedOptions);
        $mergedOptions['data-stripe'] = self::MONTH_ID;
        return Html::input('text', null, null, $mergedOptions);
    }

    /**
     * Will generate month and year input with 2 hidden inputs for month and year values.
     * @param array $options
     * @return string genetared input tag
     */
    public function monthAndYearInput($options = []) {
        $defaultOptions = [
            'id' => self::MONTH_YEAR_ID,
            'class' => 'form-control',
            'autocomplete' => self::AUTO_EXP_ATTR,
            'placeholder' => '•• / ••',
            'required' => true,
            'type' => 'tel',
        ];
        $mergedOptions = array_merge($defaultOptions, $options);
        StripeHelper::secCheck($mergedOptions);
        $mergedOptions['data-stripe'] = self::MONTH_YEAR_ID;
        $inputs = Html::input('text', null, null, $mergedOptions);

        //Append hidden year and month inputs that will get value from mixed and send to stripe
        $inputs = $inputs . $this->monthInput(['type' => 'hidden']);
        $inputs = $inputs . $this->yearInput(['type' => 'hidden']);
        return $inputs;
    }

}
