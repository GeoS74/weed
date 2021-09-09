<?
/*особенности работы с ЕИС

1) каталоги по 44-ФЗ и 223-ФЗ устроены совершенно по разному
2) в наименованиях регионов используется разная транслитерация
	Например:
		Altaj_Resp (44-ФЗ)
		Altay_Resp (223-ФЗ)
3) количество папок с регионами разное, в 223-ФЗ на 2 папки больше, чем в 44-ФЗ это:
	Irkutskaya_obl_Ust_Ordynskii_Buriatskii_okrug
	Zabaikalskii_krai_Aginskii_Buriatskii_okrug
4) структура xml файлов абсолютно разная, при это в рамках извещений по 44-ФЗ используется дополнительная схема xml для извещений, имеющих отношение к 504-ФЗ
	Какое именно отношение выяснить не удалось, такие извещения официально ничем не отличаются от простых по 44-ФЗ. Это следует учитывать при парсинге xml-файлов
	В рамках извещений по 44-ФЗ и 504-ФЗ есть несколько структур xml.
	имена узлов 2-го уровня отличаются:
	  ns2:epNotificationEOK
	  ns2:epNotificationEOKOU
	  ns2:epNotificationEZK
	  ns2:epNotificationEZP
	  ns2:fcsNotification111
	  ns2:fcsNotificationEF
	  ns2:fcsNotificationZakA
	  ns2:fcsNotificationZakK
	  ns2:fcsNotificationPO
	  ns2:fcsNotificationOK
	  ns2:fcsNotificationZK
	  ns2:epNotificationEOKD
	Как отличается вся структура этих извещений пока до конца не выяснено...
5) Имя узла 2-го уровня 615-ФЗ:
     ns2:pprf615NotificationEF
     ns2:pprf615NotificationPO
6) в рамках дня по 44-ФЗ формируется несколько архивов, причём, как правило, извещения находятся в первом архиве, в остальных архивах - файлы не представлящие интереса.
	по 223-ФЗ всё по другому, формируется 1 и более архивов. Причём в каждом находятся извещения и самое интересное имена файлов xml каждого архива повторяются.
	Т.е. если в первом архиве лежат файлы: file_name_01, file_name_02, то во втором архиве (если он будет создан) будут файлы с такими же именами file_name_01, file_name_02
7) даты в xml-файлах в формате ISO 8601 (пример: 2020-06-09T23:59:00+03:00)
	часовой пояс GMT+3
*/
class EISReader extends FTPReader implements ITenderReader
{
  protected $ftp_server;
  protected $ftp_user_name;
  protected $ftp_user_pass;

  protected $federal_law;
  
  protected $log_path = '/log/eis_reader';
  protected $log_file;


  public $count_regions = 0;   	//кол-во регионов
  public $count_notif = 0;  	//кол-во извещений



  public function read()
  {
	//запись лог-файла чтения извещений
	createDir($this -> log_path);
	$this -> progressLog();

	$this -> connect($this -> ftp_server, $this -> ftp_user_name, $this -> ftp_user_pass); //подключиться к FTP-серверу

	$region_list = $this -> getRegionList(); //получить весь список папок и файлов в разделе Регионы

	//$this -> disconnect();

	for($i = 0; $i < count($region_list); $i++) //проход по регионам
	//режим debuging 	
	//for($i = 0; $i < 1; $i++) //проход по регионам
	{
      //if($this -> federal_law == '44') $this -> count_regions_44++; //счётчик регионов
      //if($this -> federal_law == '223') $this -> count_regions_223++; //счётчик регионов

	  //$this -> connect($this -> ftp_server, $this -> ftp_user_name, $this -> ftp_user_pass); //подключиться к FTP-серверу
	  
	  
	  $this -> count_regions++; //счётчик регионов

	  $notification_list = $this -> getNotificationList( $region_list[$i] );
	  
	  //dump($notification_list);
	  //continue;

	  for($k = 0; $k < count($notification_list); $k++) //проход по архивам извещений
	  {
		//printf("<pre><b>архив: %s</b>", $notification_list[$k]);
		$this -> readNotification($notification_list[$k]);
      }
	  
	  
	  //$this -> disconnect();
	  
	}
	$this -> log();
  }
  




	/*функция для записи лог-файла чтения извещений.
	*	директория для лог-файла создаётся в конструкторе класса EISReader
	*/
	protected function progressLog()
	{
		$fp = fopen(ABS_PATH.$this -> log_path.'/'.$this -> log_file, 'w+t');

		fwrite($fp, json_encode($this -> getProgressReport()));

		fclose($fp);
	}


	protected function getProgressReport()
	{
		return array(
			'federal_law' => $this -> federal_law,
			'count_regions' => $this -> count_regions,
			'count_notif' => $this -> count_notif
		);
	}


  private function log()
  {
    if($this -> federal_law == '44')
	{
	  //$fz44 = $this -> count_notif_44;
	  
	  Log::write("/log/eis_reader", "\nвсего регионов по 44-ФЗ: ".$this -> count_regions);
	  Log::write("/log/eis_reader", sprintf("\nвсего извещений: \n\tпо 44-ФЗ: %s", $this -> count_notif) );
	  //Log::write("/log/eis_reader", sprintf("\nxml-файлы с неправильной структурой: %s", ($this -> count_notif - $fz44 ) ) );
	}

	if($this -> federal_law == '223')
	{
	  //$fz223 = $this -> count_notif_223;

	  Log::write("/log/eis_reader", "\nвсего регионов по 223-ФЗ: ".$this -> count_regions);
	  Log::write("/log/eis_reader", sprintf("\nвсего извещений: \n\tпо 223-ФЗ: %s", $this -> count_notif) );
	  //Log::write("/log/eis_reader", sprintf("\nxml-файлы с неправильной структурой: %s", ($this -> count_notif - $fz223) ) );
	}

	Log::write("/log/eis_reader", sprintf("\n------------------\n") );
  }



  /*Функция получает имя архива на FTP-сервере, затем:
  1) скачивает архив
  2) открывает архив
  3) читает архив
  4) закрывает архив
  5) удаляет архив
  */
  private function readNotification($archive)
  {
	$zip_archive = $this -> downloadFile($archive); //скачивание zip-архива извещений по ftp, возвращает путь до файла
		
	$zip = new ZipArchive();

	if($zip -> open($zip_archive) === true) //открытие zip-архива
	{
		for ($i = 0; $i < $zip -> numFiles; $i++) //чтение zip-архива
		{
			if( pathinfo($zip -> statIndex($i)['name'], PATHINFO_EXTENSION) == 'sig' ) continue; //не брать файлы с расширением .sig

			if(!$this -> XMLParser($zip -> getFromIndex($i), $archive, $zip -> statIndex($i)['name'])) continue; //прочитанный XML или пустой или не извещение
		}
		$zip -> close();
	}

	if(file_exists($zip_archive)) unlink($zip_archive); //удаление временного архива
  }

  
  
  
  
  
  
  
  
  
  

  private function XMLParser($xml, $archive, $name)
  {
	/*проверка на пустой xml
	*	в случае отсутствия извещений ЕИС формирует архив и вкладывает туда пустой xml-файл
	*	PS возможно что пустой файл может быть в архиве вместе с файлами извещений, пока это не выяснено до конца... возможны ошибки чтения
	*/
	if( !mb_strlen($xml) )
	{
		//printf("<pre>файл пустой: %s\n", $name);
		return false; //если xml-файл пустой прекратить чтение архива
	}

	//$this -> count_notif++; //переменная для подсчёта всех не пустых файлов. Нужна для проверки наличия файлов с неправильной структурой xml




	$dom = new domDocument;
	$dom -> encoding = CHARSET;
	$dom -> preserveWhiteSpace = false;
	$dom -> loadXML($xml);


	/*ATTENTION
	*	при чтении извещений по 44-ФЗ, ЕИС формирует архивы с номерами ..._002.xml.zip
	*	обычно в этих архивах находятся не извещения, а файлы содержащие какуя-то информацию о конкретном тендере.
	*	Возможно информация об отмене тендера, который отменяется в день публикации. Поэтому эти файлы попадают в одну выгрузку... возможно это не так...
	*	В таких файлах тег purchaseNumber присутствует.
	*	Для того чтобы понять является ли структура xml валидной используется тег placingWay, а не purchaseNumber.
	*/
	if($this -> federal_law == '44')
	{
		if(!$dom -> getElementsByTagName('purchaseNumber') -> length) //прочитанный XML не является извещением 44-ФЗ!!!
		{
			//printf("<pre>файл не является извещением: %s\nархив: %s\n", $name, $archive);
			//$this -> count_notif--; //надо уменьшать счётчик, т.к. возможен не корректный подсчет
			return false;
		}

		$node = $dom -> getElementsByTagName('purchaseNumber') -> item(0) -> nodeValue;
		//printf("<pre>номер извещения: %s\n", $node);

		$node = $dom -> getElementsByTagName('placingWay') -> item(0) -> nodeValue;
		//printf("<pre>тип конкурса: %s\n", $node);
		
		//$this -> count_notif_44++;
		
		//$this -> XMLControl($dom, $archive, $name);
	}


	if($this -> federal_law == '223')
	{
		$node = $dom -> getElementsByTagName('registrationNumber') -> item(0) -> nodeValue;
		//printf("<pre>номер извещения: %s\n", $node);

		//$this -> count_notif_223++;
	}

	$this -> count_notif++; //счётчик извещений

	if( !($this -> count_notif % 5) ) $this -> progressLog();

	return true;
  }



	/*есть закупки, у которых указывается МНН (международное непатентованное название) в основном лекарства
	*	у таких извещений нет кодов ОКПД и КТРУ
	*/
	private function getOKPD($dom)
	{
		if($node = $dom -> getElementsByTagName('OKPDCode') -> item(0))
		{
			return $node -> nodeValue;
		}
		else if($node = $dom -> getElementsByTagName('OKPD2') -> item(0))
		{
			if($okpd = $node -> getElementsByTagName('code') -> item(0) ) return $okpd -> nodeValue;
			else if($okpd = $node -> getElementsByTagName('OKPDCode') -> item(0)) return $okpd -> nodeValue;
		}
		return null;
	}

	private function XMLControl($dom, $archive, $name)
	{
		$result = array();
		$result['eis_num'] = 		$dom -> getElementsByTagName('purchaseNumber') -> item(0) -> nodeValue;
		$result['nomenclature'] = 	$dom -> getElementsByTagName('purchaseObjectInfo') -> item(0) -> nodeValue; //длина?
		$result['okpd'] = 			$this -> getOKPD($dom);
		$result['ktru'] = 			$dom -> getElementsByTagName('KTRU') -> item(0) 
										? $dom -> getElementsByTagName('KTRU') -> item(0) -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
		$result['customer'] = 		$dom -> getElementsByTagName('fullName') -> item(0) -> nodeValue; //длина?
		$result['customer_inn'] = 	$dom -> getElementsByTagName('INN') -> item(0) -> nodeValue;
		$result['eis_link'] = 		$dom -> getElementsByTagName('href') -> item(0) -> nodeValue;
		$result['etp_link'] = 		$dom -> getElementsByTagName('ETP') -> item(0)
										? $dom -> getElementsByTagName('ETP') -> item(0) -> getElementsByTagName('url') -> item(0) -> nodeValue : '';
		$result['etp'] = 			$dom -> getElementsByTagName('ETP') -> item(0)
										? $dom -> getElementsByTagName('ETP') -> item(0) -> getElementsByTagName('name') -> item(0) -> nodeValue : '';
		$result['sum'] = 			$dom -> getElementsByTagName('maxPrice') -> item(0) -> nodeValue; //тип float
		$result['currency'] = 		$dom -> getElementsByTagName('currency') -> item(0)
										? $dom -> getElementsByTagName('currency') -> item(0) -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
		$result['delivery_place'] = $dom -> getElementsByTagName('deliveryPlace') -> item(0) -> nodeValue; //длина?
		$result['selection_method'] = $dom -> getElementsByTagName('placingWay') -> item(0)
										? $dom -> getElementsByTagName('placingWay') -> item(0) -> getElementsByTagName('name') -> item(0) -> nodeValue : '';
		$result['selection_code'] = $dom -> getElementsByTagName('placingWay') -> item(0)
										? $dom -> getElementsByTagName('placingWay') -> item(0) -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
		
		
		//dump($result);
		
		$error = array();
		foreach($result as $k => $v)
		{
			if(!!$v) continue;
			
			if($result['okpd'] or $result['ktru']) continue;
			
			$error[] = sprintf("Error: пустое поле %s\n", $k);
			//printf("Error: пустое поле %s\nархив: %s\nфайл: %s\nкод процедуры: %s\n\n", $k, $archive, $name, $result['selection_code']);
		}
		
		if(count($error))
		{
			printf("архив: %s\nфайл: %s\nкод процедуры: %s\n\n", $archive, $name, $result['selection_code']);
			printf("%s\n\n\n", implode("", $error));
		}
		
		
		
		
		
		
		unset($result);
	}
  



  
  

    /*функция получает список всех папок и файлов в директории
	отфильтровывает лишнее и возвращает только нужные папки.
	в ЕИС используются разные правила транслитерации для тендеров 44-ФЗ и 223-ФЗ
	Например:
		Altaj_Resp (44-ФЗ)
		Altay_Resp (223-ФЗ)
	Также, различаются имена лишних папок
	*/
	private function getRegionList() //возвращает список регоинов, учитывая директорию на ЕИС
	{
		if($this -> federal_law == '44')
		{
			$region_list = $this -> readDirectory("/fcs_regions");
			$bad_directory = array( //не читать эти папки
				'/fcs_regions/_logs',
				'/fcs_regions/PG-PZ',
				'/fcs_regions/fcs_undefined',
				'/fcs_regions/directory.txt',
				'/fcs_regions/control99docs',
				'/fcs_regions/temp_err',
				'/fcs_regions/ERUZ',
			);
		}
		if($this -> federal_law == '223')
		{
			$region_list = $this -> readDirectory("/out/published");
			$bad_directory = array( //не читать эти папки
				'/out/published/undefined',
				'/out/published/ast',
				'/out/published/archive',
				
				//'/out/published/Zabaikalskii_krai_Aginskii_Buriatskii_okrug',   //этой папки нет в 44-ФЗ
				//'/out/published/Irkutskaya_obl_Ust-Ordynskii_Buriatskii_okrug', //этой папки нет в 44-ФЗ
			);
		}

		$region_list = array_filter((array)$region_list, function($v) //выкинуть имена файлов (предполагается, что в имени папок с регионами точка не используется)
		{
			return !mb_strpos($v, ".");
		});
		
		//выкинуть из списка лишние папки и вернуть массив
		//при этом необходимо переиндексировать массив функцией array_values
		return array_values( array_diff($region_list, $bad_directory) );
	}
  
  
	/*варианты поиска вчерашних извещений
	1) как вариант, можно было бы перебрать все архивы и сравнить дату создания с сегодняшней датой
			
			$last_mod = $this -> getFileLastMod( $region[$k] ); //получить время последнего редактирования файла
			if( date('Y-m-d', $last_mod) != date('Y-m-d', time()) ) continue; //взять файлы, созданные сегодня
	но при этом пришлось бы использовать метод getFileLastMod, который работает с FTP
	
	2) ориентироваться на дату выгрузки по имени архива. Это возможно т.к. имя архива формируется по определенному правилу, а именно
		
		purchaseNotice_Moskva_20200617_000000_20200617_235959_daily_001.xml (223-ФЗ) //обрати внимание ...20200617_000000_20200617...
		
		notification_Adygeja_Resp_2020061600_2020061700_001.xml (44-ФЗ) //обрати внимание ...2020061600_2020061700...
		
	это работает быстрее, ускоряет работу примерно в 2 раза (на тесте на локальной машине 108 сек против 227 сек)
		
	Прим.: архив создаётся сегодня, а в имени указана дата вчерашнего дня для 223-ФЗ и дата сегодняшнего И вчерашнего дня для 44-ФЗ		
	*/
	private function getNotificationList($region)
	{
		$notification_list = array();

		if($this -> federal_law == '44')
		{
			$notification_list = $this -> readDirectory( $region."/notifications/currMonth" ); //получить извещения текущего месяца
		  
			$notification_list = array_filter((array)$notification_list, function($v) //оставить только вчерашние извещения
			{
					$str = date('Ymd', time()-(60*60*24))."00_".date('Ymd', time())."00"; //формат даты в имени архива 2020061600_2020061700 (нас интересует вчера-сегодня)
					return mb_strpos($v, $str) !== false; //можно и так: return mb_strpos($v, $str), но это не совсем правильно, т.к. в случае если подстрока будет на позиции "0" обработает на правильно
			});
		}

		if($this -> federal_law == '223')
		{
			/*в разделе 223-ФЗ используется несколько директорий*/
			$directories = array(
				$region."/purchaseNotice/daily",
				$region."/purchaseNoticeAE/daily",
				$region."/purchaseNoticeAESMBO/daily",
				$region."/purchaseNoticeEP/daily",
				//$region."/purchaseNoticeIS/daily", //не используется, т.к. дата последнего изменения 2012г.
				$region."/purchaseNoticeKESMBO/daily",
				$region."/purchaseNoticeOA/daily",
				$region."/purchaseNoticeOK/daily",
				$region."/purchaseNoticeZK/daily",
				$region."/purchaseNoticeZKESMBO/daily",
				$region."/purchaseNoticeZPESMBO/daily",
			);

			foreach($directories as $v)
			{
				$notification_temp = $this -> readDirectory($v); //получить извещения текущего месяца
				
				//BUG DETECTED - надо проверять на тип, т.к. readDirectory может вернуть массив или false
				//если readDirectory вернёт false, то следующая инструкция array_filter вылетит с ошибкой 
				//	23.08.2021 - закомментировал проверку на массив, полагаюсь на приведение типа к array в array_filter
				//if(!is_array($notification_temp)) continue;
				
				$notification_temp = array_filter((array)$notification_temp, function($v) //оставить только вчерашние извещения
				{
					$str = date('Ymd', time()-(60*60*24) ); //формат даты в имени архива 20200617_000000_20200617 (нас интересует вчера-вчера)
					return mb_strpos($v, $str) !== false; //можно и так: return mb_strpos($v, $str), но это не совсем правильно, т.к. в случае если подстрока будет на позиции "0" обработает на правильно
				});
				$notification_list = array_merge($notification_list, $notification_temp);
			}
		}

		return array_values($notification_list); //необходимо переиндексировать массив функцией array_values
	}


	private function addNotification($dom)
	{
		//здесь работаем с DOM
		//...
	}
  
  
	function __construct($federal_law)
	{
		$this -> ftp_server = "ftp.zakupki.gov.ru";

		switch($federal_law)
		{
		  case '44':
				$this -> ftp_user_name = 'free';
				$this -> ftp_user_pass = 'free';
				
				$this -> log_file = 'temp_EISReader_44.txt';
			break;

		  case '223':
				$this -> ftp_user_name = 'fz223free';
				$this -> ftp_user_pass = 'fz223free';
				
				$this -> log_file = 'temp_EISReader_223.txt';
			break;

		  default: die('Ошибка создания объекта class: EISReader Необходимо указать один из федеральных законов (44 либо 223)');
		}
		
		$this -> federal_law = $federal_law;
	}

	function __destruct()
	{
		$this -> disconnect();
		
		//удалить лог-файл чтения извещений
		$progress_log_file = ABS_PATH.$this -> log_path.'/'.$this -> log_file;
		if( file_exists($progress_log_file) ) unlink($progress_log_file);
	}
}
?>