<?php

require_once('api/Simpla.php');

class AssetPayments extends Simpla
{	
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';
		
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$settings = $this->payment->get_payment_settings($payment_method->id);
		$amount = $order->total_price;
		$ip = getenv('HTTP_CLIENT_IP')?:
			  getenv('HTTP_X_FORWARDED_FOR')?:
			  getenv('HTTP_X_FORWARDED')?:
			  getenv('HTTP_FORWARDED_FOR')?:
			  getenv('HTTP_FORWARDED')?:
			  getenv('REMOTE_ADDR');
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$currency = $payment_currency->code;
		if ($currency == 'RUR')
		  $currency = 'RUB';
		
		
		//****Required variables****//	
		$option['TemplateId'] = intval($settings['template_id']);
		$option['CustomMerchantInfo'] = 'Simpla: '.$this->config->version;
		$option['MerchantInternalOrderId'] = $order_id;
		$option['StatusURL'] = $this->config->root_url.'/payment/AssetPayments/callback.php';	
		$option['ReturnURL'] = $this->config->root_url.'/order/';
		$option['AssetPaymentsKey'] = $settings['merchant_id'];
		$option['Amount'] = $amount;	
		$option['Currency'] = $currency;
		$option['IpAddress'] = $ip;
		
		//****Customer data and address****//
		$option['FirstName'] = $order->name;
        $option['Email'] = $order->email;
        $option['Phone'] = $order->phone;
        $option['Address'] = $order->address;
		$option['CountryISO'] = 'UA';
		
		//****Adding cart details****//
		$order = $this->orders->get_order(intval($_SESSION['order_id']));
		$purchases = $this->orders->get_purchases(array('order_id'=>intval($order->id)));
		$products_ids = array();
		$variants_ids = array();
		
		foreach($purchases as $purchase)
		{
			$products_ids[] = $purchase->product_id;
			$variants_ids[] = $purchase->variant_id;
						
			$option['Products'][] = array(
				'ProductId' => $purchase->product_id,
				'ProductName' => $purchase->product_name,
				'ProductPrice' => $purchase->price,
				'ProductItemsNum' => $purchase->amount,
				'ImageUrl' => 'https://assetpayments.com/dist/css/images/product.png',
				);
			$total_price += $purchase->price * $purchase->amount;
		}
		
		//****Adding shipping method****//
		if($order->delivery_id && $order->delivery_price>0)
		{
			
			$dlvr = $this->delivery->get_deliveries(array('enabled'=>1));
			
			foreach(array_slice($dlvr,0,2) as $names)
			{				
				if ($order->delivery_id == $names->id)
					
				$option['Products'][] = array(
					'ProductId' => '1',
					'ImageUrl' => 'https://assetpayments.com/dist/css/images/delivery.png',
					'ProductItemsNum' => 1,
					'ProductName' => $names->name,						
					'ProductPrice' => $order->delivery_price, 					
				);

			}
		}
		
		$data = base64_encode( json_encode($option) );
					
		$button =	'<form method="POST" action="https://assetpayments.us/checkout/pay">
						<input type="hidden" name="data" id="data" value="'.$data.'" />
						<input type=submit class=checkout_button value="'.$button_text.'">
					</form>';
		return $button;
	}
}
