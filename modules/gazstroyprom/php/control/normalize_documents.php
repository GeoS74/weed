<?/*разносит сериализованные данные о счётах и УПД по разным таблицам
*/
echo '<pre>';

$db = new DataBase;

$db -> dropTable('gsprom_sales');
$db -> dropTable('gsprom_scores');

if($db -> mysql_qw('CREATE TABLE IF NOT EXISTS gsprom_sales
	(
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		parent_id VARCHAR(10),
		sale_number VARCHAR(255),
		sale_date VARCHAR(255),
		invoice_number VARCHAR(255),
		invoice_date VARCHAR(255)
	)
	ENGINE=MyISAM')) printf("Created table: \"gsprom_sales\"\n--OK--\n");
else throw new Exception('not created table "gsprom_sales"');

if($db -> mysql_qw('CREATE TABLE IF NOT EXISTS gsprom_scores
	(
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		parent_id VARCHAR(10),
		score_number VARCHAR(255),
		score_date VARCHAR(255),
		paid VARCHAR(15)
	)
	ENGINE=MyISAM')) printf("Created table: \"gsprom_scores\"\n--OK--\n");
else throw new Exception('not created table "gsprom_scores"');






$query = 'SELECT * FROM gsprom_document';
//$query = 'SELECT * FROM gsprom_document WHERE id=513 LIMIT 30';
//$query = 'SELECT * FROM gsprom_document WHERE score IS NOT NULL LIMIT 30';

$mysql_result = $db -> mysql_qw($query);

if($mysql_result -> num_rows)
{
	$reg_sales = new Registry;
	$reg_sales -> setDatabaseTableName('gsprom_sales');
	$reg_scores = new Registry;
	$reg_scores -> setDatabaseTableName('gsprom_scores');

	$sales = array();
	$scores = array();
	
	while($row = $mysql_result -> fetch_assoc())
	{
		//dump($row);

		$sale = unserialize($row['sale']);
		$score = unserialize($row['score']);

		//dump($sale);
		//dump($score);

		if( is_array($sale) && count($sale) )
		{
			for($i = 0; $i < count($sale); $i++)
			{
				$sl = array(
					'parent_id' => $row['id'],
					'sale_number' => $sale[$i]['sale_number'],
					'sale_date' => $sale[$i]['sale_date'],
				);

				if( is_array($sale[$i]['invoice'][0]) && count($sale[$i]['invoice'][0]) )
				{
					$sl['invoice_number'] = $sale[$i]['invoice'][0]['invoice_number'];
					$sl['invoice_date'] =   $sale[$i]['invoice'][0]['invoice_date'];
				}

				$sales[] = $sl;
			}

			if(count($sales) > 100)
			{
				$reg_sales -> add($sales);
				$sales = array();
			}
		}	


		if( is_array($score) && count($score) )
		{
			for($i = 0; $i < count($score); $i++)
			{
				$sc = array(
					'parent_id' => $row['id'],
					'score_number' => $score[$i]['score_number'],
					'score_date' => $score[$i]['score_date'],
				);

				$scores[] = $sc;
			}

			if(count($scores) > 100)
			{
				$reg_scores -> add($scores);
				$scores = array();
			}
		}
	}
	//dump($sales);
	//dump($scores);
	
	if(count($sales)) $reg_sales -> add($sales);
	if(count($scores)) $reg_scores -> add($scores);
}
else die('нет данных');


printf("Congratulations\n--OK--\n");
?>