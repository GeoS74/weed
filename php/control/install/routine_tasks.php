<?
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli -> set_charset(DB_CHARSET);

if($mysqli -> query('INSERT INTO menu_admin 
							(title, parent_id, pos, alias, limited, hidden)
							VALUES 
							("Регламентные задания", 0, 4, "'.BASE.'/admin/routine_tasks", 0, 0)
							')) printf("Populating the table \"menu_admin\"\n--OK--\n");
else printf("Error: \"menu_admin\" table is not populated\n");

$mysqli = null;

printf("\n--Install routune_task completed--\n\n");
?>