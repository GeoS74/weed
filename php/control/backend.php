<? /* main backend controller */

//config
include_once '../../config.php';

//include functions
include_once ABS_PATH.'/lib/gs_functions.inc';

//autoload class
spl_autoload_register(function($class)
{
	if(file_exists(ABS_PATH.'/php/class/base/'.$class.'.php'))
	{
		include_once ABS_PATH.'/php/class/base/'.$class.'.php';
	}
	else
	{
		include_once ABS_PATH.'/php/class/'.$class.'.php';
	}
});

//lib
require_once(ABS_PATH.'/lib/GsHttpRequest.php');

$GsHttpRequest = new GsHttpRequest('utf-8');

global $_RESULT;

$_p = array_merge($_POST, $_FILES);



/*BUG DETECTED!!!
*	Возможна ситуация, когда сессионная кука на сервере будет удалена, а клиент ещё не обновил страницу,
*	в этом случае при дальнейшем взаимодействии с системой клиент может изменять данные на сервере.
*	А т.к. этот модуль завязан на работе с таблицами определённой компании, а также фиксации действий пользователя,
*	то данные на сервере будут изменены не корректно.
*
*	В качестве решения введена проверка данных пользователя на уровне backend-контроллера.
*
*	При получении ключа 'access' клиент должен предпринять какие-то действия...
*/
session_start();
$user = new User($_SESSION['user']);
session_write_close();

if(!$user -> get('name') && $_p['tmpl_data']['module'] !== 'authorization')
{
	$_RESULT = array('access' => 'denied');
	exit;
}


try
{
	switch($_p['tmpl_data']['module'])
	{
		case 'authorization': include_once ABS_PATH.'/php/control/user/authorization_backend.php'; break;

		case 'admin': include_once ABS_PATH.'/php/control/admin/admin_backend.php'; break;

		default: throw new BaseException('не выбран backend модуль');
	}
}
catch(BaseException $e)
{
  printf("%s\nперехвачено исключение\n", $e -> getMessage());
  print_r($e -> getTrace());
}

		/*E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E
		case 'menu':
			define('DB_TABLE_NAME', 'menu');
			include_once ABS_PATH.'/php/backend/admin/menu_editor.php'; break;

		case 'simple':
			define('DB_TABLE_NAME', 'test');
			include_once ABS_PATH.'/php/backend/common/simple.php'; break;
		//E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E*/
?>