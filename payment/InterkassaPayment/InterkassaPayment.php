<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Simpla 2.3.7
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.1
 * @update 22.10.2016
 */


require_once('api/Simpla.php');

class InterkassaPayment extends Simpla
{
    public function checkout_form($order_id, $button_text = null)
    {
        if (empty($button_text)) {
            $button_text = 'Перейти к оплате';
        }

        $order = $this->orders->get_order((int)$order_id);
        $payment_method = $this->payment->get_payment_method($order->payment_method_id);
        $payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
        $payment_settings = $this->payment->get_payment_settings($payment_method->id);

        //Собираем все данные для отправки
        $ik_co_id = $payment_settings['ik_co_id'];
        $ik_pm_no = intval($order->id);
        $ik_am = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);
        $ik_desc = '#' . intval($order->id);
        $ik_suc_u = $this->config->root_url . '/order/' . $order->url;
        $ik_fal_u = $this->config->root_url . '/order/' . $order->url;
        $ik_pnd_u = $this->config->root_url . '/order/' . $order->url;
        $ik_ia_u = $this->config->root_url . '/payment/InterkassaPayment/callback.php';

        $key = $payment_settings['s_key'];

        if ($payment_currency->code == 'RUR') {
            $ik_cur = 'RUB';
        } else {
            $ik_cur = $payment_currency->code;
        }

        //Формируем цифровую подпись для отправки на Интеркассу
        $arg = array(
            'ik_cur' => $ik_cur,
            'ik_co_id' => $ik_co_id,
            'ik_pm_no' => $ik_pm_no,
            'ik_am' => $ik_am,
            'ik_desc' => $ik_desc,
            'ik_suc_u' => $ik_suc_u,
            'ik_fal_u' => $ik_fal_u,
            'ik_pnd_u' => $ik_pnd_u,
            'ik_ia_u' => $ik_ia_u,
        );

        $dataSet = $arg;
        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $key);
        $signString = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($signString, true));

        $payment_form = "
		<form name='payment' method='post' action='https://sci.interkassa.com/' accept-charset='UTF-8'> 
		<input type='hidden' name='ik_co_id' value='$ik_co_id'>
		<input type='hidden' name='ik_pm_no' value='$ik_pm_no'>
		<input type='hidden' name='ik_cur'   value='$ik_cur'>
		<input type='hidden' name='ik_am'    value='$ik_am'>
		<input type='hidden' name='ik_desc'  value='$ik_desc'>
		<input type='hidden' name='ik_suc_u' value='$ik_suc_u'>
		<input type='hidden' name='ik_fal_u' value='$ik_fal_u'>
		<input type='hidden' name='ik_pnd_u' value='$ik_pnd_u'>
		<input type='hidden' name='ik_ia_u'  value='$ik_ia_u'>
		<input type='hidden' name='ik_sign'  value='$ik_sign'>
		<input type='submit' name='process'  value='$button_text' class='checkout_button'>
		</form>";
        return $payment_form;
    }
}