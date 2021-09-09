<?
class Registry extends DatabaseInteraction
{
  protected $order_by = 'id';

  protected $model;                  //модель данных

  protected $current_data = array(); //текущие данные объекта

  function __construct( $model = null, $database_table_name = null )
  {
    if( $model ) $this -> setModel( $model);
    if( $database_table_name ) $this -> setDatabaseTableName( $database_table_name);
  }

  //setters
  public function setModel( $model )
  {
    $this -> model = null; //сбросить модель данных

	if( is_array($model) )
	{
	  /*рекурсивная лямбда-функция перебора массива любой сложности (2 варианта)

			первый вариант возвращает массив из всех ключей конечных массивов
			второй вариант работает с внешней переменной, записывая ключи конечных массивов
			тест скорости показал следующий результат на 10000 итерациях
			Скорость варианта 1: 0.73 сек
			Скорость варианта 2: 0.68 сек
	  
			для корректного использования, надо в use указать ссылку &$eachArr (а не значение) на переменную, в которую записана анонимная функция
	  
	  Пример использования:
		  входной массив
				Array
				(
					[bar] => Array
						(
							[name] => GeoS
							[engine] => MAGNUS
							[num] => [ 1, 2, 3], //это будет проигнорировано
						)

					[baz] => Array
						(
							[sub_massive] => Array
								(
									[name] => G. Sirotkin
									[engine] => coders
									[version] => 3.0
									'num' => 777
								)

							[0] => Array
								(
									[e-mail] => gs@mail.ru
									[engine] => MAGNUS
								)

						)

				)
		после обработки функцией $eachArr
				Array
				(
					[name] => 1
					[engine] => 1
					[version] => 1
					[e-mail] => 1
				)
	  */
	  /**Вариант 1
	  $eachArr = function($arr, $k_l = array()) use (&$eachArr)
	  {
	    foreach($arr as $k => $v)
		{
		  if( is_array($v) ) $k_l = array_merge($k_l, $eachArr($v, $k_l));
		  else
		  {
		    if( is_numeric($k ) ) continue; //выбросить числовые ключи, числа не могут быть именами таблиц БД
			if( $k == 'id' ) continue;      //выбросить id, этот ключ должен быть первым в модели
		    $k_l[$k] = true;
		  }
		}
		return $k_l;
	  };
	  $key_list = $eachArr($model);
	  /**/


	  /** Вариант 2*/
	  $key_list = array();

	  $eachArr = function($arr) use (&$eachArr, &$key_list)
	  {
		foreach($arr as $k => $v)
		{
		  if( is_array($v) ) $eachArr($v);
		  else
		  {
		    if( is_numeric($k ) ) continue; //выбросить числовые ключи, числа не могут быть именами таблиц БД

		    $key_list[$k] = true;
		  }
		}
	  };
	  $eachArr($model);
	  /**/

	  $this -> model = $key_list;
	}
	else if( is_object($model) )
	{  
	  if( method_exists($model, 'getProperties') )
	  {
	    $this -> model = array_keys($model -> getProperties());
		$this -> model = array_flip( $this -> model );
	  }
	  else
	  {
	    $this -> model = null;
	  }
	}
	else if( is_string($model) )
	{ 
	  $model = new $model;
	  if( method_exists($model, 'getProperties') )
	  {
	    $this -> model = array_keys($model -> getProperties());
		$this -> model = array_flip( $this -> model );
	  }
	  else
	  {
	    $this -> model = null;
	  }
	}


	
	if( count($this -> model) ) //корректировка модели данных
	{
	  $this -> model = array_filter( //удалить ключ id, если есть
	    $this -> model,
		function($k){return $k !== 'id';},
		ARRAY_FILTER_USE_KEY
	  );
	  
	  $this -> model = array_merge( array('id'=>null), $this -> model ); //установить ключ id первым в модели

	  if( $this -> database_table_name ) //попытка создания таблицы БД
	  {
	    $this -> createTable( $this -> database_table_name, array_keys($this -> model) );
		$this -> getFieldNames(); //получить имена столбцов таблицы
	  }
	}

    return $this;
  }

  public function setDatabaseTableName( $database_table_name )
  {
    parent::setDatabaseTableName($database_table_name);

	/*WARNING - попытка создания таблицы БД при вызове этого метода может приводить к неожиданным результатам!!!
	например, если используется один и тот же объект для работы с разными данными,
	то при смене имени таблицы и в случае если таблицы нет, а модель уже установлена - будет создана новая таблица на основе старой модели данных
	if( count($this -> model) and $this -> database_table_name ) //попытка создания таблицы в БД
	{
	  //модель данных этого класса должна иметь свойство id
	  if(!$this -> model['id']) $this -> model = array_merge( array('id'=>null), $this -> model );

	  $this -> createTable( $this -> database_table_name, array_keys($this -> model) );
	  $this -> getFieldNames();
	}
	*/
	return $this;
  }





  public function setOrderBy( $order_by )
  {
    $this -> order_by = (string)$order_by;
    return $this;
  }

  protected function setCurrentData( $data = null, $rewrite = null )
  {
    if( $rewrite != 'NOT_REWRITE' )
	{
      $this -> current_data = array();
	}

	if( $data )
	{
	  $this -> current_data[] = $data;
	}
	return $this;
  }




  
  
  /*записывает данные в БД
  *add( array $data )
  *@param array $data - массив данных для записи (любой вложенности)
  *@return $this - текущий объект
  *
  *в качестве входных данных предпочтительно использовать ассоциативные массивы,
  *т.к. при формировании модели данных значения с числовыми ключами будут проигнорированы (см. комментарии в реализации setModel() )
  *при формировании данных для записи - значения с числовыми ключами будут также проигнорированы
  *
  *Алгоритм действий
  *	1) Проверить существование модели данных, если её нет - создать модель
  *	2) Убрать из модели данных ключ id
  *	3) Сравнить модель с полями таблицы БД. Получить массив с полями таблицы БД в которые будет произведена запись
  *	4) Получить массив данных для записи в БД (кол-во элементов в этом массиве кратно кол-ву полей для записи)
  *	5) Создать строку запроса
  *	6) Записать данные в БД
  *	7) Во входную структуру добавить поле id и записать туда последний идентификатор новой записи.
  *		Причём если была запись многомерного массива, то в это поле будет записан id первой добавленной строки.
  *	8) Записать полученные данные в текущие данные объекта
  */
  public function add()
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Класс: Registry, Метод: add)');

	$data = func_get_args()[0]; //входные данные для записи

    if( !count($this -> model) ) //установить модель данных и создать таблицу БД
	{
	  $this -> setModel($data);
	}

	if( !count($this -> model) ) die('Ошибка: модель данных не установлена (Файл: '.__FILE__.' Класс: '.get_class().', Метод: add)');



	//выкинуть поле id из модели, т.к. при записи новых данных это поле записывается автоматически
    $model_not_id = array_filter( (array)$this -> model,
	  function($key) {
	    return $key != 'id';
	  }, ARRAY_FILTER_USE_KEY );


    $target_fields = $this -> getTargetFields( $model_not_id ); //получить поля для записи

    if( !count($target_fields) ) return $this; //записывать нечего


    //формирование массива данных для записи в БД
	//step 1 - записать в массив temp значения подходящие для записи
	//	если в качестве значения - массив, рекурсивно вызывает сама себя
	//step 2 - сравнить по ключу массивы с именами столбцов таблицы БД и массива temp
	//	полученный массив добавить в результирующий набор данных для записи
	//
    $query_data = array();

    $eachArr = function($arr) use (&$eachArr, &$query_data, $target_fields)
    {
		//step 1 сформировать временный массив. В него НЕ пишутся массивы и значения с числовыми ключами
		$temp = array();
		foreach($arr as $k => $v)
		{
		  if( is_array($v) ) $eachArr($v);
		  else
		  {
		    if( is_numeric($k ) ) continue; //выбросить числовые ключи, числа не могут быть именами таблиц БД

			$temp[$k] = $v;
		  }
		}

        //step 2
		if( count($temp) )
		{
		  foreach($target_fields as $k => $v)
		  {
			$temp_2[] = $temp[$k];
		  }
		  $query_data = array_merge($query_data, $temp_2);
		}
		  
		/* //BUG DETECTED
		такая реализация //step 2 работает хорошо только если поля таблиц БД имеют тип VARCHAR
		в случае если начать использовать поле TINYINT(1) для записи булева значения проверка в строке (1)
		отработает не корректно. Т.к. если передать 0, в массив значений будет записано значение null.
		И при попытке записи null в колонку типа TINYINT (или INT и т.д.) ничего не произойдёт.
		Поэтому //step 2 записывает значения как они есть.
		if( count($temp) )
		{
		  $temp_2 = array();
		  $flag = false; //флаг, принимает значение true в случае если в массиве есть данные, подходящие для записи в таблицу

		  foreach($target_fields as $k => $v)
		  {
		    if( $temp[$k] ) //BUG DETECTED (1)
			{
			  $temp_2[] = $temp[$k];
			  $flag = true;
			}
			else $temp_2[] = null;
		  }
		  //можно проверить массив так:
		  //$test = array_reduce($arr, function($carry, $item){
          //   return $carry.$item;
          //   });
		  //в результате если переменная $test будет не пустая - есть что записать
		  if($flag) $query_data = array_merge($query_data, $temp_2);
		}
		*/
    };
    $eachArr($data);


	//плейсхолдеры
	$plch = array_fill(0, count($target_fields), '?');

	$plch = '('.implode(', ', $plch).')'; //создаёт строку с плейсхолдерами типа: (?, ?, ?, ?, ?)
	
    //т.к. кол-во элементов в массиве $query_data кратно кол-ву элементов в массиве $target_fields
	//для подсчёта кол-ва блоков используется деление
	$plch = array_fill( 0, ( count($query_data)/count($target_fields) ) , $plch ); //создаёт массив из строк с плейсхолдерами: array( (?, ?, ?, ?, ?), (?, ?, ?, ?, ?), ... (?, ?, ?, ?, ?) )


	$query = 'INSERT INTO '.$this -> database_table_name.
					' ('.implode(', ', array_keys($target_fields)).')'.
					' VALUES '.implode(', ', $plch);


	$this -> mysql_qw( $query, $query_data );

	$data['id'] = $this -> mysql_last_id();

	$this -> setCurrentData($data);

    return $this;
  }

  
  
  
  
  

  public function out()
  {
    $args = func_get_args();

	if( count($args[0]) )
	{
      return htmlspecialcharser( $args[0] );
	}
	else
	{
	  return htmlspecialcharser( $this -> current_data );
	}
  }

  public function load( $start = null, $limit = null )
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Класс: Registry, Метод: load)');

	//выбор полей таблицы
	if( !count($this -> model) )
	{
	  $fields = '*';
	}
	else
	{
	  $fields = $this -> getTargetFields( $this -> model );
	  $fields = implode(', ', array_keys($fields));
	}

	$query = 'SELECT '.$fields.' FROM '. $this -> database_table_name . ' ORDER BY ' . $this -> order_by;

	if( !is_null($start) ) $query .= ' LIMIT '. (int)$start; 

	if( !is_null($limit) ) $query .= ', '. (int)$limit;

	$mysql_result = $this -> mysql_qw( $query );


	$this -> setCurrentData(); //обнуление текущих данных регистра
	
	if( !$mysql_result ) return $this; //если таблицы не существует, или она пустая завершить выполнение
	
	while( $row = $mysql_result -> fetch_array( MYSQLI_ASSOC ) )
	{
	  $this -> setCurrentData($row, 'NOT_REWRITE');
	}

	return $this;
  }

 
  public function upd()
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Файл: '.__FILE__.' Класс: Registry, Метод: upd)');
	
	$args = func_get_args();
	$data = $args[0];

    if( !count($this -> model) and count($data) )
	{
	  $this -> setModel($data);
	}

	if( !count($this -> model) ) die('Ошибка: не установлена модель данных (Файл: '.__FILE__.' Класс: Registry, Метод: upd)');

	//данные для записи в БД
    $data = array_filter( (array)$data,
	  function($val, $key)
	  {
	    if( $this -> update_mode == 'ONLY_EXISTING' ) //если флаг установлен - перезаписать только поля имеющие значения
		{
		  return (!!$val and $key != 'id' and $key != 'date_create');
		}
	    return ($key != 'id' and $key != 'date_create');
	  }, ARRAY_FILTER_USE_BOTH );

    $data = $this -> getTargetFields( $data );

	$id = is_array( $args[0]['id'] ) ? $args[0]['id'] : array( $args[0]['id'] );
	
	//плейсхолдеры
	$plch = preg_replace_callback( '/.+/i', function($e) {return $e[0] . '=?';}, array_keys($data) );
	$plch_id = array_fill( 0, count($id), '?' );
	
	$query = 'UPDATE ' . $this -> database_table_name .
	         ' SET ' . implode(', ', $plch) .
			 ' WHERE id IN ('. implode(', ', $plch_id) . ')';
			 
	$this -> mysql_qw( $query, array_merge($data, $id) );

	$this -> setCurrentData($args[0]);

	return $this;
  }

  /**
  *	@param $data - данные
  *	@param $column_name - не обязательный параметр, имя столбца (по умолчанию 'id')
  */
  public function del()
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Класс: BaseUnit, Метод: del)');

	$args = func_get_args();
	$data = $args[0];
	$column_name = is_string($args[1]) ?  $args[1] : 'id';

	$id = is_array( $data['id'] ) ? $data['id'] : array( $data['id'] );
	$plch_id = array_fill( 0, count($id), '?' );

	$query = 'DELETE FROM '. $this -> database_table_name . ' WHERE '.$column_name.' IN ('. implode( ', ', $plch_id ) . ')';

	$this -> mysql_qw( $query, $id );

	$this -> setCurrentData($data);

	return $this;
  }
}

?>