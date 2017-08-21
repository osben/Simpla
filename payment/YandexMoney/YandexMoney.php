<?php
/**
	Version: 1.2.0.2
	License: Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу https://money.yandex.ru/doc.xml?id=527132 (далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.
*/
require_once('api/Simpla.php');
require_once (dirname(__FILE__).'/../../../Tools.php');

class YandexMoney extends Simpla
{
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';
		
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		//$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$settings = $this->payment->get_payment_settings($payment_method->id);
		$price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);
		$return_url = $this->config->root_url.'/order/'.$order->url;
		$payment_url = ($settings['yandex_testmode'])?'demo':'';
		$payment_sitemode = ($settings['yandex_paymode']=='site')?true:false;
		$payment_type = ($payment_sitemode)?$settings['yandex_paymenttype']:'';

		if (isset($settings['ya_kassa_send_check']) && $settings['ya_kassa_send_check']) {
            $purchases = $this->orders->get_purchases(array('order_id'=>intval($order->id)));

            $receipt = array(
                'customerContact' => $order->email,
                'items' => array(),
            );

            $id_tax = (isset($settings['ya_kassa_tax']) && $settings['ya_kassa_tax'] ? $settings['ya_kassa_tax'] : 1);

            $sum = 0;
            foreach ($purchases as $purchase) {
                $sum += $purchase->price * $purchase->amount;
            }

            if ($order->delivery_id && $order->delivery_price > 0) {
                $sum += $order->delivery_price;
            }

            unset($purshase);

            $disc = number_format($price/$sum, 2, '.', '');

            foreach ($purchases as $purchase) {
                $receipt['items'][] = array(
                    'quantity' => $purchase->amount,
                    'text' => substr($purchase->product_name, 0, 128),
                    'tax' => $id_tax,
                    'price' => array(
                        'amount' => number_format($purchase->price * ($disc), 2, '.', ''),
                        'currency' => 'RUB'
                    ),
                );
            }

            if ($order->delivery_id && $order->delivery_price > 0) {
                $delivery = $this->delivery->get_delivery($order->delivery_id);

                $receipt['items'][] = array(
                    'quantity' => 1,
                    'text' => substr($delivery->name, 0, 128),
                    'tax' => $id_tax,
                    'price' => array(
                        'amount' => number_format($order->delivery_price * ($disc), 2, '.', ''),
                        'currency' => 'RUB'
                    ),
                );
            }
        }

		$button = '<form method="POST" action="https://'.$payment_url.'money.yandex.ru/eshop.xml">
					<input type="hidden" name="shopid" value="'.$settings['yandex_shopid'].'">
					<input type="hidden" name="sum" value="'.$price.'">
					<input type="hidden" name="scid" value="'.$settings['yandex_scid'].'">
					
					<input type="hidden" name="shopSuccessURL" value="'.$return_url.'">
					<input type="hidden" name="shopFailURL" value="'.$return_url.'">
					
					<input type="hidden" name="cps_email" value="'.htmlspecialchars($order->email, ENT_QUOTES).'">
					<input type="hidden" name="cps_phone" value="'.htmlspecialchars(preg_replace("/[-+()]/",'',$order->phone), ENT_QUOTES).'">
                    '.(isset($settings['ya_kassa_send_check']) && $settings['ya_kassa_send_check'] ? '<input type="hidden" name="ym_merchant_receipt" value=\''.json_encode($receipt).'\'>' : '').'
					<input type="hidden" name="customerNumber" value="'.$order->id.'">
					<input type="hidden" name="paymentType" value="'.$payment_type.'">
					<input type="hidden" name="cms_name" value="simplacms"/>
					<input type="submit" name="submit-button" value="'.$button_text.'" class="checkout_button">
					</form>';
		return $button;
	}
}