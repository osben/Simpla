<?php
/**
 * Version: 1.2.0.2
 * License: Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу https://money.yandex.ru/doc.xml?id=527132 (далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.
 */
require_once('api/Simpla.php');

class YandexFastPay extends Simpla
{
    const QUICK_PAY_VERSION = 2;
    const ORDER_PLACEHOLDER = '%order_id%';
    const FAST_PAY_URL = 'https://money.yandex.ru/fastpay/confirm';

    public function checkout_form($order_id, $button_text = null)
    {
        if (empty($button_text)) {
            $button_text = 'Перейти к оплате';
        }

        $order = $this->orders->get_order((int)$order_id);
        $payment_method = $this->payment->get_payment_method($order->payment_method_id);
        $settings = $this->payment->get_payment_settings($payment_method->id);
        $narrative = $settings['yandex_fast_pay_narrative'];
        if(strpos(self::ORDER_PLACEHOLDER,$narrative)) {
            $narrative = str_replace(self::ORDER_PLACEHOLDER, $order_id, $narrative);
        }
        $price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);

        $form = '<form action="'. self::FAST_PAY_URL .'" method="post">
                    <input type="hidden" name="formId" value="' . $settings['yandex_fast_pay_id'] . '">
                    <input type="hidden" name="narrative" value="' . htmlspecialchars($narrative) . '">
                    <input type="hidden" name="fio" value="' . $order->name . '">
                    <input type="hidden" name="sum" value="' . $price . '">
                    <input type="hidden" name="quickPayVersion" value="'. self::QUICK_PAY_VERSION .'">
                    <input type="submit" name="submit-button" value="' . $button_text . '" class="checkout_button">
                 </form>';

        return $form;
    }
}