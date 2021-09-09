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
        -> setTo("gsirotkin@yandex.ru")
        -> setFrom("noreply@pricestorm.ru")
		-> setSubject($message)
		;

	for($i = 0; $i < count($err); $i++)
	{
		$mail
			-> setMessage( "Ошибка: ".$err[$i]['message']."\r\n" )
			-> setMessage( "Архив: ".$err[$i]['archive']."\r\n" )
			-> setMessage( "Файл: ".$err[$i]['file']."\r\n\r\n" )
			;
	}
	$mail -> send();
}


ob_start();

$start = microtime(true);
$base_memory_usage = memory_get_usage();





$reader = new EISReader(44);

$reader -> read();

//$fz44 = $reader -> count_notif_44;
//$fz504 = $reader -> count_notif_504;
//$fz615 = $reader -> count_notif_615;
//$count_notif_44_all = $reader -> count_notif;

$count_regions_44 = $reader -> count_regions;
$count_notif_44   = $reader -> count_notif;

//проверка на ошибки
if($reader -> getErrors()) sendError($reader, "Ошибки при чтении извещений 44-ФЗ");





$reader = new EISReader(223);

$reader -> read();

//$fz223 = $reader -> count_notif_223;
//$count_notif_223_all = $reader -> count_notif;

$count_regions_223 = $reader -> count_regions;
$count_notif_223   = $reader -> count_notif;


//проверка на ошибки
if($reader -> getErrors()) sendError($reader, "Ошибки при чтении извещений 223-ФЗ");


$time = microtime(true) - $start;
$memory = memory_get_usage() - $base_memory_usage;
Log::write("/log/eis_reader", sprintf("\nвремя работы скрипта: %s сек\r\nиспользование памяти %d байт\r\n", round($time, 2), $memory) );


$mail = new Mail;

	$mail
        -> setTo( 'gsirotkin@yandex.ru' )
        -> setFrom("noreply@pricestorm.ru")
		-> setSubject("Чтение извещений ЕИС успешно выполнено")
        -> setMessage( "Регионов по 44-ФЗ: ".$count_regions_44."\r\n" )
        -> setMessage( "Извещений по 44-ФЗ: ".$count_notif_44."\r\n" )
		-> setMessage( "\r\n" )
		-> setMessage( "Регионов по 223-ФЗ: ".$count_regions_223."\r\n" )
		-> setMessage( "Извещений по 223-ФЗ: ".$count_notif_223."\r\n" )
        -> setMessage("Это письмо сформировано автоматически. Отвечать на него не нужно.")
        -> send()
		;

$buffer = ob_get_contents();
ob_end_clean();
if(mb_strlen($buffer)) Log::write("/log/eis_reader/error", $buffer."\n");
?>