<?php
/**
 * @name Интеркасса 2.0
 * @description Модуль разработан в компании GateOn предназначен для CMS Simpla 2.3.7
 * @author www.gateon.net
 * @email www@smartbyte.pro
 * @version 1.1
 * @update 22.10.2016
 */

//Проверка ответа и его айпи
if(count($_POST) && isset($_POST['ik_co_id']) && checkIP()){
	chdir ('../../');
	require_once('api/Simpla.php');
	$simpla = new Simpla();

	//Получение всех нужных настроек
	$order = $simpla->orders->get_order(intval($_REQUEST['ik_pm_no']));
	$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
	$ik_settings = unserialize($method->settings);
	
	//Проверяем все другие поля
	if($ik_settings['ik_co_id'] == $_POST['ik_co_id'] && $order->total_price == $_POST['ik_am'] && $order->id == $_POST['ik_pm_no']) {
		if(isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts'){
			$key = $ik_settings['t_key'];
		} else {
			$key = $ik_settings['s_key'];
		}

		$request = $_POST;
		
		//удаляем все поле которые не принимают участия в формировании цифровой подписи
		foreach ($request as $key => $value) {
			if (!preg_match('/ik_/', $key)) continue;
			$dataSet[$key] = $value;
		}

		$request_sign = $request['ik_sign'];
		unset($request['ik_sign']);

		ksort($request, SORT_STRING);
		array_push($request, $key);
		$str = implode(':', $request);
		$sign = base64_encode(md5($str, true));

		//Только при совпадении цифровых подписей заказ станет успешным
		if($request_sign == $sign){
			//Смена статуса оплаты в админке
			$simpla->orders->update_order(intval($order->id), array('paid'=>1));
		}
		$simpla->notify->email_order_user(intval($order->id));
		$simpla->notify->email_order_admin(intval($order->id));
		$simpla->orders->close(intval($order->id));

	}
}

function wrlog($content){
	$file = 'log.txt';
	$doc = fopen($file, 'a');
	file_put_contents($file, PHP_EOL . $content, FILE_APPEND);	
	fclose($doc);
}
function checkIP(){
	$ip_stack = array(
		'ip_begin'=>'151.80.190.97',
		'ip_end'=>'151.80.190.104'
	);
	if(!ip2long($_SERVER['REMOTE_ADDR'])>=ip2long($ip_stack['ip_begin']) && !ip2long($_SERVER['REMOTE_ADDR'])<=ip2long($ip_stack['ip_end'])){
		$this->wrlog('REQUEST IP'.$_SERVER['REMOTE_ADDR'].'doesnt match');
		die('Ты мошенник! Пшел вон отсюда!');
	}
	return true;
}