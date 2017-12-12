<?php

require_once('api/Simpla.php');

class WebPay extends Simpla
{
    public function checkout_form($order_id, $button_text = null)
    {
        if (empty($button_text)) {
            $button_text = 'Перейти к оплате';
        }

        $order = $this->orders->get_order((int)$order_id);
        $purchases = $this->orders->get_purchases(array('order_id' => intval($order->id)));

        $payment_method = $this->payment->get_payment_method($order->payment_method_id);
        $payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
        if ($payment_currency->code == 'RUR') {
            $payment_currency->code = 'RUB';
        }

        $settings = $this->payment->get_payment_settings($payment_method->id);

        $total_price_not_discount = 0;

        $return_url = $this->config->root_url . '/order/' . $order->url;
        $cancel_return_url = $this->config->root_url . '/order/' . $order->url;
        $notify_url = $this->config->root_url . '/payment/WebPay/callback.php';


        if ($settings['webpay_testmode']) {
            $payment_url = "https://securesandbox.webpay.by";
        } else {
            $payment_url = "https://payment.webpay.by";
        }

        $seed = time();


        $button = '<form method="POST" action="' . $payment_url . '">
						<input type="hidden" name="*scart">
						<input type="hidden" name="wsb_storeid" value="' . $settings['webpay_shop_id'] . '" >
						<!-- TODO не обьязательные поле
							<input type="hidden" name="wsb_store" value="' . $this->settings->site_name . '" >
						-->
						<input type="hidden" name="wsb_order_num" value="' . $order->id . '" >
						<input type="hidden" name="wsb_currency_id" value="' . $payment_currency->code . '" ><!-- BYR, USD, EUR, RUB -->
						<input type="hidden" name="wsb_version" value="2">
						<!-- TODO не обьязательные поле (russian, english)
							<input type="hidden" name="wsb_language_id" value="russian">
						-->


						<input type="hidden" name="wsb_return_url" value="' . $return_url . '">
						<input type="hidden" name="wsb_cancel_return_url" value="' . $cancel_return_url . '">
						<input type="hidden" name="wsb_notify_url" value="' . $notify_url . '">
						<input type="hidden" name="wsb_test" value="' . $settings['webpay_testmode'] . '">

						<!-- TODO не обьязательные поля
							<input type="hidden" name="wsb_customer_name" value="">
							<input type="hidden" name="wsb_customer_address" value="">
							<input type="hidden" name="wsb_service_date" value="">
						-->
						';

        $i = 0;
        foreach ($purchases as $purchase) {
            $price = $this->money->convert($purchase->price, $payment_method->currency_id, false);
            $button .= '
								<input type="hidden" name="wsb_invoice_item_name[' . $i . ']" value="' . $purchase->product_name . ' ' . $purchase->variant_name . '">
								<input type="hidden" name="wsb_invoice_item_quantity[' . $i . ']" value="' . $purchase->amount . '">
								<input type="hidden" name="wsb_invoice_item_price[' . $i . ']" value="' . $price . '">
								';
            $total_price_not_discount += $purchase->amount * $price;
            $i++;
        }
        if ($order->discount > 0) {
            $order->coupon_discount += $total_price_not_discount - $total_price_not_discount * (100 - $order->discount) / 100;

        }

        if ($order->coupon_discount > 0) {
            $discount = $this->money->convert($order->coupon_discount, $payment_method->currency_id, false);
            if (!empty($order->coupon_code)) {
                $button .= '<input type="hidden" name="wsb_discount_name" value="Купон: ' . $order->coupon_code . '">';
            }

            $button .= '<input type="hidden" name="wsb_discount_price" value="' . $discount . '">';
            $total_price_not_discount -= $discount;
        }


        if ($order->delivery_id && !$order->separate_delivery && $order->delivery_price > 0) {
            $delivery_price = $this->money->convert($order->delivery_price, $payment_method->currency_id, false);
            $button .= '
								<input type="hidden" name="wsb_shipping_price" value="' . $delivery_price . '">
							';
            $total_price_not_discount += $delivery_price;
        }

        $signature = sha1(
            $seed
            . $settings['webpay_shop_id']
            . $order->id
            . $settings['webpay_testmode']
            . $payment_currency->code
            . $total_price_not_discount
            . $settings['webpay_shop_key']
        );

        $button .= '
						<input type="hidden" name="wsb_seed" value="' . $seed . '">
						<input type="hidden" name="wsb_signature" value="' . $signature . '">
						<!-- TODO не обьязательные поля
							<input type="hidden" name="wsb_tax" value="">
							
							<input type="hidden" name="wsb_shipping_name" value="">
							<input type="hidden" name="wsb_discount_name" value="">
							<input type="hidden" name="wsb_discount_price" value="">
							
						-->
						<input type="hidden" name="wsb_total" value="' . $total_price_not_discount . '">
						<!-- TODO не обьязательные поля
							<input type="hidden" name="wsb_order_tag" value="">
						-->
						<input type="hidden" name="wsb_email" value="' . $order->email . '">
						<input type="hidden" name="wsb_phone" value="' . $order->phone . '">
						<input type="submit" name="submit-button" value="' . $button_text . '" class="checkout_button">
					</form>';
        return $button;
    }

}
