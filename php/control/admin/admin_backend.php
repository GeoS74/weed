<? /* admin backend controller */
switch($_p['tmpl_data']['template'])
{
	case 'routine_tasks':
		include_once ABS_PATH.'/php/backend/admin/routine_tasks.php'; break;

	case 'system_settings':
		include_once ABS_PATH.'/php/backend/admin/system_settings.php'; break;
	
	case 'system_modules':
		include_once ABS_PATH.'/php/backend/admin/system_modules.php'; break;

	case 'users_list':
		define('DB_TABLE_NAME', 'users');
		include_once ABS_PATH.'/php/backend/admin/users_list.php'; break;

	case 'users_rank':
		define('DB_TABLE_NAME', 'users_rank');
		include_once ABS_PATH.'/php/backend/common/simple.php'; break;
	
	case 'users_company':
		define('DB_TABLE_NAME', 'users_company');
		include_once ABS_PATH.'/php/backend/common/simple.php'; break;
	
	case 'menu_crm':
		define('DB_TABLE_NAME', 'menu_crm');
		include_once ABS_PATH.'/php/backend/admin/menu_editor.php'; break;
	
	case 'menu_admin':
		define('DB_TABLE_NAME', 'menu_admin');
		include_once ABS_PATH.'/php/backend/admin/menu_editor.php'; break;

	default: throw new BaseException('не выбран backend шаблон');
}
?>