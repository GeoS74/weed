<?
/*Класс BaseUnit
*
*Описание:
*	описывает бызовый объект данных
*
*Интерфейс:
*
*	setDatabaseTableName( string $database_table_name ) - устанавливает имя таблицы БД
*	setEditMode( string $edit_mode ) -     [наследуемый метод] при установке значения USE_ALL_DATA_FIELDS
*										   если количество свойств объекта больше чем в таблице БД
*									       недостающие поля будут созданы в таблице БД
*	setUpdateMode( string $update_mode ) - при установке значения ONLY_EXISTING
*										   перезаписывает только те поля, которые имеют значения
*	setId( mixed $id )
*	setDateCreate( string $ms )
*
*	getProperties()
*
*	add()
*	upd()
*	del()
*	
*/
class BaseUnit extends DatabaseInteraction
{
  protected $properties = array();

  function __construct()
  {
    $this 
		-> set('id')
		-> set('date_create');
  }

  //setters
  public function set()
  {
    $args = func_get_args();

	if( !$args[0] ) return $this;
	
    if( is_array($args[0]) )
	{
	  foreach($args[0] as $key => $val)
	  {
	    call_user_func(array($this, 'propProcessing'), $key, $val);
	  }
	}
	else
	{
	  call_user_func(array($this, 'propProcessing'), $args[0], $args[1]);
	}
    return $this;
  }

  //getters
  public function get( $arg ) { return $this -> properties[$arg]; }

  public function getProperties() { return $this -> properties; }


  //protected
  protected function propProcessing( $key, $val = '' )
  {
	//BUG REPORT!!!
	//необходимо проверять $val, если будет передаваться объект (например объект даты) будет вылет с фатальной ошибкой
	if( gettype($val) == 'object' ) $val = '';
		
    switch( $key )
	{
	  case 'id':
	    $val = is_array( $val ) ? $val : (string)$val;
	    break;
	  
	  case 'date_create':
	    $val = !!$val ? (string)$val : time() * 1000;
	    break;
		
      default: 
	    //$val = (string)$val; //при такой реализации невозможно записать массив
	    $val = is_array( $val ) ? $val : (string)$val;
	}
	
	$this -> properties[$key] = $val;
  }

  protected function createDatabaseTable()
  {
    if( !$this -> database_table_name ) die('Ошибка: не задано имя таблицы БД (Класс: BaseUnit, Метод: createDatabaseTable)');

    $this -> createTable($this -> database_table_name, array_keys($this -> properties));
	$this -> getFieldNames();
	return $this;
  }



  //implementation of methods DatabaseInteraction
  public function add()
  {
	$this -> createDatabaseTable();

    //данные для записи в БД
    $data = array_filter( $this -> properties,
	  function($key) {
	    return $key != 'id';
	  }, ARRAY_FILTER_USE_KEY );

    $data = $this -> getTargetFields( $data );

    //плейсхолдеры
	$plch = preg_replace_callback( '/.+/i', function($e) {return $e[0] . '=?';}, array_keys($data) );

    $query = 'INSERT INTO ' . $this -> database_table_name . ' SET ' . implode(', ', $plch);

	$this -> mysql_qw( $query, $data );

	$this -> set( 'id', $this -> mysql_last_id() );

    return $this;
  }

  public function upd()
  {
    $this -> createDatabaseTable();

    //данные для записи в БД
    $data = array_filter( $this -> properties,
	  function($val, $key) {

	    if( $this -> update_mode == 'ONLY_EXISTING' ) //если флаг установлен - перезаписать только поля имеющие значения
		{
		  return (!!$val and $key != 'id' and $key != 'date_create');
		}
	    return ($key != 'id' and $key != 'date_create');
	  }, ARRAY_FILTER_USE_BOTH );

    $data = $this -> getTargetFields( $data );

	$id = is_array( $this -> get('id') ) ? $this -> get('id') : array( $this -> get('id') );

	//плейсхолдеры
	$plch = preg_replace_callback( '/.+/i', function($e) {return $e[0] . '=?';}, array_keys($data) );
	$plch_id = array_fill( 0, count($id), '?' );
	
	$query = 'UPDATE ' . $this -> database_table_name .
	         ' SET ' . implode(', ', $plch) .
			 ' WHERE id IN ('. implode(', ', $plch_id) . ')';

	$this -> mysql_qw( $query, array_merge( $data, $id ) );

    return $this;
  }

  public function del()
  {
    //-del this code- 	$this -> createDatabaseTable(); 	-del this code-
	
	if( !$this -> database_table_name ) die('Файл:'.__FILE__.' Ошибка: не задано имя таблицы БД (Класс: BaseUnit, Метод: del)');
	
    $id = is_array( $this -> get('id') ) ? $this -> get('id') : array( $this -> get('id') );
	$plch_id = array_fill( 0, count($id), '?' );
	
	$query = 'DELETE FROM '. $this -> database_table_name . ' WHERE id IN ('. implode( ', ', $plch_id ) . ')';
	
	$this -> mysql_qw( $query, $id );
	
	return $this;
  }
  
  protected function load( $start = null, $limit = null ){}
}
?>