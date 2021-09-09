<? /* admin controller
*	last update: 2021/6/11
*/

//only for admin
if($user -> get('rank') !== 'admin')
{
	header("Location: ".BASE."/login");
	exit();
}

define('MODULE', 'admin');


switch($url -> getPath(1))
{
	case 'routine_tasks':
		define('TMPL_NAME', 'routine_tasks');
		define('PAGE_TITLE', 'Регламентные задания');
		include_once ABS_PATH.'/tpl/admin/dev/routine_tasks.dev.htm';
		break;

	case 'system':
		switch($url -> getPath(2))
		{
			case 'settings':
				define('TMPL_NAME', 'system_settings');
				define('PAGE_TITLE', 'Настройки системы');
				include_once ABS_PATH.'/tpl/admin/dev/system_settings.dev.htm';
				break;
			
			case 'modules':
				define('TMPL_NAME', 'system_modules');
				define('PAGE_TITLE', 'Модули системы');
				include_once ABS_PATH.'/tpl/admin/dev/system_modules.dev.htm';
				break;

			default:
				header("Location: ".BASE.'/admin');
				exit();
		}
		break;

	case 'users':
		switch($url -> getPath(2))
		{
			case 'rank':
				define('TMPL_NAME', 'users_rank');
				define('PAGE_TITLE', 'Ранги пользователей');
				include_once ABS_PATH.'/tpl/common/simple.htm';
				break;

			case 'company':
				define('TMPL_NAME', 'users_company');
				define('PAGE_TITLE', 'Компании');
				include_once ABS_PATH.'/tpl/common/simple.htm';
				break;

			default:
				define('TMPL_NAME', 'users_list');
				define('PAGE_TITLE', 'Пользователи');
				include_once ABS_PATH.'/tpl/admin/dev/users.dev.htm';
		}
		break;


	case 'menu':
		switch($url -> getPath(2))
		{
			case 'crm':
				define('TMPL_NAME', 'menu_crm');
				define('PAGE_TITLE', 'Редактор меню crm-системы');
				include_once ABS_PATH.'/tpl/admin/menu_editor.htm';
				break;

			case 'main':
				define('TMPL_NAME', 'menu_admin');
				define('PAGE_TITLE', 'Редактор меню админ панели');
				include_once ABS_PATH.'/tpl/admin/menu_editor.htm';
				break;

			default:
				header("Location: ".BASE.'/admin');
				exit();
		}
		break;


  default:
	define('PAGE_TITLE', 'Админ панель');
	include_once ABS_PATH.'/tpl/admin/dev/admin_panel.dev.htm';
}




















exit;
switch($url -> getPath(1))
{
  case 'panel':

    define('TMPL_NAME', 'admin_panel');
    define('PAGE_TITLE', 'Админ панель');
    include_once ABS_PATH.'/tpl/admin/panel.htm'; break;

  case 'users':

    define('TMPL_NAME', 'admin_panel_users');
    define('PAGE_TITLE', 'Пользователи');
    include_once ABS_PATH.'/tpl/admin/admin_panel_users.htm'; break;

  case 'users_rank': //ранги пользователя

    define('TMPL_NAME', 'admin_panel_users_rank');
    define('PAGE_TITLE', 'Ранги пользователей');
    include_once ABS_PATH.'/tpl/common/simple_list.htm'; break;

  case 'users_company': //компания пользователя

    define('TMPL_NAME', 'admin_panel_users_company');
    define('PAGE_TITLE', 'Компания');
    include_once ABS_PATH.'/tpl/common/simple_list.htm'; break;

  //редакторы разных меню
  case 'general_menu_editor':

    define('TMPL_NAME', 'general_menu_editor');
    define('PAGE_TITLE', 'Редактор меню админ панели');
    include_once ABS_PATH.'/tpl/admin/menu_editor.htm'; break;


  case 'menu_editor':

    define('TMPL_NAME', 'menu_editor');
    define('PAGE_TITLE', 'Редактор основного меню');
    include_once ABS_PATH.'/tpl/admin/menu_editor.htm'; break;

  /**/
  case 'menu_editor_gparser':

    define('TMPL_NAME', 'menu_editor_gparser');
    define('PAGE_TITLE', 'Редактор меню парсера');
    include_once ABS_PATH.'/tpl/admin/menu_editor.htm'; break;
	/**/

  case 'stock_menu_editor':

    define('TMPL_NAME', 'stock_menu_editor');
    define('PAGE_TITLE', 'Редактор меню склада');
    include_once ABS_PATH.'/tpl/admin/menu_editor.htm'; break;


  default: header("Location: ".BASE."/admin/panel");
}
?>