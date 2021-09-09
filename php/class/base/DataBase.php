<?
/*Класс DataBase
*
*Описание:
*	Класс DataBase содержит методы для работы с БД
*
*Интерфейс:
*	mysql_qw() - функция обращается к БД
*	getLastId - возвращает последнее значение id записи в БД при выполнении INSERT-a. При выполнении других запросов значение - 0
*
*	mysql_qw - функция обращается к БД
*	mysql_last_id - возвращает последнее значение id записи в БД при выполнении INSERT-a. При выполнении других запросов значение - 0
*	createTable - создаёт таблицу в БД
*	dropTable - удаляет таблицу из БД
*	addFieldsToTable - добавляет столбцы в таблицу БД
*/
class DataBase
{
	private $last_id = 0;
	private $mysqli = null;

	function __construct()
	{
		$this -> getMysqli();
	}

	public function mysql_qw()
	{
		if($this -> mysqli === null) $this -> getMysqli();

		@$mysql_result = $this -> mysqli -> query($this -> mysql_make_qw(func_get_args()));
		
		@$this -> last_id = $this -> mysqli -> insert_id;
		
		return $mysql_result; 
	}

	/*возвращает значение id (AUTO_INCREMENT) после последней операции INSERT или 0
	*/
	public function mysql_last_id(){return $this -> last_id;}

	/*создаёт таблицу в БД
	*
	*Описание:
	*		object createTable( string $table_name, mixed $columns_name )
	*
	*		возвращает $this либо завершает выполнение скрипта
	*
	*Список аргументов:
	*		table_name - имя создаваемой таблицы
	*		columns_name - перечень имён столбцов. Можно использовать массив
	*
	*Примечание:
	*1)	первый элемент в списке (массиве) columns_name становится PRIMARY KEY,
	*		остальные имеют тип VARCHAR(255)
	*2)	перед созданием таблицы, производится проверка наличия таблицы в БД.
	*		если таблица уже существует, то всё равно вернёт true
	*/
	public function createTable()
	{
		$args = func_get_args();

		if(count($args) < 2) die($this -> getErrorMessage('количество передаваемых аргументов меньше 2', __METHOD__));

		$table_name = $args[0];

		if(!validationSymbol($table_name)) die($this -> getErrorMessage('имя таблицы содержит запрещённые символы, либо не является строкой', __METHOD__));

		$columns_name = is_array($args[1]) ? $args[1] : array_slice($args, 1);

		if(!count($columns_name)) die($this -> getErrorMessage('не заданы имена столбцов', __METHOD__));

		$args[0] = 'CREATE TABLE IF NOT EXISTS %s (';

		$args[1] = $table_name;

		for($i = 0; $i < count($columns_name); $i++)
		{
		  if(!validationSymbol($columns_name[$i])) die($this -> getErrorMessage('имя столбца: "'.$columns_name[$i].'" содержит запрещённые символы', __METHOD__));

			if($i == 0)
			{
				$args[0] .= '%s INT NOT NULL AUTO_INCREMENT, ';
				$primary_key = $columns_name[$i];
			}
			else $args[0] .= '%s VARCHAR(255), ';

			$args[$i + 2] = $columns_name[$i];
		}

		$args[0] .= 'PRIMARY KEY('. $primary_key .'))';

		$this -> mysql_qw( call_user_func_array( "sprintf", $args ) ) or die($this -> getErrorMessage('таблица в БД не создана', __METHOD__));
		return $this;
	}

	/*удаляет таблицу из БД
	*
	*Описание:
	*		boolean dropTable( string $table_name )
	*
	*		возвращает true в случае успеха (на данный момент false не возвращается, при неудачной попытке создания таблицы вызывается оператор die() )
	*
	*Примечание:
	*		перед удалением таблицы, производится проверка наличия таблицы в БД.
	*		если таблица уже отсутствует, то всё равно вернёт true
	*/
	public function dropTable($table_name)
	{
		if(!validationSymbol($table_name)) die($this -> getErrorMessage('имя таблицы содержит запрещённые символы, либо не является строкой', __METHOD__));

		$this -> mysql_qw( 'DROP TABLE IF EXISTS ' . $table_name ) or die($this -> getErrorMessage('таблица в БД не удалена', __METHOD__));
		
		return $this;
	}

	/*добавляет столбцы в таблицу БД
	*/
	public function addFieldsToTable()
	{
		$args = func_get_args();

		if( count( $args ) < 2 ) die($this -> getErrorMessage('количество передаваемых аргументов меньше 2', __METHOD__));
		
		$table_name = $args[0];

		$fields_name = is_array($args[1]) ? $args[1] : array_slice( $args, 1 );

		if(!count($fields_name)) die($this -> getErrorMessage('не заданы имена столбцов', __METHOD__));
		
		$fields = array_fill(0, count($fields_name), 'ADD %s VARCHAR(255)');
		
		$query = 'ALTER TABLE '.$table_name.' '.implode(', ', $fields);
		
		$args = array( $query );

		//print_r(call_user_func_array( "sprintf", array_merge($args, $fields_name) ));
		
		$this -> mysql_qw( call_user_func_array( "sprintf", array_merge($args, $fields_name) ) ) or die($this -> getErrorMessage('поля не добавлены в таблицу', __METHOD__));
		
		return $this;
	}

	/*формирует строку sql-запроса
	*
	*Описание:
	*		string mysql_make_qw( array $args )
	*		
	*		экранирует спец. символы и возвращает строку sql-запроса
	*
	*Список аргументов:
	*		args - массив с данными
	*			   Первый элемент массива всегда шаблон с плейсхолдерами. 
	*			   Второй - либо перечень аргументов, либо массив аргументов.
	*
	*Примечание:
	*		если плейсхолдеров больше чем передаваемых аргументов, аргументы добиваются "Unknow_placeholder_n", где n-номер по порядку неизвестного аргумента
	*/
	private function mysql_make_qw($args)
	{
		$tmpl =& $args[0]; //ссылка на шаблон

		$tmpl = str_replace('%', '%%', $tmpl); //если в шаблоне используется спец. символ %, то он удваивается
		$tmpl = str_replace('?', '%s', $tmpl); //знаки ? заменяются спец. символами %s

		$argsList = is_array($args[1]) ? $args[1] : array_slice($args, 1); //проверка второго аргумента на массив

		$argsList = array_values($argsList); //если ключи строковые, меняет их на числовые

		foreach($argsList as $i => $v) //экранирует спец. символы и перезаписывает массив $args. Первый элемент это шаблон
		{
			$args[$i + 1] = "'".$this -> mysqli -> real_escape_string($v)."'";
		}

		for ($i = $c = count($args) - 1; $i < $c + 20; $i++) //добивает недостающие аргументы
		{
			$args[$i + 1] = "Unknow_placeholder_" . $i;
		}
		//print_r( call_user_func_array( "sprintf", $args )."\n\n" );
		return call_user_func_array( "sprintf", $args );
	}
  
	private function getMysqli()
	{
		@$this -> mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		@$this -> mysqli -> set_charset(DB_CHARSET);
		return $this;
	}
	
	private function getErrorMessage($message, $method)
	{
		return sprintf("Error: %s (Класс: %s, Метод: %s)", $message, __CLASS__, $method);
	}
}
?>