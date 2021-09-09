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
  
  protected $errors = array();


  public $count_notif = 0;  //кол-во извещений
  public $count_notif_44 = 0;  //кол-во извещений
  public $count_notif_504 = 0; //кол-во извещений
  public $count_notif_615 = 0; //кол-во извещений
  public $count_notif_223 = 0; //кол-во извещений

  public $count_regions_44 = 0;   //кол-во регионов
  public $count_regions_223 = 0;   //кол-во регионов



  public function read()
  {
	$this -> connect($this -> ftp_server, $this -> ftp_user_name, $this -> ftp_user_pass); //подключиться к FTP-серверу

	$region_list = $this -> getRegionList(); //получить весь список папок и файлов в разделе Регионы

	for($i = 0; $i < count($region_list); $i++) //проход по регионам
	{
      if($this -> federal_law == '44') $this -> count_regions_44++; //счётчик регионов
      if($this -> federal_law == '223') $this -> count_regions_223++; //счётчик регионов

	  $notification_list = $this -> getNotificationList( $region_list[$i] );

	  for($k = 0; $k < count($notification_list); $k++) //проход по архивам извещений
	  {
		if( !$this -> readNotification($notification_list[$k]) ) break; //если получен файл не являющийся извещением перейти к следующему региону
      }
	}
	$this -> log();
  }
  
  public function getErrors()
  {
	if( count($this -> errors) ) return $this -> errors;
	else false;
  }
  
  protected function setErrors( $error )
  {
	$this -> errors[] = $error;
  }
  
  
  
  
  private function log()
  {
    if($this -> federal_law == '44')
	{
	  $fz44 = $this -> count_notif_44;
	  $fz504 = $this -> count_notif_504;
	  $fz615 = $this -> count_notif_615;
	  
	  Log::write("/log/eis_reader", "\nвсего регионов по 44-ФЗ: ".$this -> count_regions_44);
	  Log::write("/log/eis_reader", sprintf("\nвсего извещений: %s \n\tпо 44-ФЗ: %s \n\tпо 504-ФЗ: %s \n\tпо 615-ФЗ: %s", ($fz44 + $fz504 + $fz615), $fz44, $fz504, $fz615) );
	  Log::write("/log/eis_reader", sprintf("\nxml-файлы с неправильной структурой: %s", ($this -> count_notif - ($fz44 + $fz504 + $fz615) ) ) );
	}
	
	if($this -> federal_law == '223')
	{
	  $fz223 = $this -> count_notif_223;

	  Log::write("/log/eis_reader", "\nвсего регионов по 223-ФЗ: ".$this -> count_regions_223);
	  Log::write("/log/eis_reader", sprintf("\nвсего извещений: \n\tпо 223-ФЗ: %s", $fz223) );
	  Log::write("/log/eis_reader", sprintf("\nxml-файлы с неправильной структурой: %s", ($this -> count_notif - $fz223) ) );
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
		
    $zip = zip_open($zip_archive); //открытие zip-архива

	if($zip)
    {
	  while($zip_entry = zip_read($zip)) //чтение zip-архива
      {
		if( pathinfo(zip_entry_name($zip_entry), PATHINFO_EXTENSION) == 'sig' ) continue; //не брать файлы с расширением .sig

			/**справочно (полезные плюшки)
			//zip_entry_name($zip_entry)              //название xml-файла
			//zip_entry_filesize($zip_entry)          //исходный размер
			//zip_entry_compressedsize($zip_entry)    //сжатый размер
			//zip_entry_compressionmethod($zip_entry) //метод сжатия
			/**/

        if(zip_entry_open($zip, $zip_entry, "r"))
		{
          $xml = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)); //чтение файла

		  if( $this -> XMLParser( $xml, $archive, zip_entry_name($zip_entry) ) ) $xml_valid = true; //это xml-файл с извещением
		  else $xml_valid = false; //XMLParser получил не извещение

          zip_entry_close($zip_entry);
        }
		//использовать это только после всех тестов с xml-структурой
		if(!$xml_valid) break; //если получено не извещение завершить цикл
      }
      zip_close($zip);
	}
    unlink($zip_archive); //удаление временного архива

	return $xml_valid; //
	//if($xml_valid) return true; //чтение архива закончено, в прочитаном архиве были xml извещения
	//else return false; //в прочитанном архиве нет извещений
  }


  private function XMLParser($xml, $archive, $name)
  {
	/*проверка на пустой xml
	в случае отсутствия извещений ЕИС формирует архив и вкладывает туда пустой xml-файл
	PS возможно что пустой файл может быть в архиве вместе с файлами извещений, пока это не выяснено до конца... возможны ошибки чтения
	*/
	if( !mb_strlen($xml) ) return false; //если xml-файл пустой прекратить чтение архива
	
	$this -> count_notif++; //переменная для подсчёта всех не пустых файлов. Нужна для проверки наличия файлов с неправильной структурой xml
	
    $dom = new domDocument;

    $dom -> encoding = CHARSET;
    $dom -> preserveWhiteSpace = false;

    $dom -> loadXML($xml);


    $xpath = new DOMXPath( $dom );




	if($this -> federal_law == '44')
    {
	  $xpath->registerNamespace('eis', "http://zakupki.gov.ru/oos/types/1"); //регистрируем URI пространства имён и префикс с объектом DOMXPath



	  if($xpath -> query( '/ns2:export/ns2:fcsNotificationEF' ) -> length) //это 44-ФЗ
	  {
		$this -> count_notif_44++;
		
		Log::write("/log/eis_reader/44-1", $archive);
        Log::write("/log/eis_reader/44-1", "\nфайл: ".$name);
		Log::write("/log/eis_reader/44-1", "\nизвещение: ".$xpath -> query('/*/*/eis:purchaseNumber')[0] -> nodeValue."\n");
	  } 
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotification111' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotificationZakA' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotificationZakK' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotificationPO' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotificationOK' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:fcsNotificationZK' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }
	  
	  else if($xpath -> query( '/ns2:export/ns2:epNotificationEOKD' ) -> length) //это ещё один 44-ФЗ
	  {
		$this -> count_notif_44++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:epNotificationEOK' ) -> length) //это 504-ФЗ
	  {
		$this -> count_notif_504++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:epNotificationEOKOU' ) -> length) //это 504-ФЗ
	  {
		$this -> count_notif_504++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:epNotificationEZK' ) -> length) //это 504-ФЗ
	  {
		$this -> count_notif_504++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:epNotificationEZP' ) -> length) //это 504-ФЗ
	  {
		$this -> count_notif_504++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:pprf615NotificationEF' ) -> length) //это 615-ФЗ
	  {
		$this -> count_notif_615++;
	  }

	  else if($xpath -> query( '/ns2:export/ns2:pprf615NotificationPO' ) -> length) //это 615-ФЗ
	  {
		$this -> count_notif_615++;
	  }

	  else //возврат в случае НЕ правильной структуры xml
	  {
	    if( mb_strpos($archive, '_001.xml.zip') ) //ищем неправильную xml структуру только в первом архиве (по 44-ФЗ в остальных архивах скорее вссего не извещения)
		{
          Log::write("/log/eis_reader", "\n".$archive);
          Log::write("/log/eis_reader", "\nфайл: ".$name);
		  
		  $this -> setErrors( array('archive' => $archive, 'file' => $name) );
		}
		return false;
	  }
	}

	if($this -> federal_law == '223')
    {
	  $xpath->registerNamespace('eis', "http://zakupki.gov.ru/223fz/types/1"); //регистрируем URI пространства имён и префикс с объектом DOMXPath

	  if($xpath -> query( '/ns2:purchaseNotice/ns2:body/ns2:item' ) -> length) //это 223-ФЗ
	  {
		$this -> count_notif_223++;
	  }

	  else //возврат в случае НЕ правильной структуры xml
	  {
          Log::write("/log/eis_reader", "\n".$archive);
          Log::write("/log/eis_reader", "\nфайл: ".$name);

		  $this -> setErrors( array('archive' => $archive, 'file' => $name) );

		  return false;
	  }
	}

	return true; //возврат в случае правильной структуры xml
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

	$region_list = array_filter($region_list, function($v) //выкинуть имена файлов (предполагается, что в имени папок с регионами точка не используется)
      {
        return !mb_strpos($v, ".");
      }
    );
	
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
	if($this -> federal_law == '44')
    {
      $notification_list = $this -> readDirectory( $region."/notifications/currMonth" ); //получить извещения текущего месяца
	  
	  $notification_list = array_filter($notification_list, function($v) //оставить только вчерашние извещения
        {
		  $str = date('Ymd', time()-(60*60*24))."00_".date('Ymd', time())."00"; //формат даты в имени архива 2020061600_2020061700 (нас интересует вчера-сегодня)
          return mb_strpos($v, $str) !== false; //можно и так: return mb_strpos($v, $str), но это не совсем правильно, т.к. в случае если подстрока будет на позиции "0" обработает на правильно
        }
      );

	  return array_values($notification_list); //необходимо переиндексировать массив функцией array_values
	}

	if($this -> federal_law == '223')
	{
	  $notification_list = $this -> readDirectory( $region."/purchaseNotice/daily" ); //получить извещения текущего месяца
	  
	  $notification_list = array_filter($notification_list, function($v) //оставить только вчерашние извещения
        {
		  $str = date('Ymd', time()-(60*60*24) ); //формат даты в имени архива 20200617_000000_20200617 (нас интересует вчера-вчера)
          return mb_strpos($v, $str) !== false; //можно и так: return mb_strpos($v, $str), но это не совсем правильно, т.к. в случае если подстрока будет на позиции "0" обработает на правильно
        }
      );

	  return array_values($notification_list); //необходимо переиндексировать массив функцией array_values
	}
  }


  private function addNotification($dom)
  {
	//здесь работаем с DOM
  }
  
  
  function __construct($federal_law)
  {
    $this -> ftp_server = "ftp.zakupki.gov.ru";
	
    switch($federal_law)
	{
	  case '44':
	      $this -> ftp_user_name = 'free';
	      $this -> ftp_user_pass = 'free';
	    break;

	  case '223':
	      $this -> ftp_user_name = 'fz223free';
	      $this -> ftp_user_pass = 'fz223free';
	    break;

	  default: die('Ошибка создания объекта class: EISReader Необходимо указать один из федеральных законов (44 либо 223)');
	}
	
	$this -> federal_law = $federal_law;
  }
  
  function __destruct()
  {
    $this -> disconnect();
  }
}
?>