<?/* gazstroyprom controller */
include_once ABS_PATH.'/modules/gazstroyprom/config.php';

switch($url -> getPath(0))
{
	case 'normalize':
		include_once ABS_PATH . '/modules/gazstroyprom/php/control/normalize_documents.php'; break;
		break;

	case 'documents':

		if($url -> getPath(1) === 'add_new') //перехват обращений от 1С
		{
			include_once ABS_PATH.'/modules/gazstroyprom/php/control/1c_query_add.php';
			break;
		}

		define('TMPL_NAME', 'gsp_documents');
		define('PAGE_TITLE', 'Приёмо-сдаточные акты');
		include_once ABS_PATH . '/modules/gazstroyprom/tpl/dev/documents.dev.htm'; break;
		break;

	case 'files': //скачивание файла
		include_once ABS_PATH.'/modules/gazstroyprom/tpl/download_file.htm';
		break;


	default: 
		header("Location: ".BASE."/documents");
		exit;
}
?>