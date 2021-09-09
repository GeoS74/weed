<?
/*
пример кода ОКПД2 23.19.12.119
XX - класс
XX.X - подкласс
XX.XX - группа
XX.XX.X - подгруппа
XX.XX.XX - вид
XX.XX.XX.XX0 - категория
XX.XX.XX.XXX - подкатегория

почитать про ОКПД2 https://www.audit-it.ru/terms/accounting/okpd.html
*/
class OKPD2Reader extends EISReader
{
  public $count_position; //переменная для подсчёта позиций справочника

  private $db_table_name = 'tender_okpd2'; //таблица справочника

  private $temp_table_1 = 'tender_okpd_2_position'; //временная таблица позиций справочника
  private $temp_table_2 = 'tender_okpd_2_link';     //временная таблица связей позиций справочника через id

  private $temp_data = array(); //временный массив для накопления позиций справочника

  private $registry; //свойство содержит объект Registry
  
  //protected $log_path определен в супер-классе
  



  function __construct($federal_law)
  {
    parent::__construct($federal_law);

	$this -> log_file = 'temp_OKPD2Reader.txt';
  }
  
  
	protected function getProgressReport()
	{
		return array(
			'step' => ++$this -> count_step,
		);
	}



  /*создание справочника ОКПД2
  1) записать все позиции справочника во временную таблицу
  2) установить связи позиций между собой и записать данные во 2-ю временную таблицу
  3) объеденить обе временные таблицы и создать результирующую таблицу
  4) проверка на ошибки
  5) удалить временные таблицы
  */
  public function read()
  {
	//запись лог-файла чтения извещений
	createDir($this -> log_path);
	$this -> progressLog();
	

    $this -> connect($this -> ftp_server, $this -> ftp_user_name, $this -> ftp_user_pass); //подключиться к FTP-серверу

	$okpd2_list = $this -> getOKPD2List(); //получить список имен архивов с данными ОКПД2 по вчерашней выгрузке



	//step 1 - создание 1-ой временной таблицы (позиции справочника)
	
	$this -> registry = new Registry;
	
	//BUG REPORT - в таблице необходимо иметь поле TEXT(1000), т.к. названия некоторых позиций очень большие
	$this -> registry
  	  -> mysql_qw( 'CREATE TABLE IF NOT EXISTS '.$this -> temp_table_1.' 
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						date_create VARCHAR(255),
						name TEXT(1000),
						code VARCHAR(14)
						) ENGINE=MyISAM'
					);
	
	$this -> registry -> setDatabaseTableName( $this -> temp_table_1 ); //временная таблица позиций справочника

	for($i = 0; $i < count($okpd2_list); $i++) //проход по архивам справочника
    {
      $this -> readOKPD2Parts( $okpd2_list[$i] );
    }
	
    if( count($this -> temp_data) ) //если временный массив не пуст, дописать данные в БД
	{
	  $this -> registry -> add( $this -> temp_data );
	  $this -> temp_data = array();
	}

	$this -> progressLog();

	//step 2 - создание 2-ой временной таблицы (связи позиций через id)
	
	$this -> registry = new Registry; //создать новый объект, для того чтобы сбросить модель данных
	
	$this -> registry -> setDatabaseTableName( $this -> temp_table_2 ); //временная таблица позиций связей id позиций справочника

	$this -> setParentId(); //установить связи parent_id
	
	if( count($this -> temp_data) ) //если временный массив не пуст, дописать данные в БД
	{
	  $this -> registry -> add( $this -> temp_data );
	  $this -> temp_data = array();
	}
	
	$this -> progressLog();
	
	//step 3 - создание результирующей таблицы
	
	$this -> registry -> dropTable( $this -> db_table_name );
	
	$query = 'CREATE TABLE IF NOT EXISTS '.$this -> db_table_name.'
               (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY)
		      SELECT 
		        T1.id,
		        T1.date_create,
		        T1.name,
		        T1.code,
		        T2.parent_id

		      FROM '.$this -> temp_table_1.' T1
		      JOIN '.$this -> temp_table_2.' T2
		      
			  ON T1.id=T2.position_id
             ';
	
	$this -> registry -> mysql_qw($query);

	$this -> progressLog();

	//step 4 - проверка на ошибки
	
	$query = 'SELECT COUNT(*) FROM '.$this -> temp_table_1;
	$mysql_result = $this -> registry -> mysql_qw( $query );
	if( $mysql_result ) $count_table_1 = $mysql_result -> fetch_row()[0];
	
	$query = 'SELECT COUNT(*) FROM '.$this -> temp_table_2;
	$mysql_result = $this -> registry -> mysql_qw( $query );
	if( $mysql_result ) $count_table_2 = $mysql_result -> fetch_row()[0];
	
	$query = 'SELECT COUNT(*) FROM '.$this -> db_table_name;
	$mysql_result = $this -> registry -> mysql_qw( $query );
	if( $mysql_result ) $count_result_table = $mysql_result -> fetch_row()[0];
	
    if( $count_table_1 != $count_table_2 ) $this -> setError( 'установлены не все связи. Позиций без связи:'.($count_table_1-$count_table_2) );
    
	if( $count_table_1 != $count_result_table ) $this -> setError( 'результирующая таблица меньше чем исходная на '.($count_table_1-$count_result_table) );

	$this -> count_position = $count_result_table;

	$this -> progressLog();

    //step 5 - удалить временные таблицы

	$this -> registry -> dropTable( $this -> temp_table_1 );
	$this -> registry -> dropTable( $this -> temp_table_2 );
	
	$this -> progressLog();
  }


  
  
  
  private function setParentId()
  {
	$foo = new BaseUnit;

	$mysql_result = $foo -> mysql_qw( 'SELECT id, code FROM '.$this -> temp_table_1 );
	
	if($mysql_result)
	{
	  while($row = $mysql_result -> fetch_array( MYSQLI_ASSOC ) )
	  {
		$foo
		  -> set('position_id', $row['id'])
		  -> set('parent_id', $this -> getIdByCode($row['code']) )
		  ;
		  
		$this -> temp_data[] = $foo -> getProperties();
		
		if( count($this -> temp_data) == 100 )
	    {
	      $this -> registry -> add($this -> temp_data);
	      $this -> temp_data = array();
	    }
	  }
	}
  }


  /*принимает код позиции и ищет родительский код
    возвращает id записи родительской позиции
	в случае неудачной попытки, рекурсивно вызывает себя несколько раз
  */
  private function getIdByCode($code, $recurse_count = 0)
  {
    if( mb_strlen($code) >= 2) //код из 2-х и более символов
    {
	  $needle = $this -> cutCode($code); //обрезка кода
	}
    else //это буквенное обозначение класса, самый верхний уровень классификатора ОКПД2
	{
	  return 0;
	}

	$foo = new BaseUnit;

	$mysql_result = $foo -> mysql_qw( 'SELECT id FROM '.$this -> temp_table_1.' WHERE code=? LIMIT 1', $needle );

	if($mysql_result -> num_rows)
	{
		 //$this -> recurse=0;
		 
	  $row = $mysql_result -> fetch_array( MYSQLI_ASSOC );
	  return $row['id'];
	}
	else
	{
	  //if($this -> recurse > 3) 
	  if($recurse_count > 3) 
	  {
		  print_r( "ERROR код позиции: ".$code." needle: ".$needle."\n" );
		  //$this -> recurse=0;
	    return;
	  }
	  //рекурсивно вызвать этот метод
	  //...
	  $this -> getIdByCode($needle, $recurse_count++);
	  //$this -> recurse++;
	}
  }
  

  //ожидает получить код длиной 2 и более символов
  //обрезает код и возвращает код родителя
  private function cutCode($code)
  {
    if( mb_strlen($code) > 2) //обрезка кода
    {
	  //если строка XX.XX.XX.XXX убрать последние четыре знака
	  if(mb_strlen($code) == 12)
	  {
	    $needle = mb_substr($code, 0, mb_strlen($code)-4);
      }
      else
	  { //если строка 29.1, при делении по модулю будет 0 и надо убрать последние 2 символа, если 29.19, при делении по модулю будет остаток и надо убрать последний 1 символ
	    
		//отрабатывает не правильно: $needle = (mb_strlen($code) % 2) ? mb_substr($code, 0, mb_strlen($code)-1) : mb_substr($code, 0, mb_strlen($code)-2);
		
		$arr = explode('.', $code); //разбить на блоки

        $last = $arr[ count($arr)-1 ]; //последний блок

		//если в последнем блоке 2 цифры - убрать одну, если одна - убрать последний блок
        if( mb_strlen($last) > 1 ) $arr[ count($arr)-1 ] = mb_substr($last, 0, mb_strlen($last)-1);
        else
		  $arr = array_slice($arr, 0, count($arr)-1);

        $needle = implode('.', $arr); //собрать код обратно
	  }
    }
	else //код из 2-х цифр - это основные классы справочника, их разнести по буквенным обозначениям
	{
	  switch( $code )
	  {
	    case '01':
	    case '02':
	    case '03': $needle = 'A'; break;
		case '05':
		case '06':
		case '07':
		case '08':
		case '09': $needle = 'B'; break;
		case '10':
		case '11':
		case '12':
		case '13':
		case '14':
		case '15':
		case '16':
		case '17':
		case '18':
		case '19':
		case '20':
		case '21':
		case '22':
		case '23':
		case '24':
		case '25':
		case '26':
		case '27':
		case '28':
		case '29':
		case '30':
		case '31':
		case '32':
		case '33': $needle = 'C'; break;
		case '35': $needle = 'D'; break;
		case '36':
		case '37':
		case '38':
		case '39': $needle = 'E'; break;
		case '41':
		case '42':
		case '43': $needle = 'F'; break;
		case '45':
		case '46':
		case '47': $needle = 'G'; break;
		case '49':
		case '50':
		case '51':
		case '52':
		case '53': $needle = 'H'; break;
		case '55':
		case '56': $needle = 'I'; break;
		case '58':
		case '59':
		case '60':
		case '61':
		case '62':
		case '63': $needle = 'J'; break;
		case '64':
		case '65':
		case '66': $needle = 'K'; break;
		case '68': $needle = 'L'; break;
		case '69':
		case '70':
		case '71':
		case '72':
		case '73':
		case '74':
		case '75': $needle = 'M'; break;
		case '77':
		case '78':
		case '79':
		case '80':
		case '81':
		case '82': $needle = 'N'; break;
		case '84': $needle = 'O'; break;
		case '85': $needle = 'P'; break;
		case '86':
		case '87':
		case '88': $needle = 'Q'; break;
		case '90':
		case '91':
		case '92':
		case '93': $needle = 'R'; break;
		case '94':
		case '95':
		case '96': $needle = 'S'; break;
		case '97':
		case '98': $needle = 'T'; break;
		case '99': $needle = 'U'; break;
	  }
	}

	return $needle;
  }
  
  
  
  
  
  
  /*
  {
	$zip_archive = $this -> downloadFile($archive); //скачивание zip-архива извещений по ftp, возвращает путь до файла
		
	$zip = new ZipArchive();

	if($zip -> open($zip_archive)) //открытие zip-архива
	{
		for ($i = 0; $i < $zip -> numFiles; $i++) //чтение zip-архива
		{
			if( pathinfo($zip -> statIndex($i)['name'], PATHINFO_EXTENSION) == 'sig' ) continue; //не брать файлы с расширением .sig

			if(!$this -> XMLParser($zip -> getFromIndex($i), $archive, $zip -> statIndex($i)['name'])) continue; //прочитанный XML или пустой или не извещение
		}
		$zip -> close();
	}
	unlink($zip_archive); //удаление временного архива
  }
  */
  
  /*Функция получает имя архива на FTP-сервере, затем:
  1) скачивает архив
  2) открывает архив
  3) читает архив
  4) закрывает архив
  5) удаляет архив
  */
  private function readOKPD2Parts($archive)
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
		
		unlink($zip_archive); //удаление временного архива
	}
	
	/*
	$zip_archive = $this -> downloadFile($archive); //скачивание zip-архива извещений по ftp, возвращает путь до файла
		
    $zip = zip_open($zip_archive); //открытие zip-архива

	if($zip)
    {
	  while($zip_entry = zip_read($zip)) //чтение zip-архива
      {
			/**справочно (полезные плюшки)
			//zip_entry_name($zip_entry)              //название xml-файла
			//zip_entry_filesize($zip_entry)          //исходный размер
			//zip_entry_compressedsize($zip_entry)    //сжатый размер
			//zip_entry_compressionmethod($zip_entry) //метод сжатия
			/** /

        if(zip_entry_open($zip, $zip_entry, "r"))
		{
          $xml = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)); //чтение файла

		  $this -> XMLParser( $xml, $archive, zip_entry_name($zip_entry) );

          zip_entry_close($zip_entry);
        }
      }
      zip_close($zip);
	}
    unlink($zip_archive); //удаление временного архива
	*/
  }

  
  /*парсит xml-файл и передаёт данные для записи в БД*/
  private function XMLParser($xml, $archive, $name)
  {
    //if( !mb_strlen($xml) ) return; //проверка на пустой xml-файл. Возможно она не нужна
	
	$dom = new domDocument;

    $dom -> encoding = CHARSET;
    $dom -> preserveWhiteSpace = false;

    $dom -> loadXML($xml);


    $xpath = new DOMXPath( $dom );
	
	$xpath -> registerNamespace('okpd', 'http://zakupki.gov.ru/oos/export/1');
	
	$node_list = $xpath -> query('/*/okpd:nsiOKPD2List/okpd:nsiOKPD2');
	
	
	//записать коды в таблицу БД
	for($i = 0; $i < $node_list -> length; $i++)
    {
	  $code = $node_list[$i] -> getElementsByTagName('code') -> item(0) -> nodeValue;
	  $name = $node_list[$i] -> getElementsByTagName('name') -> item(0) -> nodeValue;

	  if( (bool)$node_list[$i] -> getElementsByTagName('actual') -> item(0) -> nodeValue ) //проверка на актуальность
	  {
	    $this -> addCode($code, $name);
	  }
    }
	
	return true;
  }
  

  private function addCode($code, $name)
  {
	$foo = new BaseUnit;

	//пример кода ОКПД2 23.19.12.119
	$foo
	  -> set('code', $code)
	  -> set('name', $name)
	  ;
	
	$this -> temp_data[] = $foo -> getProperties();
	
	if( count($this -> temp_data) == 100 )
	{
	  $this -> registry -> add($this -> temp_data);
	  $this -> temp_data = array();
	}
  }


  /*возвращает список имен архивов с данными ОКПД2 по вчерашней выгрузке
  */
  private function getOKPD2List()
  {
    if($this -> federal_law == '44')
    {
      $okpd2_list = $this -> readDirectory( "/fcs_nsi/nsiOKPD2" ); //получить имена архивов, составляющих справочник

	  $okpd2_list = array_filter((array)$okpd2_list, function($v) //оставить только вчерашнее обновление
        {
		  $str = date('Ymd', time()-(60*60*24))."000000_".date('Ymd', time())."000000"; //формат даты в имени архива 20200616000000_20200617000000 (нас интересует вчера-сегодня)
          return mb_strpos($v, $str) !== false; //можно и так: return mb_strpos($v, $str), но это не совсем правильно, т.к. в случае если подстрока будет на позиции "0" обработает на правильно
        }
      );

	  return array_values($okpd2_list); //необходимо переиндексировать массив функцией array_values
	}
	
	if($this -> federal_law == '223') //заглушка, справочник читает только из директории 44-ФЗ
	{
	  return array();
	}
  }
}
?>