<?
/*перехват обращений от 1С и создание новых записей
*/
/*структура данных от 1С
{
	"UID": "b700ffe9-79df-431d-9ea6-5f6fde1ab3f8",
	"inn_customer": "7810474820",
	"number": "ТД023045",
	"sum": "25 256,38",
	"customer": "ГСП-ГСМ ООО (быв.СГК-ГСМ)",
	"master": "Суханов Михаил Сергеевич",
	"car": "Урал 4320  Р 413 СВ 197",
	"date_document": "06.10.2019 5:50:20",
	"sale": [										//реализации
				{
				"sale_number": "ТД009341",
				"sale_date": "07.10.2019 13:47:26",
				"invoice": [						//счёт-фактуры
								{
								"invoice_number": "55454545",
								"invoice_date": "27.11.2019 16:35:47"
								},
								{
								"invoice_number": "ТД009326",
								"invoice_date": "07.10.2019 13:47:26"
								}
							]
				}
			],
	"score": [										//счета
				{
				"score_number": "ТД010098",
				"score_date": "27.11.2019 17:35:48"
				},
				{
				"score_number": "ТД008484",
				"score_date": "07.10.2019 14:16:14"
				},
				{
				"score_number": "ТД010097",
				"score_date": "27.11.2019 17:03:17"
				}
	]
}


*/

$reports = 'ip:'.$_SERVER['REMOTE_ADDR'].' '.$_SERVER['HTTP_USER_AGENT'].' '.$_SERVER['REQUEST_URI'];
Log::setFileName( 'debug_'.date('Y-m-d') );
Log::write('/log/debug', $reports );
//Log::write('/log/debug', json_encode($_POST) );
//Log::write('/log/debug', $_POST['json_data'] );

//if( !$_POST['json_data'] ) exit();

define('DB_TABLE_NAME', 'gsprom_document');


$valid_inn = array(
  '7810474820' => 'ГСП-Сервис ООО',
  '7810612780' => 'ГСП-ГСМ ООО',
  '7810442793' => 'ГСП-Механизация ООО',
  '7810443268' => 'ГСП-2 ООО',
);


//получение структуры
$structure = json_decode( $_POST['json_data'], true );

//trimer($structure);

//if($structure['inn_customer']) //убрать это условие после того, как Вова обновит конфигурацию
//{
	//проверка на валидность контрагента по инн
	if(!$valid_inn[ $structure['inn_customer'] ]) exit;

	//корректировка названия контрагента
	$structure['customer'] = $valid_inn[ $structure['inn_customer'] ];
//}


//почтовые ящики
$bovid_mail = array(
  'edenisov@bovid.ru',
  'gsirotkin@bovid.ru',
  'aldanbovid@mail.ru',
  'msuhanov@bovid.ru',
  'agrachev@bovid.ru',
  'emanyaeva@bovid.ru',
);

$customer_mail = array();

if($structure['inn_customer'] == '7810474820') //ГСП-Сервис ООО
{
  $customer_mail = array(
    'KukhtenkovAA@gsp-service.com',
    'TsaoNV@gsp-service.com',
    'vozhzhovaa@gsp-service.com',
  );
}
else if($structure['inn_customer'] == '7810612780') //ГСП-ГСМ ООО
{
  $customer_mail = array(
	'v.s.shilov@mail.ru',
    'sechkinii@gsp-gsm.ru',
    'ovchinnikovma@gsp-gsm.ru',
    'drugovas@gsp-gsm.ru',
    'laptevVA@gsp-gsm.ru',
    'fedorovana@gsp-gsm.ru',
    'pankovaa@gsp-gsm.ru',
  );
}


//обработка даты от 1С
$parse_date = date_parse_from_format("d.m.Y H:i", $structure['date_document']); //парсинг даты в массив
$structure['date_document'] = mktime( $parse_date['hour'], $parse_date['minute'], 0, $parse_date['month'], $parse_date['day'], $parse_date['year']);
$structure['date_document'] *= 1000; //перевод даты в миллисекунды



class DocumentGSP extends BaseUnit
{
  //метод проверяет наличие наименования заказчика в таблице gsprom_customer, если этого заказчика там нет - добавляет его
  public function customerControl()
  {
	$args = func_get_args();
	$data = $args[0];

	if( !$data['customer'] ) return $this; //пустые на писать
	
	$unit = new BaseUnit;
	
	$db_table_name = 'users_company';
	
	$query = 'SELECT * FROM '.$db_table_name.' WHERE section=?';

	$mysql_result = $unit -> mysql_qw($query, $data['customer']);

	if(!$mysql_result -> num_rows)
	{
	  $unit
	    -> setEditMode('USE_ALL_DATA_FIELDS')
	    -> setDatabaseTableName($db_table_name)
		-> set('title', $data['customer'])
		-> set('parent_id', 0)
		-> set('user', '1C')
		-> add()
		;
	}
	return $this;
  }
}


//обработка данных по приемо-сдаточному акту
$unit = new DocumentGSP;

$unit
  -> setDatabaseTableName( DB_TABLE_NAME )
  //-> setEditMode('USE_ALL_DATA_FIELDS')
  ;

$unit
  -> set('UID', $structure['UID'])
  -> set('number', $structure['number'])
  -> set('sum', getDigit($structure['sum']))

  -> set('customer', $structure['customer'])
  -> customerControl($structure)

  -> set('master', $structure['master'])
  -> set('car', $structure['car'])
  -> set('date_document', $structure['date_document'])
  -> set('status', 'поступила заявка') //статус по умолчанию
  -> set('sale', serialize($structure['sale'])) //сериализованны данные по реализациям и счетам-фактурам
  -> set('score', serialize($structure['score'])) //сериализованны данные по счетам
  ;


//*******************************
//ИСПОЛЬЗОВАТЬ ТОЛЬКО ОДИН РАЗ!!!
//*******************************
		//создание таблицы и изменение типа для двух столбцов
		//$unit -> upd();
		//$query = 'ALTER TABLE '.DB_TABLE_NAME.' MODIFY sale TEXT NULL, MODIFY score TEXT NULL';
		//$unit -> mysql_qw( $query );
//*******************************
//ИСПОЛЬЗОВАТЬ ТОЛЬКО ОДИН РАЗ!!!
//*******************************


//проверка на повторную отправку данных
$query = 'SELECT * FROM '.DB_TABLE_NAME.' WHERE UID=? ORDER BY id DESC LIMIT 1';
$mysql_result = $unit -> mysql_qw( $query, $structure['UID'] );

if( $mysql_result -> num_rows ) //запись есть -> перезаписать данные
{
  $row = $mysql_result -> fetch_array( MYSQLI_ASSOC );

  //если введена реализация и ПСА в статусе 'поступила заявка' изменить статус
  if( count($structure['sale']) and $row['status'] == 'поступила заявка') 
  {
	$unit -> set('status', 'ремонт окончен');


	//запись о смене статуса
    $foo = new BaseUnit;
    $foo
	  -> setDataBasetableName( DB_TABLE_NAME.'_change_status' )
	  -> set( 'status', $unit -> get('status') )

	  //-> set( 'parent_id', $unit -> get('id') ) //BUG DETECTED!!! не фиксируется изменение статуса при наличии реализации. Решено.
	  -> set( 'parent_id', $row['id'] )

	  -> set( 'comment', '' )
	  -> set( 'user', '1C' )
	  -> set( 'company', 'БОВИД' )
	  -> add()
	  ;
  }
  else
  {
	$unit -> set('status', $row['status']); //статус не меняется
  }
  
  $unit
    -> set('id', $row['id'])
    -> upd()
	;
}
else //записи нет -> записать новые данные
{
  $unit -> add();
  
  //уведомить по e-mail
  $mail = new Mail;
  $mail 
        -> setTo( $bovid_mail ) //установка адресатов по умолчанию
        -> setTo( $customer_mail ) //установка адресатов заказчика
        -> setFrom("noreply@bovid.ru")
		-> setSubject("Поступила заявка: ".$structure['car'])
        -> setMessage("Заказчик: ".$structure['customer']."\r\n\r\n")
        -> setMessage("Автомобиль: ".$structure['car']."\r\n\r\n")
        -> setMessage("Приёмо-сдаточный акт: № ".$structure['number']." от ".date('Y-m-d', $structure['date_document']/1000)."\r\n\r\n")
        -> setMessage("Мастер: ".$structure['master']."\r\n\r\n")
        -> setMessage("Это письмо сформировано автоматически. Отвечать на него не нужно.")
        -> send()
		;

  $foo = new BaseUnit;
  $foo
	  -> setDataBasetableName( DB_TABLE_NAME.'_change_status' )
	  -> set( 'status', 'поступила заявка' )
	  -> set( 'parent_id', $unit -> get('id') )
	  -> set( 'comment', '' )
	  -> set( 'user', '1C' )
	  -> set( 'company', 'БОВИД' )
	  -> add()
	  ;
}
?>