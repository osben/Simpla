<?php
header('Content-type: text/html; charset=utf-8'); 
error_reporting(E_ALL ^ E_NOTICE);
// ini_set('display_errors', 0);
?>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf8" />
  <meta http-equiv="Content-Language" content="ru" />
  <title>Установка Simpla</title>
</head>
<style>
  h1{font-size:26px; font-weight:normal}
  p{font-size:19px;}
  input{font-size:18px;}
  td{padding-right:15px;font-size:18px; font-family:tahoma, verdana;}
  p.error{color:red;}
  div.maindiv{width: 600px; height: 300px; position: relative; left: 50%; top: 100px; margin-left: -300px; }
</style>
<body>
<div style='width:100%; height:100%;'> 
  <div class="maindiv">
    <h1>Установка Simpla 2.3</h1>
    <?PHP install(); ?>
  </div>
</div>


</body>
</html>


<?php

//
//  Установка
//
function install()
{
	if(!isset($_GET['step']))
	{
		hello_screen();
	}
	else
	{
		$step = $_GET['step'];
		switch($step)
		{
			case 'database':
				dbconfig(); break;
			case 'license':
				license(); break;
			case 'admin':
				adminconf(); break;
			case 'final':
				finalstep(); break;
			default:		 
				hello_screen();
		} 
	}
}

//
//  Приветствие
//
function hello_screen()
{
	print "<p>
	Пользовательское соглашение:
	<textarea style='width:600; height:200;'>
	
Настоящее пользовательское соглашение (далее &mdash; Соглашение) является юридическим соглашением между Пользователем системы управления сайтами &laquo;Simpla cms&raquo; (далее &mdash; Продуктом) и Пикусовым Д. С. (далее &mdash; Автором).

Соглашение относится ко всем распространяемым версиям или модификациям программного Продукта. 

1. Все положения Соглашения распространяются как на Продукт в целом, так и на его отдельные компоненты, за исключением компонентов, описанных в п.7 данного Соглашения.

2. Соглашение вступает в силу непосредственно в момент получения Пользователем копии Продукта посредством электронных средств передачи данных либо на физических носителях.

3. Соглашение дает Пользователю право использовать Продукт в рамках одного сайта (интернет-магазина), который работает в пределах одного полного доменного имени.

4. Автор не несет ответственность за какие-либо убытки и/или ущерб (в том числе, убытки в связи недополученной коммерческой выгодой, прерыванием коммерческой и производственной деятельности, утратой данных), возникающие в связи с использованием или невозможностью использования Продукта, даже если Автор был уведомлен о возможном возникновении таких убытков и/или ущерба.

5. Продукт поставляется на условиях &laquo;как есть&raquo; без предоставления гарантий производительности, покупательной способности, сохранности данных, а также иных явно выраженных или предполагаемых гарантий. Автор не несёт какой-либо ответственности за причинение или возможность причинения вреда Пользователю, его информации или его бизнесу вследствие использования или невозможности использования Продукта.

6. Автор не несёт ответственность, связанную с привлечением Пользователя или третьих лиц к административной или уголовной ответственности за использование Продукта в противозаконных целях (включая, но не ограничиваясь, продажей через Интернет магазин объектов, изъятых из оборота или добытых преступным путём, предназначенных для разжигания межрасовой или межнациональной вражды и т.д.).

7. Продукт содержит компоненты, на которые не распространяется действие настоящего Соглашения. Эти компоненты предоставляются и распространяются свободно в соответствии с собственными лицензиями. Таковыми компонентами являются:
	- Визуальный редактор TinyMCE;
	- Файловый менеджер SMExplorer;
	- Менеджер изображений SMImage;
	- Редактор кода Codemirror;
	- Скрипт просмотра изображений EnlargeIt.

8. Пользователь не имеет права продавать или распространять Продукт без согласия Автора.

9. Пользователь имеет право модифицировать Продукт по своему усмотрению. При этом последующее использование Продукта должно осуществляться в соответствии с данным Соглашением и при условии сохранения всех авторских прав.

10. Автор оставляет за собой право в любое время изменять условия Соглашения без предварительного уведомления.

11. Получение экземпляра Продукта, его использование и/или хранение автоматически означает:
	а) осведомленность Пользователя о содержании Соглашения;
	б) принятие его положений;
	в) выполнение условий данного Соглашения.

Официальный сайт Продукта: http://simplacms.ru
	</textarea></p>";
	print "<p><form method=get><input type='hidden' name='step' value='database'><input type='submit' value='С соглашением согласен, начать установку →'></form></p>";
}

//
//  Настройка mysql
//
function dbconfig()
{
	$configfile = 'config/config.php';

	$dbhost = 'localhost';
	$dbname = '';
	$dbuser = '';
	$dbpassword = '';
	$error = '';
	
	if(isset($_POST['dbhost']))
		$dbhost = $_POST['dbhost'];
	if(isset($_POST['dbname']))
		$dbname = $_POST['dbname'];
	if(isset($_POST['dbuser']))
		$dbuser = $_POST['dbuser'];
	if(isset($_POST['dbpassword']))
		$dbpassword = $_POST['dbpassword'];
		
	if(!empty($dbname) && !empty($dbuser))
	{
		$GLOBALS['lk'] = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);
		if (mysqli_connect_errno()) {
			printf("Не удалось подключиться: Connect %s\n", mysqli_connect_error());
			exit();
		}

		if (!mysqli_query($GLOBALS['lk'], 'SET NAMES utf8') === TRUE) {
			printf("Не удалось подключиться: Query\n ");
		}
		
		if(!is_readable('simpla.sql'))
			$error = 'Файл simpla.sql не найден';
			
		if(!is_writable('config/config.php'))
			$error = 'Поставьте права на запись для файла config/config.php';
			
		if(empty($error))
		{
			mysqlrestore('simpla.sql');
			
			# Запишем конфиги с базой
			$conf = file_get_contents('config/config.php');
			$conf = preg_replace("/db_name.*;/i", 'db_name = "'.$dbname.'"', $conf);
			$conf = preg_replace("/db_server.*;/i", 'db_server = "'.$dbhost.'"', $conf);
			$conf = preg_replace("/db_user.*;/i", 'db_user = "'.$dbuser.'"', $conf);
			$conf = preg_replace("/db_password.*;/i", 'db_password = "'.$dbpassword.'"', $conf);
			$cf = fopen('config/config.php', 'w');
			fwrite($cf, $conf);
			fclose($cf);

			print "<p>База данных успешно настроена</p>";
			print "<p><form method=get><input type='hidden' name='step' value='admin'><input type='submit' value='продолжить →'></form></p>";
			exit();

		}
		
	}
	

	
	print "<p>Введите параметры базы данных MySQL</p>";
	if(empty($error))
		print "<p><b>Существующие данные в базе будут уничтожены!</b></p>";
	if(!empty($error))
		print "<p class=error>$error</p>";
	print "<p><form method=post><table>";
	print "<tr><td>Сервер</td><td><input type=text name=dbhost value='$dbhost'></td></tr>";
	print "<tr><td>Имя базы</td><td><input type=text name=dbname value='$dbname'></td></tr>";
	print "<tr><td>Логин</td><td><input type=text name=dbuser value='$dbuser'></td></tr>";
	print "<tr><td>Пароль</td><td><input type=password name=dbpassword value='$dbpassword'></td></tr>";
	print "<tr><td></td><td><input type='hidden' name='step' value='dbconfig'><input type='submit' value='продолжить →'></td></tr>";
	print "</table></form></p>";

}



//
//  Настройка лицензии
//
function license()
{
	$license = '';
	$error = '';

	$end = date("Y-m-d", time()+60*60*24*31); // на 31 день
	$c = '';
	for($i=0; $i<rand(30, 40); $i++)
	{
		$c .= rand(0,9);
	}
	$license = getenv("HTTP_HOST").'#'.$end.'#'.$c;
	$p = 11;
	$g = 2;
	$x = 7; //rand(2, $p-1);
	$y = pow($g, $x) % $p; // same as x

	$message = bin2hex($license);
	$message = str_split($message, 4);

	$testlicense = '';
	$shift = $x;

	foreach($message as $block)
	{	
		$block = base_convert($block, 16, 10);
		$enc_block = '';
		for($i = 0; $i < strlen($block); $i++)
		{
			$k = rand(2, $p - 1);
			$a = pow($g, $k) % $p + ($i + $shift) % 26;
			$b = ( (pow($y % $p, $k) % $p) * ($block[$i]) ) % $p + ($i + $shift) % 25;

			$enc_block .= base_convert($a, 10, 36).base_convert($b, 10, 36);
		}
		$testlicense .= $enc_block.' ';
		$shift += $x;
	}

	if(!empty($_POST['license']))
	{ 
		$license = $_POST['license'];
		if(!$enddate = check_license($license))
		{
			$error = 'Лицензия недействительна';
		}
		elseif(!is_writable('config/config.php'))
		{
			$error = 'Поставьте права на запись для файла config/config.php';
		}
		else
		{ 
			# Запишем конфиги с базой
			$conf = file_get_contents('config/config.php');
			$conf = preg_replace("/license.*/i", 'license = "'.$license.'"', $conf);
			$cf = fopen('config/config.php', 'w');
			fwrite($cf, $conf);
			fclose($cf);
			
			if ($enddate != '*')
				print "<p>Ваша тестовая лицензия действительна до: $enddate;</p>";
			else
				print "<p>Благодарим вас за использование лицензионной версии Simpla!</p>";

			print "<p><form method=get><input type='hidden' name='step' value='final'><input type='submit' value='продолжить →'></form></p>";
			exit();
		}

	 
		
	}

	print "<p>Для работы Simpla необходим лицензионный ключ:</p>";
	if(!empty($error))
		print "<p class=error>$error</p>";
	print "<p><form method=post name=license><textarea name=license style='width:600px; height:100px;'>".$_POST['license']."</textarea>";
	print "<table><tr><td><p><input type='button' value='получить тестовый ключ' onclick=\"document.license.license.value='$testlicense';\"></p></td><td><p><input type='hidden' name='step' value='license'><input type='submit' value='продолжить →'></form></p></td></tr></table>";
	

}

// 
//  Установка пароля администратора
//
function adminconf()
{
	$current_dir = dirname(__FILE__);
	$error = '';
	if(isset($_POST['login']) && isset($_POST['password']))
	{

		if(!is_writable($current_dir.'/simpla/.passwd'))
			$error = 'Поставьте права на запись для файла '.$current_dir.'/simpla/.passwd';
		
		if(!is_writable($current_dir.'/simpla/.htaccess'))
			$error = 'Поставьте права на запись для файла '.$current_dir.'/simpla/.htaccess';
		
		if(empty($error))
		{
			$login = $_POST['login'];
			$password = $_POST['password'];
			$encpassword = crypt_apr1_md5($password);
			
			$path_to_passwd = $current_dir.'/simpla/.passwd';

			$passstring = $login.':'.$encpassword;
			$passfile = fopen($path_to_passwd, 'w');
			fputs($passfile, $passstring);
			fclose($passfile);
			
			
    		$htaccess = file_get_contents($current_dir.'/simpla/.htaccess');
    		$htaccess = preg_replace("/AuthUserFile .*\n/i", "AuthUserFile $path_to_passwd\n", $htaccess);

    		$htafile = fopen($current_dir.'/simpla/.htaccess', 'w');
    		fwrite($htafile, $htaccess);
    		fclose($htafile);	
    		print "<p>Пароль администратора установлен успешно. Не забудьте его.</p>";
			print "<p><form method=get><input type='hidden' name='step' value='license'><input type='submit' value='продолжить →'></form></p>";
    		exit();
    		
    				
		}
	
	}

	print "<p>Задайте логин и пароль администратора сайта.</p>";
	if(!empty($error))
		print "<p class=error>$error</p>";
	print "<p><form method=post><table>";
	print "<tr><td>Логин</td><td><input type=text name=login value='".$_POST['login']."'></td></tr>";
	print "<tr><td>Пароль</td><td><input type=password name=password value='".$_POST['password']."'></td></tr>";
	print "<tr><td></td><td><input type='hidden' name='step' value='adminconf'><input type='submit' value='продолжить →'></td></tr>";
	print "</table></form></p>";
}


//
//  Конечный шаг
//
function finalstep()
{
	@unlink('simpla.sql');
	@unlink('install.php');
	
	if(is_file('install.php'))
		$error = 'Обязательно удалите файлы install.php и simpla.sql';	

	$url = rtrim('http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']), '/');
	print "<p>Установка завершена успешно</p>";
	print "<p>Адрес вашего сайта: <a href='$url'>$url</a></p>";
	print "<p>Панель управления: <a href='$url/simpla'>$url/simpla</a></p>";
	print "<p>Приятной работы с Simpla!</p>";
	if(!empty($error))
		print "<p class=error>$error</p>";
}




function crypt_apr1_md5($plainpasswd) {
    $salt = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
    $len = strlen($plainpasswd);
    $text = $plainpasswd.'$apr1$'.$salt;
    $bin = pack("H32", md5($plainpasswd.$salt.$plainpasswd));
    for($i = $len; $i > 0; $i -= 16) { $text .= substr($bin, 0, min(16, $i)); }
    for($i = $len; $i > 0; $i >>= 1) { $text .= ($i & 1) ? chr(0) : $plainpasswd{0}; }
    $bin = pack("H32", md5($text));
    for($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $plainpasswd : $bin;
        if ($i % 3) $new .= $salt;
        if ($i % 7) $new .= $plainpasswd;
        $new .= ($i & 1) ? $bin : $plainpasswd;
        $bin = pack("H32", md5($new));
    }
    for ($i = 0; $i < 5; $i++) {
        $k = $i + 6;
        $j = $i + 12;
        if ($j == 16) $j = 5;
        $tmp = $bin[$i].$bin[$k].$bin[$j].$tmp;
    }
    $tmp = chr(0).chr(0).$bin[11].$tmp;
    $tmp = strtr(strrev(substr(base64_encode($tmp), 2)),
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
    "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");
    return "$"."apr1"."$".$salt."$".$tmp;
}



##################################################################
##################################################################


function mysqlrestore($filename)
{
  $templine = '';
  $fp = fopen($filename, 'r');

  // Loop through each line
  if($fp)
  while(!feof($fp)) {
    $line = fgets($fp);
    // Only continue if it's not a comment
    if (substr($line, 0, 2) != '--' && $line != '') {
      // Add this line to the current segment
      $templine .= $line;
      // If it has a semicolon at the end, it's the end of the query
      if (substr(trim($line), -1, 1) == ';') {
        // Perform the query
        mysqli_query($GLOBALS['lk'], $templine) or print('Error performing query \'<b>' . $templine . '</b>\': ' . mysqli_error() . '<br /><br />');
        // Reset temp variable to empty
        $templine = '';
      }
    }
  }

  fclose($fp);
}

##################################################################
##################################################################


function check_license($license)
{
		$p=11; $g=2; $x=7; $r = ''; $s = $x;
		$bs = explode(' ', $license);		
		foreach($bs as $bl){
			for($i=0, $m=''; $i<strlen($bl)&&isset($bl[$i+1]); $i+=2){
				$a = base_convert($bl[$i], 36, 10)-($i/2+$s)%26;
				$b = base_convert($bl[$i+1], 36, 10)-($i/2+$s)%25;
				$m .= ($b * (pow($a,$p-$x-1) )) % $p;}
			$m = base_convert($m, 10, 16); $s+=$x;
			for ($a=0; $a<strlen($m); $a+=2) $r .= @chr(hexdec($m{$a}.$m{($a+1)}));}

		@list($l->domains, $l->expiration, $l->comment) = explode('#', $r, 3);

		$l->domains = explode(',', $l->domains);

		$h = getenv("HTTP_HOST");
		if(substr($h, 0, 4) == 'www.') $h = substr($h, 4);
		if(!in_array($h, $l->domains) || (strtotime($l->expiration)<time() && $l->expiration!='*'))
			return false;
 		else
 		{
 			return $l->expiration;
		}

}
