<?php

require_once('api/Simpla.php');
require_once(__DIR__ . '/begateway-api-php/lib/BeGateway.php');

class BeGateway extends Simpla
{

  private $_order_id;

	public function checkout_form($order_id, $button_text = null)
	{
    $this->_order_id = intval($order_id);

		if(empty($button_text))
			$button_text = 'Перейти к оплате';

      $fields = $this->_get_form();

      if ($fields['error']) {
        $button = '<div class="message_error">Ошибка получения токена на оплату!</div>';
      } else {
    		$button =
          '<form method="POST" action="' . $fields['action'] . '">'.
  				'<input type="hidden" name="token" value="'.$fields['token'].'"/>'.
  				'<input type="submit" name="submit-button" value="'.$button_text.'"  class="checkout_button">'.
  				'</form>';
      }

		return $button;
	}

  private function _get_form() {
    $order = $this->orders->get_order($this->_order_id);
    if (empty($order))
    	return array('error'=>true);

    $payment_method = $this->payment->get_payment_method($order->payment_method_id);
    if (empty($payment_method))
    	return array('error'=>true);

    $payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
    $settings = $this->payment->get_payment_settings($payment_method->id);

    \BeGateway\Settings::$shopId = $settings['shop_id'];
    \BeGateway\Settings::$shopKey = $settings['shop_key'];
    \BeGateway\Settings::$checkoutBase = 'https://' . $settings['domain_checkout'];

    $desc = 'Оплата заказа №'.$order->id;
    $result_url = $this->config->root_url.'/order/'.$order->url;
    $server_url = $this->config->root_url.'/payment/BeGateway/callback.php';
    $server_url = str_replace('simplacms.local', 'simplacms.webhook.begateway.com:8443', $server_url);

    $fio_arr = explode(" ", $order->name);
    $firstname = trim($fio_arr[0]);
    $lastname = trim($fio_arr[1]);

    $transaction = new \BeGateway\GetPaymentToken;

    $transaction->money->setAmount($order->total_price);
    $transaction->money->setCurrency(str_replace("RUR", "RUB", $payment_currency->code));
    $transaction->setDescription($desc);
    $transaction->setTrackingId("$this->_order_id|$order->payment_method_id");
    $transaction->setLanguage('ru');
    $transaction->setNotificationUrl($server_url);
    $transaction->setSuccessUrl($result_url);
    $transaction->setDeclineUrl($result_url);
    $transaction->setFailUrl($result_url);
    $transaction->setCancelUrl($this->config->root_url);

    $transaction->customer->setFirstName($firstname);
    $transaction->customer->setLastName($lastname);
    $transaction->customer->setEmail($order->email);
    $transaction->customer->setPhone($order->phone);

    $transaction->setTestMode($settings['test_mode'] == 1);

    if ($settings['pm_bankcard']) {
      $cc = new \BeGateway\PaymentMethod\CreditCard;
      $transaction->addPaymentMethod($cc);
    }

    if ($settings['pm_bankcard_halva']) {
      $halva = new \BeGateway\PaymentMethod\CreditCardHalva;
      $transaction->addPaymentMethod($halva);
    }

    if ($settings['pm_erip']) {
      $erip = new \BeGateway\PaymentMethod\Erip(array(
        'order_id' => $order_id,
        'account_number' => strval($order_id),
        'service_no' => $settings['pm_erip_service_no'],
        'service_info' => array($desc)
      ));
      $transaction->addPaymentMethod($erip);
    }

    $response = $transaction->submit();

    if (!$response->isSuccess()) {
      echo '<!--' . $response->getMessage() . '-->';
      return array('error'=>true);
    }

    return array(
      'action' => $response->getRedirectUrlScriptName(),
      'token' => $response->getToken(),
      'error' => false
    );
  }
}
