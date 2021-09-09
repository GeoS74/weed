<?/* authorization controller
*	last update: 2021/6/1
*/
define('MODULE', 'authorization');

switch($url -> getPath(0))
{
	case 'login': //авторизация пользователя

		if($user -> get('name'))
		{
			header("Location: ".(mb_strlen(BASE) > 1 ? BASE : '/'));
			exit();
		}

		define('TEMPLATE', 'authorization');
		define('PAGE_TITLE', 'Авторизация пользователя');
		include_once ABS_PATH . '/tpl/user/authorization.htm'; break;

	case 'logout': //завершение сессии
		$user -> logout();

		header("Location: ".BASE.'/login');
		exit();

//default не используется, т.к. используется 0-ой элемент из $url -> getPath(0)
}
?>