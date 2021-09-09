<?
ini_set('max_execution_time', '3600');
ini_set('memory_limit', '1024M');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");

trimer($_p);

//print_r($_p);

class Foo
{
	function __construct()
	{
		ob_start();
	}
	
	function __destruct()
	{
		$buffer = ob_get_contents();
		ob_end_clean();
		if(mb_strlen($buffer)) Log::write("/log/eis_reader/error", $buffer."\n");
	}
}


switch($_p['name_task'])
{
	case 'read_eis_44':
		if($_p['event'] === 'run') readNotification(44);
		else $_RESULT = readProgressLog(ABS_PATH.'/log/eis_reader/temp_EISReader_44.txt');
		break;
		
	case 'read_eis_223':
		if($_p['event'] === 'run') readNotification(223);
		else $_RESULT = readProgressLog(ABS_PATH.'/log/eis_reader/temp_EISReader_223.txt');
		break;
	
	case 'read_okpd2':
		if($_p['event'] === 'run')
		{
			$reader = new OKPD2Reader(44);
			$reader -> read();
		}
		else $_RESULT = readProgressLog(ABS_PATH.'/log/eis_reader/temp_OKPD2Reader.txt'); 
		break;
}


function readNotification($federal_law)
{
	$timer_start = microtime(true);

	$reader = new EISReader($federal_law);
	$reader -> read();

	print_r('Всего извещений по '.$federal_law.'-ФЗ: '.$reader -> count_notif."\n");
	print_r('Время чтения '.$federal_law.'-ФЗ: '.round( (microtime(true) - $timer_start), 3)." сек\n");
}


function readProgressLog($path)
{
	sleep(2); //(необходима задержка выполнения)
	return file_exists($path) ? json_decode(file_get_contents($path), true) : null;
}
?>