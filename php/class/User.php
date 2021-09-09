<?
class User extends BaseUnit
{
	function __construct($data = array())
	{
		session_start(); //нужно стартовать сессию, т.к. метод getHashPass() использует session_id()

		parent::__construct();

		if(count($data)) $this -> set($data);
	}

	public function login()
	{
		//Round 1: проверка правильности введённых данных
		if(!$this -> checkName($this -> get('name'))) return false;
		if(!$this -> checkPass($this -> get('pass'))) return false;


		/*	технически можно было бы использовать функцию checkNameDouble для проверки имени пользователя на задвоенность ,
			но в этом случае пришлось бы делать дополнительный запрос к БД для контроля правильности введённого пароля.
			Чтобы минимизировать кол-во обращений к БД используется один запрос, по которому и проводится проверка данных
		*/
		//Round 2: проверка имени пользователя
		$mysql_result = $this -> mysql_qw('SELECT * FROM '.$this -> database_table_name.' WHERE name=? LIMIT 1', $this -> get('name'));

		if(!$mysql_result -> num_rows)
		{
		  $this -> set('errors', 'Логин или пароль указан не корректно');
		  return false;
		}

		//Round 3: проверка пароля
		$row = $mysql_result -> fetch_array(MYSQLI_ASSOC); //получить данные пользователя из БД

		if(!$this -> accessCheck($row['pass'], $this -> get('pass')))
		{
		  $this -> set('errors', 'Логин или пароль указан не верно');
		  return false;
		}


		//контроль ip адреса
		$this -> set('ip', $_SERVER['REMOTE_ADDR']);

		if( $this -> get('ip') !== $row['ip'] ) //перезапись ip адреса
			$this -> mysql_qw('UPDATE '.$this -> database_table_name.' SET ip=? WHERE id=?', $this -> get('ip'), $row['id']);


		//Congratulations! Авторизация прошла успешно

		//заполнение полей
		$this
		  -> set('date_create', $row['date_create'])
		  -> set('name', $row['name'])
		  -> set('full_name', $row['full_name'])
		  -> set('email', $row['email'])
		  -> set('company', $row['company'])
		  -> set('rank', $row['rank'])
		  -> set('weight', $row['weight'])
		  //-> set('ip', $row['ip']) //ip адрес устанавливается текущий
		  ;

		return true;
	}

	public function logout()
	{
		$_SESSION = array();
		unset($_COOKIE[session_name()]);
		session_destroy();
		return true;
	}

	public function register()
	{
		$this -> properties = trimer($this -> getProperties());
		
		if(!$this -> checkName($this -> get('name'))) return false;
		if(!$this -> checkNameDouble($this -> get('name'))) return false;
		if(!$this -> checkPass($this -> get('pass'))) return false;
		if(!$this -> checkEmail($this -> get('email'))) return false;
		if(!$this -> checkEmailDouble($this -> get('email'))) return false;

		//если ранг не установлен, то по умолчанию 'user'
		if(!$this -> get('rank')) $this -> set('rank', 'user');
		
		//Congratulations!
		$this
			-> set('pass', $this -> getHashPass($this -> get('pass')))
			-> add();

		return true;
	}

	public function correction() //изменение учётных данных
	{
		$this -> properties = trimer($this -> getProperties());

		//проверка корректности данных
		if(!$this -> checkName($this -> get('name'))) return false;
		if(!$this -> checkEmail($this -> get('email'))) return false;


		//получение текущих данных пользователя
		$mysql_result = $this -> mysql_qw('SELECT id, name, email FROM '.$this -> database_table_name.' WHERE id=? LIMIT 1', $this -> get('id'));

		$current = new BaseUnit(); //текущие данные пользователя
		$current -> set($mysql_result -> fetch_array(MYSQLI_ASSOC));

		//проверка на задвоенность
		if(!$this -> checkNameDouble($this -> get('name'), $current -> get('id'))) return false;
		if(!$this -> checkEmailDouble($this -> get('email'), $current -> get('id'))) return false;

		//если ранг не установлен, то по умолчанию 'user'
		if(!$this -> get('rank')) $this -> set('rank', 'user');

		//Congratulations!
		$this -> upd();

		return true;
	}

	public function changePass()
	{
		if(!$this -> checkPass($this -> get('pass'))) return false;

		$this
			-> set('pass', $this -> getHashPass($this -> get('pass')))
			-> upd();

		return true;
	}

	private function accessCheck($hashPass, $pass)
	{
		$salt = mb_substr($hashPass, 0, 31, 'utf-8'); //выделяет соль из существующего хеша пароля
		return ($hashPass === crypt($pass, $salt)) ? true : false; //хеширует введённый пароль с использованием соли [$salt] и возвращает результат сравнения хешей
	}

	private function getHashPass($pass) //возвращает хеш строки с динамической солью или false
	{
		if(!$pass or (mb_strlen($pass) < 3)) return false;
		return crypt($pass, '$2a$12$'.session_id());
	}

	private function checkName($name)
	{
		//Round 1: проверка длины имени пользователя
		if(mb_strlen($name) < 3)
		{
			$this -> set('errors', 'Слишком короткий логин');
			return false;
		}

		//Round 2: проверка корректности имени пользователя
		if(preg_match(REGEXP_USER_NAME, $name))
		{
			$this -> set('errors', 'Логин или пароль задан не корректно');
			return false;
		}

		return true;
	}

	/*	если передаётся второй параметр, поиск будет производится по строкам с id отличным от $exception_id
	*/
	private function checkNameDouble($name, $exception_id = null)
	{
		//Round 1: проверка имени пользователя на задвоенность
		if(is_null($exception_id)) 	$query =  'SELECT id FROM '.$this -> database_table_name.' WHERE name=? LIMIT 1';
		else 						$query =  'SELECT id FROM '.$this -> database_table_name.' WHERE (name=? AND id!=?) LIMIT 1';
		
		$mysql_result = $this -> mysql_qw($query, $name, $exception_id);

		if($mysql_result -> num_rows)
		{
			$this -> set('errors', 'Пользователь с таким именем уже существует');
			return false;
		}

		return true;
	}

	private function checkPass($pass)
	{
		//Round 1: проверка длины пароля
		if(mb_strlen($pass) < 3)
		{
			$this -> set('errors', 'Слишком короткий пароль');
			return false;
		}

		//Round 2: проверка корректности пароля
		if(preg_match(REGEXP_USER_PASS, $pass))
		{
			$this -> set('errors', 'Логин или пароль указан не корректно');
			return false;
		}

		return true;
	}

	private function checkEmail($email)
	{
		//Round 1: проверка длины email
		if(mb_strlen($email) < 5)
		{
			$this -> set('errors', 'Слишком короткий e-mail');
			return false;
		}

		//Round 2: проверка корректности email
		if(!preg_match(REGEXP_USER_EMAIL, $email))
		{
			$this -> set('errors', 'e-mail указан не корректно');
			return false;
		}

		return true;
	}

	/*	если передаётся второй параметр, поиск будет производится по строкам с id отличным от $exception_id
	*/
	private function checkEmailDouble($email, $exception_id = null)
	{
		//Round 1: проверка mail на задвоенность
		if(is_null($exception_id)) $query = 'SELECT id FROM '.$this -> database_table_name.' WHERE email=? LIMIT 1';
		else $query = 'SELECT id FROM '.$this -> database_table_name.' WHERE (email=? AND id!=?) LIMIT 1';

		$mysql_result = $this -> mysql_qw($query, $email, $exception_id);

		if($mysql_result -> num_rows)
		{
			$this -> set('errors', 'Такой e-mail уже существует');
			return false;
		}

		return true;
	}
}
?>