<?
/*
Есть 2 пути выполнить скрипт с помощью планировщика crontab
	1) обратиться к нему по HTTPS;
	2) обратиться к файлу напрямую.
Первый вариант не подходит, т.к. это открывает доступ к запуску задания из вне, поэтому был выбран второй путь.
В этом случае для успешной работы скрипта необходимо чтобы он частично выполнял функции главного контроллера.
А именно, он должен подключить конфигурационный файл, подключить функции и классы.
Изначально хотелось убрать скрипт в поддиректорию, но... появилась проблема:
не смог разобраться как подключить конфигурационный файл из поддиректории
Инструкция типа:
	include_once '../config.php';
не отрабатывает...

При этом если проверить путь для подключения файлов, который используется функциями типа include() так:
	echo get_include_path(). "\n";
то скрипт вернет такой путь:
	.:/usr/share/php
Естественно, что в этой директории (которая кстати недоступна) ничего не лежит
Проблема решается применением следующей инструкции:
	set_include_path('pricestorm.ru/public_html');
она устанавливает корневую папку сайта, как путь по умолчанию.
Вопрос закрыт. Скрипт отрабатывает из любой вложенной директории.

И ещё:
	getenv('DOCUMENT_ROOT');
вернёт:
	/home/r/rudieboy74/pricestorm.ru/public_html
*/

set_include_path('pricestorm.ru/public_html');

ini_set('max_execution_time', '3600'); //30 минут + 30 минут
ini_set('memory_limit', '1024M'); 


/*BUG DETECTED
при запуске скрипта через crontab обнаружился ещё один забавный баг:
константы ABS_PATH и ROOT_PATH не объявлялись, из-за этого не отрабатывала функция создания директории.
Причём ABS_PATH в принципе не инициализировалась, а ROOT_PATH инициализировалась с пустым значением и дальше её нельзя было изменить.
Поэтому обе константы устанавливаются здесь и ДО подключения файла config.php
*/

define('ABS_PATH',  "/home/r/rudieboy74/pricestorm.ru/public_html");
define('ROOT_PATH', "/home/r/rudieboy74/pricestorm.ru/public_html");




//config
include_once 'config.php';

//include functions
include_once ABS_PATH.'/lib/gs_functions.inc';




//autoload class
spl_autoload_register( function( $class )
{
  if( file_exists( ABS_PATH.'/php/class/base/'.$class.'.php' ) )
  {
    include_once ABS_PATH.'/php/class/base/'.$class.'.php';
  }
  else
  {
    include_once ABS_PATH.'/php/class/'.$class.'.php';
  }
}); 




function sendError($reader, $message) //служебная функция отправки ошибок на e-mail
{
  $err = $reader -> getErrors();
  
  $mail = new Mail;
  
  $mail
        -> setTo( 'gsirotkin@yandex.ru' )
        -> setFrom("noreply@pricestorm.ru")
		-> setSubject($message)
		;

  for($i = 0; $i < count($err); $i++)
  {
	$mail -> setMessage( $err[$i]."\r\n" );
  }
  
  $mail -> send();
}




$start = microtime(true);
$base_memory_usage = memory_get_usage();


$reader = new OKPD2Reader(44);

$reader -> read();

if($reader -> getErrors()) sendError($reader, "Ошибки формирования ОКПД2"); //проверка на ошибки





$time = microtime(true) - $start;
$memory = memory_get_usage()-$base_memory_usage;
Log::write("/log/okpd_2_reader", sprintf("\nвремя работы скрипта: %s сек\r\nиспользование памяти %d байт\r\n", round($time, 2), $memory) );


$mail = new Mail;

	  $mail
        -> setTo( 'gsirotkin@yandex.ru' )
        -> setFrom("noreply@pricestorm.ru")
		-> setSubject("Формирование справочника ОКПД2 успешно выполнено")
        -> setMessage("Справочник состоит из ".$reader -> count_position." позиций"."\r\n")
        -> setMessage("Это письмо сформировано автоматически. Отвечать на него не нужно.")
        -> send()
		;
?>