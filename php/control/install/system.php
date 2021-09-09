<?/* install controller
*	last update: 2021/7/15
*/
$start_install = false;
$error = array();
$setError = function($msg) use (&$error)
{
	$error[] = sprintf("<strong style='color:red'>%s</strong>", $msg);
};

if(count($_POST))
{
	$base = array_filter(explode('/', $_POST['base']), function($v){return $v;});
	$_POST['base'] = count($base) > 0 ? '/'.implode('/', $base) : '';
	
	if(!$_POST['db_host']) $setError('Укажите адрес сервера');
	if(!$_POST['db_user']) $setError('Укажите логин');
	//if(!$_POST['db_pass']) $setError('Укажите пароль');
	if(!$_POST['db_name']) $setError('Укажите имя базы данных');
	
	if(!count($error)) $start_install = true;
}
if($start_install !== true)
{
	?>
	<h1>Установка системы</h1>
	<?if(count($error)) foreach($error as $k => $v) printf("-%s<br>", $v);?>
	<form method="post" style="width:550px; background:#757575; padding:15px; color:white">
	<input type="text" name="base"    value="<?=$_POST['base']?>"/> Путь до корневой папки системы. Например: /my/dir<br><br>
	<input type="text" name="db_host" value="<?=$_POST['db_host']?>"/> Имя хоста, либо IP-адрес<br><br>
	<input type="text" name="db_user" value="<?=$_POST['db_user']?>"/> Имя пользователя MySQL<br><br>
	<input type="text" name="db_pass" value="<?=$_POST['db_pass']?>"/> Пароль пользователя MySQL<br><br>
	<input type="text" name="db_name" value="<?=$_POST['db_name']?>"/> Имя базы данных<br><br>
	<input type="submit" value="Начать установку" style="cursor:pointer"/>
	</form>
	<?
	exit;
}

try
{
	printf("<pre>Start install...\n\n");

	//step 1
	$arr = file(getenv('DOCUMENT_ROOT').$_POST['base'].'/config.php'); //здесь config.php не подключен, поэтому константа ABS_PATH не доступна

	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_strpos($arr[$i], 'BASE')) 
		{
			$arr[$i] = "define('BASE', '".$_POST['base']."');\n";
			continue;
		}
		if(mb_strpos($arr[$i], 'DB_HOST')) 
		{
			$arr[$i] = "define('DB_HOST', '".$_POST['db_host']."');\n";
			continue;
		}
		if(mb_strpos($arr[$i], 'DB_USER')) 
		{
			$arr[$i] = "define('DB_USER', '".$_POST['db_user']."');\n";
			continue;
		}
		if(mb_strpos($arr[$i], 'DB_PASS')) 
		{
			$arr[$i] = "define('DB_PASS', '".$_POST['db_pass']."');\n";
			continue;
		}
		if(mb_strpos($arr[$i], 'DB_NAME')) 
		{
			$arr[$i] = "define('DB_NAME', '".$_POST['db_name']."');\n";
			break;
		}
	}
	file_put_contents(getenv('DOCUMENT_ROOT').$_POST['base'].'/config.php', $arr); //здесь config.php не подключен, поэтому константа ABS_PATH не доступна
	

	//config
	include_once 'config.php';


	//step 2
	$arr = file(ABS_PATH.'/.htaccess');

	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_strpos($arr[$i], 'RewriteBase') !== false) //совпадение может быть на 0-ой позиции
		{
			$arr[$i] = "RewriteBase ".$_POST['base']."\n";
			break;
		}
	}
	file_put_contents(ABS_PATH.'/.htaccess', $arr);


	//step 3
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
	if($mysqli -> query('CREATE DATABASE IF NOT EXISTS '.DB_NAME.' CHARACTER SET '.DB_CHARSET.' COLLATE '.DB_CHARSET.'_general_ci')) printf("Created database: \"%s\"\n--OK--\n", DB_NAME);
	else throw new Exception('not created database');


	//step 4
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$mysqli -> set_charset(DB_CHARSET);

	if($mysqli -> query('CREATE TABLE IF NOT EXISTS users
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						date_create VARCHAR(255),
						name VARCHAR(255),
						full_name VARCHAR(255),
						pass VARCHAR(255),
						rank VARCHAR(255),
						email VARCHAR(255),
						company VARCHAR(255),
						ip VARCHAR(255),
						weight VARCHAR(255),
						token VARCHAR(255)
						) ENGINE=MyISAM')) printf("Created table: \"users\"\n--OK--\n");
	else throw new Exception('not created table "users"');


	//step 5
	$mysql_result = $mysqli -> query('SELECT id FROM users');

	if($mysql_result -> num_rows) printf("Error: \"users\" table table already has entries\n");
	else
	{
		if($mysqli -> query('INSERT INTO users 
						(name, pass, rank)
						VALUES ("admin", "$2a$12$c9nn28pakricd1p8echmoeDEhXL/ETr6NdkVBpbWNvCe5gw5zImf.", "admin")')) printf("Populating the table \"users\"\n--OK--\n");
		else printf("Error: \"users\" table is not populated\n");
	}


	//step 6
	if($mysqli -> query('CREATE TABLE IF NOT EXISTS users_rank 
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						title VARCHAR(255),
						parent_id INT(11),
						pos INT(11)
						) ENGINE=MyISAM')) printf("Created table: \"users_rank\"\n--OK--\n");
	else throw new Exception('not created table "users_rank"');


	//step 7
	$mysql_result = $mysqli -> query('SELECT id FROM users_rank');

	if($mysql_result -> num_rows) printf("Error: \"users_rank\" table table already has entries\n");
	else
	{
		if($mysqli -> query('INSERT INTO users_rank 
						(title, parent_id, pos)
						VALUES ("admin", 0, 1), ("moderator", 0, 2), ("user", 0, 3)')) printf("Populating the table \"users_rank\"\n--OK--\n");
		else printf("Error: \"users_rank\" table is not populated\n");
	}


	//step 8
	if($mysqli -> query('CREATE TABLE IF NOT EXISTS menu_admin 
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						title VARCHAR(255),
						parent_id INT(11),
						pos INT(11),
						alias VARCHAR(255),
						limited TINYINT(1),
						hidden TINYINT(1)
						) ENGINE=MyISAM')) printf("Created table: \"menu_admin\"\n--OK--\n");
	else throw new Exception('not created table "menu_admin"');


	//step 9
	$mysql_result = $mysqli -> query('SELECT id FROM menu_admin');
	
	if($mysql_result -> num_rows) printf("Error: \"menu_admin\" table table already has entries\n");
	else
	{
		if($mysqli -> query('INSERT INTO menu_admin 
							(title, parent_id, pos, alias, limited, hidden)
							VALUES 
							("Админ панель", 0, 1, "'.BASE.'/admin", 0, 0),
							("Система", 0, 2, "'.BASE.'/admin/system", 0, 0),
							("Настройки", 2, 1, "'.BASE.'/admin/system/settings", 0, 0),
							("Модули", 2, 2, "'.BASE.'/admin/system/modules", 0, 0),
							("Пользователи", 0, 3, "'.BASE.'/admin/users", 0, 0),
							("Компании", 4, 1, "'.BASE.'/admin/users/company", 0, 0),
							("Ранги", 4, 2, "'.BASE.'/admin/users/rank", 0, 0),
							("Home", 0, 0, "'.BASE.'", 0, 0)
							')) printf("Populating the table \"menu_admin\"\n--OK--\n");
		else printf("Error: \"menu_admin\" table is not populated\n");
	}
	
	
	
	$mysqli = null;

	printf("\n--Install completed--\n\n<a href='%s/login'>go to main page</a>\n\n", mb_strlen(BASE) > 0 ? BASE : '/');


	//step 10
	$arr = file(ABS_PATH.'/index.php');

	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_stripos($arr[$i], 'install.php')) 
		{
			$arr[$i] = "include_once('php/control/main.php');\n";
			break;
		}
	}
	file_put_contents(ABS_PATH.'/index.php', $arr);


	//info...
	printf("<strong style='color:red'>Attention!!!</strong> created a user with administrator rights:\n\n");
	printf("Login: \t<b>admin</b>\n");
	printf("Password: <b>admin</b>\n\n");
	printf("You need to change the administrator credentials in the \"users\" section\n\n");
}
catch(Exception $e)
{
	printf("<pre>Error: %s\n", $e -> getMessage());
	dump($e -> getTrace());
}
?>