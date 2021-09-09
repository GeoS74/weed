<?
class Note extends BaseUnit
{
	protected function propProcessing($key, $val='')
	{
		switch( $key )
		{
		  case 'parent_id':
		  case 'pos': $val = (int)$val; break;
		}
		parent::propProcessing($key, $val);
	}
}

$note = new Note;

$note -> mysql_qw('CREATE TABLE IF NOT EXISTS '.DB_TABLE_NAME.' 
					  (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					    title VARCHAR(255),
					    parent_id INT(11),
					    pos INT(11)
						) ENGINE=MyISAM'
					);

$note
	-> setDatabaseTableName(DB_TABLE_NAME)
	-> set(trimer($_p['form_data']));

switch($_p['tmpl_data']['event'])
{
	case 'add': $note -> add(); break;
	case 'upd': $note -> upd(); break;

	case 'del':
		$mysql_result = $note -> mysql_qw( 'SELECT * FROM '. DB_TABLE_NAME );
		if($mysql_result -> num_rows)
		{
			$arr = array();
			while($row = $mysql_result -> fetch_assoc()) $arr[] = $row;

			$child = new Graf($arr);
			$child = $child -> getChildrenById($note -> get('id'));

			if($child -> getGraf()[0]['level'] > 1) //переопределение потомков
			{
				$note -> mysql_qw( 'UPDATE '.DB_TABLE_NAME.' SET parent_id=? WHERE parent_id=?', $child -> getGraf()[0]['parent_id'], $child -> getGraf()[0]['id']  );
				$note -> del();
			}
			else //удаление всей ветки
			{
				$arr = array();
				$child -> each(function($i, $e) use(&$arr)
				{
					$arr[] = $e['id'];
				});
				
				$note -> set('id', $arr) -> del();
			}
		}
		break;
}
$mysql_result = $note -> mysql_qw('SELECT * FROM '.DB_TABLE_NAME);
while($row = $mysql_result -> fetch_array( MYSQLI_ASSOC )) $_RESULT['+'.$row['id']] = htmlspecialcharser($row);
?>