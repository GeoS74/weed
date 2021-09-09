<?
class ReportTender extends BaseUnit
{
  protected $selection_condition = array(); //условие выбора
  protected $selection_data  = array();     //данные выбора
  
  protected $order_by = 'id';
  
  protected $date_start;
  protected $date_end;
  
  protected $current_data = array(
    'data_table'=>array(),
	'sum' => 0,
	'count' => 0,
  );

  function __construct($date_start=null, $date_end=null)
  {
    $this -> date_start = $date_start ? $this -> getMilliSeconds($date_start) : 1;
	$this -> date_end = $this -> getMilliSeconds($date_end) + 86300000; //плюс сутки
  }

  public function setOrderBy( $order_by )
  {
    $this -> order_by = (string)$order_by;
    return $this;
  }
  
  public function load($start = null, $limit = null)
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Файл: '.__FILE__.' Класс: Report, Метод: load)');

	if( count($this -> selection_condition) ) $where = ' WHERE date_create >= ?+0 AND date_create < ?+0 AND '. implode( ' AND ', $this -> selection_condition );
	
    $query = 'SELECT COUNT(*) as count, SUM(sum) as sum FROM '.$this -> database_table_name.$where;

	$mysql_result = $this -> mysql_qw($query, array_merge(array($this -> date_start, $this -> date_end), $this -> selection_data));
	
	if( $mysql_result ) $this -> current_data = $mysql_result -> fetch_assoc();

    return $this;
  }
  
  public function getExplanation()
  {
	//запрос в основную таблицу
    //$query = 'SELECT explanation, COUNT(DISTINCT id) as count, SUM(sum) as sum FROM tender_bovid WHERE (direction="зап. части" OR direction IS NULL) AND status="отклонено" AND (result="Выбрать" OR result="") GROUP BY explanation';
    //запрос в предварительную таблицу
    //$query = 'SELECT explanation, COUNT(DISTINCT id) as count, SUM(sum) as sum FROM tender_bovid_upload WHERE (direction="зап. части" OR direction IS NULL) AND status="отклонено" GROUP BY explanation';
	
	if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Файл: '.__FILE__.' Класс: Report, Метод: getExplanation)');

	if( count($this -> selection_condition) ) $where = ' WHERE date_create >= ?+0 AND date_create < ?+0 AND '. implode( ' AND ', $this -> selection_condition );
	
    $query = 'SELECT explanation, COUNT(DISTINCT id) as count, SUM(sum) as sum FROM '.$this -> database_table_name.$where.' GROUP BY explanation';
	
	$mysql_result = $this -> mysql_qw($query, array_merge(array($this -> date_start, $this -> date_end), $this -> selection_data));
	
	$result = array();

	if($mysql_result)
	{
	  while( $row = $mysql_result -> fetch_assoc() )
	  {
	    $result[ $row['explanation'] ] = array('sum' => $row['sum'], 'count' => $row['count']);
	  }
	}
    return $result;
  }

  public function loadTable() //загрузить данные таблицы
  {
	if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Класс: ReportTender, Метод: loadTable)');

	if( count($this -> selection_condition) ) $where = ' WHERE date_create >= ?+0 AND date_create < ?+0 AND '. implode( ' AND ', $this -> selection_condition );

    $query = 'SELECT * FROM '.$this -> database_table_name.$where. ' ORDER BY ' . $this -> order_by;

	$mysql_result = $this -> mysql_qw($query, array_merge(array($this -> date_start, $this -> date_end), $this -> selection_data));

	if( !$mysql_result -> num_rows ) return $this;

	while( $row = $mysql_result -> fetch_assoc() )
	{
	  $this -> current_data['data_table'][] = $row;
	}

    return $this;
  }

  public function getTable()
  {
	return $this -> current_data['data_table'];
  }

  public function getCount()
  {
    return (int)$this -> current_data['count'];
  }

  public function getSum()
  {
    return (int)$this -> current_data['sum'];
  }

  public function getPeriod()
  {
    return array('date_start' => $this -> date_start, 'date_end' => $this -> date_end);
  }

  public function addSelection($where) //установить условие отбора, возвращает $this
  {
    $this -> selection_condition[] = (string)$where;

	$args = func_get_args();

	//вторым аргументом, если это массив, или просто передав аргументы, можно установить данные для отбора
	if($args[1])
	{
	  if( is_array($args[1]) ) $this -> selection_data = array_merge($this -> selection_data, $args[1]);
	  else $this -> selection_data = array_merge($this -> selection_data, array_slice( $args, 1 ));
	}

	return $this;
  }

  public function reset() //сбросить текущее условие отбора, возвращает $this
  {
    $this -> selection_condition = array();
    $this -> selection_data = array();
    $this -> current_data = array('data_table'=>array(), 'sum' => 0, 'count' => 0,);
	return $this;
  }


  //protected functions
  //
  protected function getMilliSeconds($date) //возвращает миллисекунды или метку текущего времени
  {
    $arr_date = explode('-', $date);

    if( !$arr_date[0] ) return time()*1000 + 18000000; //при вводе неправильной даты возвращать метку текущего времени

    $year =  (int)$arr_date[0];
    $month = (int)$arr_date[1];
    $day =   (int)$arr_date[2];
  
    if(!$month) $month++;
    if(!$day) $day++;

    return mktime(0, 0, 0, $month, $day, $year)*1000;
  }
}
?>