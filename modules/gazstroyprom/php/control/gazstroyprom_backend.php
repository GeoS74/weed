<? /* admin backend controller */

if(!$user -> get('name')) die( 'пользователь не авторизирован' );

switch($_p['tmpl_data']['template'])
{
	case 'gsp_documents':
		include_once ABS_PATH.'/modules/gazstroyprom/php/backend/gsp_documents.php'; break;

	case 'users_company':
		define('DB_TABLE_NAME', 'users_company');
		include_once ABS_PATH.'/php/backend/common/simple.php'; break;

	default: throw new BaseException('не выбран backend шаблон');
}
?>