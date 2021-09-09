<?
trimer($_p);

$setting = $_p['form_edit'];

if($_p['tmpl_data']['event'] == 'change')
{
	$arr = file(ABS_PATH.'/config.php');

	for($i = 0; $i < count($arr); $i++)
	{
		if(mb_strpos($arr[$i], 'REGISTRATE_MODE') !== false)
		{
			$arr[$i] = !!$setting['registrate_mode'] ? "define('REGISTRATE_MODE', true);\n" : "define('REGISTRATE_MODE', false);\n";
			continue;
		}
	}
	file_put_contents(ABS_PATH.'/config.php', $arr);
}

$_RESULT = array(
	registration_mode => $_p['tmpl_data']['event'] == 'change' ? $setting['registrate_mode'] : REGISTRATE_MODE,
);
?>