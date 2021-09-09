<?
define('MODULE', 'gazstroyprom');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

//step 1 - запись в меню системы
$mysqli -> query('CREATE TABLE IF NOT EXISTS menu_crm 
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					    title VARCHAR(255),
					    parent_id INT(11),
					    pos INT(11),
					    alias VARCHAR(255),
					    limited BOOLEAN,
					    hidden BOOLEAN
						) ENGINE=MyISAM'
					);

$mysql_result = $mysqli -> query('SELECT id FROM menu_crm WHERE alias LIKE "%/documents"');
	
if($mysql_result -> num_rows) printf("Error: \"menu_crm\" table table already has entries\n");
else
{
	if($mysqli -> query('INSERT INTO menu_crm 
								(title, parent_id, pos, alias, limited, hidden)
								VALUES 
								("Приёмо-сдаточные акты", 0, 0, "'.BASE.'/documents", 1, 0)
								')) printf("Populating the table \"menu_crm\"\n--OK--\n");
	else printf("Error: \"menu_crm\" table is not populated\n");
}

//step 2 - добавление в главный контроллер
$arr = file(ABS_PATH.'/php/control/main.php');

for($i = 0, $exist = false; $i < count($arr); $i++) //проверить установку модуля
{
	if(mb_strpos($arr[$i], '/modules/'.MODULE) !== false)
	{
		$exist = true;
		break;
	}
}
if(!$exist) //добавить контроллер модуля
{
	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_strpos($arr[$i], 'break;') !== false)
		{
			$arr[$i] .= "\n\t".'case \'documents\': include_once ABS_PATH.\'/modules/'.MODULE.'/php/control/gazstroyprom.php\';  break;';
			break;
		}
	}
	file_put_contents(ABS_PATH.'/php/control/main.php', $arr);
}


//step 3 - добавление в backend контроллер
$arr = file(ABS_PATH.'/php/control/backend.php');

for($i = 0, $exist = false; $i < count($arr); $i++) //проверить установку модуля
{
	if(mb_strpos($arr[$i], '/modules/'.MODULE) !== false)
	{
		$exist = true;
		break;
	}
}
if(!$exist) //добавить контроллер модуля
{
	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_strpos($arr[$i], 'break;') !== false)
		{
			$arr[$i] .= "\n\t\t".'case \''.MODULE.'\': include_once ABS_PATH.\'/modules/'.MODULE.'/php/control/gazstroyprom_backend.php\';  break;';
			break;
		}
	}
	file_put_contents(ABS_PATH.'/php/control/backend.php', $arr);
}

//step 4 - добавить время установки модуля
$arr = file(__DIR__.'/config.php');

for($i = 0; $i < count($arr); $i++) //проверить установку модуля
{
	if(mb_strpos($arr[$i], 'DATE_INSTALL') !== false)
	{
		$arr[$i] = 'define(\'DATE_INSTALL\', \''.time().'\');'."\n";
		break;
	}
	
	if($i === count($arr) - 1) //если на последней итерации в config не находит константу DATE_INSTALL - дописать её
	{
		$arr[ $i -1 ] .= "\n".'define(\'DATE_INSTALL\', \''.time().'\');'."\n";
	}
}
file_put_contents(__DIR__.'/config.php', $arr);
?>