<?
$user = new User($_p['form_data']);

$user -> setDatabaseTableName('users');

switch($_p['tmpl_data']['event'])
{
	case 'login':
		if($user -> login())
		{
			$_SESSION['user'] = array(
				'date_create' => $user -> get('date_create'),
				'name' 		=> $user -> get('name'),
				'full_name' => $user -> get('full_name'),
				'email' 	=> $user -> get('email'),
				'company' 	=> $user -> get('company'),
				'rank' 		=> $user -> get('rank'),
				'weight' 	=> $user -> get('weight'),
				'ip' 		=> $user -> get('ip'),
			);
			
			$_RESULT = array('login' => true);
		}
		else
		  $_RESULT = array('errors' => $user -> get('errors'));
		break;

	case 'register':
		$user -> set('ip', $_SERVER['REMOTE_ADDR']);
		  
		$_RESULT = $user -> register() ? array('register' => true) : array('errors' => $user -> get('errors'));	
		break;
}
?>