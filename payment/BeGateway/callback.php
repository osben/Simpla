<?php
require_once(__DIR__ . '/begateway-api-php/lib/BeGateway.php');
// Работаем в корневой директории
chdir ('../../');
require_once('api/Simpla.php');
$simpla = new Simpla();

$webhook = new \BeGateway\Webhook;

// Сумма, которую заплатил покупатель. Дробная часть отделяется точкой.
$money = new \BeGateway\Money;
$money->setCents($webhook->getResponse()->transaction->amount);
$money->setCurrency($webhook->getResponse()->transaction->currency);

$amount = $money->getAmount();

// Внутренний номер покупки продавца
// В этом поле передается id заказа в нашем магазине.
list($order_id, $payment_method_id) = explode('|', $webhook->getTrackingId());
$order_id = intval($order_id);
$payment_method_id = intval($payment_method_id);

// Проверим статус
if(!$webhook->isSuccess())
	die('Incorrect Status');
////////////////////////////////////////////////
// Выберем заказ из базы
////////////////////////////////////////////////
$order = $simpla->orders->get_order(intval($order_id));
if(empty($order))
	die('Оплачиваемый заказ не найден');


// Нельзя оплатить уже оплаченный заказ
if($order->paid)
	die('Этот заказ уже оплачен');

////////////////////////////////////////////////
// Выбираем из базы соответствующий метод оплаты
////////////////////////////////////////////////
if ($order->payment_method_id != $payment_method_id)
  die('Опата не принадлежит заказу');

$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
if(empty($method))
	die("Неизвестный метод оплаты");

if(round($simpla->money->convert($order->total_price, $method->currency_id, false), 2) != $money->getAmount())
  die("Неверная сумма");

$settings = unserialize($method->settings);

\BeGateway\Settings::$shopId = $settings['shop_id'];
\BeGateway\Settings::$shopKey = $settings['shop_key'];

// Проверяем авторизационные данные
if (!$webhook->isAuthorized())
  die('Нет авторизации');

////////////////////////////////////
// Проверка наличия товара
////////////////////////////////////
$purchases = $simpla->orders->get_purchases(array('order_id'=>intval($order->id)));
foreach($purchases as $purchase)
{
	$variant = $simpla->variants->get_variant(intval($purchase->variant_id));
	if(empty($variant) || (!$variant->infinity && $variant->stock < $purchase->amount))
	{
		die("Нехватка товара $purchase->product_name $purchase->variant_name");
	}
}

// Установим статус оплачен
$simpla->orders->update_order(intval($order->id), array('paid'=>1));

// Спишем товары
$simpla->orders->close(intval($order->id));
$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));

die("OK".$order_id."\n");
