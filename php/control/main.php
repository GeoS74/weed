<?/* main controller */

//config
include_once 'config.php';

//include functions
include_once ABS_PATH.'/lib/gs_functions.inc';

session_start();

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

//create glodal object
$user = new User($_SESSION['user']);

$url = new Url();


//create global const
define('BACKEND_CONTROLLER', BASE.'/php/control/backend.php' );

switch($url -> getPath(0))
{
	case 'test': include_once ABS_PATH . '/tpl/test.htm'; break;

	
	case 'login':
	case 'logout': include_once ABS_PATH.'/php/control/user/authorization.php'; break;
	
	case 'admin': include_once ABS_PATH . '/php/control/admin/admin.php'; break;
	
	default: 
		include_once ABS_PATH.'/tpl/404.htm';
}
	/*E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E
	case 'menu':
		define('MODULE', 'menu');
		define('PAGE_TITLE', 'Редактор меню');
		include_once ABS_PATH.'/tpl/admin/menu_editor.htm'; break;

	case 'simple':
		define('MODULE', 'simple');
		define('PAGE_TITLE', 'Простой список');
		include_once ABS_PATH.'/tpl/common/dev/simple.htm'; break;
	//E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E		E X A M P L E*/


?>