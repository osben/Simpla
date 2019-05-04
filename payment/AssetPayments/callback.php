<?php

chdir ('../../');
require_once('api/Simpla.php');

		// Выбираем данные
		$json = json_decode(file_get_contents('php://input'), true);

		$simpla = new Simpla();
		$order_id = $json['Order']['OrderId'];
		$order = $simpla->orders->get_order(intval($order_id));
		$payment_method = $simpla->payment->get_payment_method($order->payment_method_id);
		$settings = $simpla->payment->get_payment_settings($payment_method->id);
		

		$key = $settings['merchant_id'];
		$secret = $settings['secret_key'];
		$transactionId = $json['Payment']['TransactionId'];
		$signature = $json['Payment']['Signature'];
		$amount = $json['Order']['Amount'];
		$currency = $json['Order']['Currency'];
		$status = $json['Payment']['StatusCode'];
		$requestSign =$key.':'.$transactionId.':'.strtoupper($secret);
		$sign = hash_hmac('md5',$requestSign,$secret);
		

		if ($status == 1 && $sign == $signature) {
			// Установим статус оплачен
			$simpla->orders->update_order(intval($order->id), array('paid' => 1));
            
			// Отправим уведомление на email
            $simpla->notify->email_order_user(intval($order->id));
            $simpla->notify->email_order_admin(intval($order->id));
            
			// Спишем товары
            $simpla->orders->close(intval($order->id));
		} else {
			$simpla->orders->update_order(intval($order->id), array('paid' => 0));
		}
				
?>