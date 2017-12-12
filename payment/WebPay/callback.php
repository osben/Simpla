<?php

// Работаем в корневой директории
chdir('../../');
require_once 'api/Simpla.php';

$simpla = new Simpla();

$batch_timestamp = $simpla->request->post('batch_timestamp'); //    - время совершения транзакции;
$currency_id     = $simpla->request->post('currency_id'); //    - валюта транзакции (Примечание: представляет собой буквенный, трехзначный код в соответствии с ISO 4217 );
$amount          = $simpla->request->post('amount'); //    - сумма транзакции;
$payment_method  = $simpla->request->post('payment_method'); //    - метод совершения транзакции (возможные значения: cc банковская карточка, test - совершена без реального процессинга карточки);
$order_id        = $simpla->request->post('order_id'); //    - номер заказа в системе webpay.by;
$site_order_id   = $simpla->request->post('site_order_id'); //    - номер (имя) заказа, присвоенное магазином;
$transaction_id  = $simpla->request->post('transaction_id'); //    - номер транзакции;
$payment_type    = $simpla->request->post('payment_type'); //    - тип транзакции;
$rrn             = $simpla->request->post('rrn'); //    - номер транзакции в системе Visa/MasterCard;
$wsb_signature   = $simpla->request->post('wsb_signature'); //    - электронная подпись

$order = $simpla->orders->get_order(intval($site_order_id));
if (empty($order)) {
    die('Оплачиваемый заказ не найден');
}

// Нельзя оплатить уже оплаченный заказ
if ($order->paid) {
    die('Этот заказ уже оплачен');
}

// Выбираем из базы соответствующий метод оплаты
$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
if (empty($method)) {
    die("Неизвестный метод оплаты");
}

$settings = unserialize($method->settings);

$my_signature = md5(
    $batch_timestamp
    . $currency_id
    . $amount
    . $payment_method
    . $order_id
    . $site_order_id
    . $transaction_id
    . $payment_type
    . $rrn
    . $settings['webpay_shop_key']
);
if ($wsb_signature !== $my_signature) {
    die("bad sign\n");
}

if ($amount != $simpla->money->convert($order->total_price, $method->currency_id, false) || $amount <= 0) {
    die("incorrect price\n");
}

////////////////////////////////////
// Проверка наличия товара
////////////////////////////////////
$purchases = $simpla->orders->get_purchases(array('order_id' => intval($order->id)));
foreach ($purchases as $purchase) {
    $variant = $simpla->variants->get_variant(intval($purchase->variant_id));
    if (empty($variant) || (!$variant->infinity && $variant->stock < $purchase->amount)) {
        die("Нехватка товара $purchase->product_name $purchase->variant_name");
    }
}

// Установим статус оплачен
$simpla->orders->update_order(intval($order->id), array('paid' => 1));

// Спишем товары
$simpla->orders->close(intval($order->id));
$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));

die("OK" . $order_id . "\n");
