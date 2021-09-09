<? /* authorization backend controller */
switch($_p['tmpl_data']['template'])
{
	case 'authorization': include_once ABS_PATH.'/php/backend/user/login.php'; break;

	default: throw new BaseException('не выбран backend шаблон');
}
?>