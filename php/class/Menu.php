<?
class Menu extends DataBase
{
	private $db_table_name;
	private $user;
	private $graf;
	private $active_id;

	function __construct($db_table_name, $user)
	{
		$this -> db_table_name = (string)$db_table_name;

		$this -> user = $user;

		$mysql_result = $this -> mysql_qw('SELECT * FROM '.$this -> db_table_name);

		$arr = array();

		if($mysql_result)
			while($row = $mysql_result -> fetch_assoc()) $arr['+'.$row['id']] = htmlspecialcharser($row);

		$this -> graf = new Graf($arr);

		$this -> graf -> sortBy('pos');
	}

	public function getMenu()
	{
		if($this -> graf -> getGraf())
			$this -> active_id = $this -> getActiveId();

		return $this -> createMenu($this -> makeMenu());
	}

	private function makeMenu()
	{
		$makeMenu = function( $arr, $subMenu = false ) use ( &$makeMenu )
		{
			if(!is_array($arr)) return null;
			
			$result = !$subMenu ? '<ul class="top_menu">' : '<ul class="sub_menu">';

			foreach($arr as $val)
			{
				if($val['hidden'] && $this -> user -> get('rank') != 'admin') continue; //если пользователь не админ и элемент скрыт завершает итерацию
				if($val['limited'] && $this -> user -> get('rank') != 'admin' && $this -> user -> get('rank') != 'moderator') continue; //если пользователь не админ и не модератор и элемент ограничен завершает итерацию
				
				$result .= $val['id'] == $this -> active_id ? '<li class="active_menu">' : '<li>';

				if($val['alias']) $result .= '<a href="'.$val['alias'].'">'.$val['title'].'</a>';
				else $result .= '<span>'.$val['title'].'</span>';

				//если по ключу next доступен массив - рекурсивно вызывает функцию, для формирования вложенных списков
				if(is_array($val['next'])) $result .= $makeMenu($val['next'], true);

				$result .= '</li>';
			}
			return $result .= '</ul>';
		};
		
		return $makeMenu( $this -> graf -> getGraf() );
	}

	private function createMenu($menu)
	{
		$result = '<div class="menu">';

		//insert logo
		if($this -> user -> get('company') == 'bovid')
		{
			$result .= '<img src="'.BASE.'/img/logo_300_83.jpg" ';

			if(!$this -> user -> get('rank')) $result .= 'onclick="location=\''.BASE.'/login\'"';

			$result .= '/>';
		}

		$result .= $menu;

		return $result .= '</div>';
	}
  
	private function getActiveId()
	{
		$active_id = 0;

		$this -> graf -> each(function($index, $e) use (&$active_id)
		{
			if($active_id) return;

			if($e['alias'] == $_SERVER['REQUEST_URI']) $active_id = $e['id'];
		});
		return $active_id;
	}
}