<?
class Reg extends Registry
{
	public function add()
	{
		$user = func_get_args()[0];

		if($user -> register())
			$this -> setCurrentData( $user -> set('pass', null) -> getProperties() );
		else
			$this -> setCurrentData( array('errors' => $user -> get('errors')) );
	}

	public function upd()
	{
		$user = func_get_args()[0];

		if($user -> correction())
			$this -> setCurrentData( $user -> set('pass', null) -> getProperties() );
		else
			$this -> setCurrentData( array('errors' => $user -> get('errors')) );
	}

	public function changePass()
	{
		$user = func_get_args()[0];

		if($user -> changePass())
			$this -> setCurrentData(null);
		else
			$this -> setCurrentData( array('errors' => $user -> get('errors')) );
	}


	public function search($data=array(), $start=null, $limit=null)
	{
		if( !$this -> database_table_name ) die(printf("Ошибка: не задано имя таблицы БД (файл: %s, класс: %s, метод: %s", __FILE__, __CLASS__, __METHOD__));

		$q = $this -> preProcessing( $data, $start, $limit);

		$mysql_result = $this -> mysql_qw( $q['query'], $q['data'] );

		$this -> setCurrentData(); //обнуление текущих данных регистра

		if( !$mysql_result ) return $this;

		while( $row = $mysql_result -> fetch_array( MYSQLI_ASSOC ) )
		{
		  $this -> setCurrentData($row, 'NOT_REWRITE');
		}
		return $this;
	}

	protected function preProcessing($data = array(), $start = null, $limit = null)
	{
		$where_search = array();
		$data_search = array();

		//текстовый поиск
		$sample = (string)$data['text_search'];
		if($sample)
		{
			$sample = '%'.trim($sample).'%';

			$checkbox= false; //флаг включения любого чекбокса

				//лямбда-функция проверки состояния чекбокса
				$checkCheckbox = function($ch_name, $column) use (&$where_search, &$data_search, $sample, &$checkbox)
				{
					if(!$ch_name) return;
					$where_search[] = $column . ' LIKE ?';
					array_push($data_search, $sample);
				};

			$checkCheckbox($data['search_for_name'], 'name');
			$checkCheckbox($data['search_for_email'], 'email');

			if(!$checkbox)
			{
				$where_search[] = '(name LIKE ? OR email LIKE ?)';
				array_push($data_search, $sample, $sample);
			}
		}

		//выпадающий список
		$company = $data['search_for_company'];
		if($company)
		{
			$where_search[] = '(company=?)';
			array_push($data_search, $company);
		}

		if( count($where_search) ) $where = ' WHERE '. implode( ' AND ', $where_search );

		$query = 'SELECT * FROM '. $this -> database_table_name .' T '. $where . ' ORDER BY ' . $this -> order_by;

		if( (int)$limit ) //лимитирование и смещение
		{
			$query .= ' LIMIT '; 

			if( !is_null($start) ) $query .= (int)$start .', ';

			$query .= (int)$limit;
		}

		return array( 'query' => $query, 'data' => $data_search );
	}
}

//echo "~ backend connect ~\n";
//$timer_start = microtime(true);
//echo round((microtime(true) - $timer_start), 2);

trimer($_p);


/**
print_r($_p);
$_RESULT = array(
	'main_data' => array(),
	'meta_data' => array(
			'event' => $_p['tmpl_data']['event'],
			'start' => $_p['tmpl_data']['start'],
		),
	);
exit;
/**/
//print_r("query:\n");
//print_r($_p);



$user = new User($_p['form_edit']);
$user -> setDatabaseTableName(DB_TABLE_NAME);

$reg = new Reg;
$reg -> setDatabaseTableName(DB_TABLE_NAME);

switch($_p['tmpl_data']['event'])
{
  case 'add':
	//sleep(3);
    $reg -> add($user);
	break;

	case 'change_pass':
		$reg -> changePass($user);
		break;

  case 'edit':
    $reg -> upd($user);
	break;

  case 'del':
    $reg -> del($user -> getProperties());
	break;

  case 'load':
    $reg
	  -> setOrderBy('id')
	  -> search($_p['form_search'], $_p['tmpl_data']['start'], $_p['tmpl_data']['limit']);
	break;
}

$_RESULT = array(
	'main_data' => $reg -> out(),
	'meta_data' => array(
			'event' => $_p['tmpl_data']['event'],
			'start' => $_p['tmpl_data']['start'],
		),
	);
?>