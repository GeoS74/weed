<?
trimer($_p);

$dir = '/modules';

if(!file_exists(ABS_PATH.$dir)) createDir($dir);


print_r($_p);


if($_p['tmpl_data']['event'] === 'install' && $_p['form_edit']['module']) //инсталляция модуля
{
	include_once(sprintf("%s/%s/install.php", ABS_PATH.$dir, $_p['form_edit']['module']));
}

$modules = array();

foreach(scandir(ABS_PATH.$dir) as $m)
{
	if($m === '..' || $m === '.') continue;

	$module = array();
	
	$module['name'] = $m;

	if(!file_exists(sprintf('%s/%s/install.php', ABS_PATH.$dir, $m)))
		$module['error'][] = ' не найден файл install.php ';

	if(!file_exists(sprintf('%s/%s/config.php', ABS_PATH.$dir, $m)))
		$module['error'][] = ' не найден файл config.php ';
	else
	{
		//при установке в config модуля прописывается дата установки
		$conf = file(sprintf('%s/%s/config.php', ABS_PATH.$dir, $m));

		for($i = 0, $install = false; $i < count($conf); $i++)
		{
			if(mb_stripos($conf[$i], 'DATE_INSTALL') !== false) 
			{
				if( preg_match('/\d{10}/', $conf[$i]) ) $install = true;
				break;
			}
		}
		$module['install'] = $install ? 1 : 0;
	}


	$modules[] = $module;
}


$_RESULT = array(
	'main_data' => $modules,
	'meta_data' => array(
			'event' => $_p['tmpl_data']['event'],
		),
	);
?>