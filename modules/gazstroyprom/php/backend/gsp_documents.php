<?
/*ВНИМАНИЕ!!! при любых тестах отключать отправку сообщений на e-mail!!!

*методы объекта Registry должны работать и возвращать структуру вида:
	Array
	(
		[0] => Array
				(
					[id] => 3
					[date_create] => 1630416174000
					[status] => поступила заявка
					...
					[comment_status] => 
				)
		[1] => Array
				(
					[id] => 4
					[date_create] => 1630416174000
					[status] => поступила заявка
					...
					[comment_status] => 
				)
	)

*такой массив может получить объект для редактирования/удаления строк, он должен быть преобразован к виду (см. выше):
	Array
	(
		[0] => Array
				(
					[id] => Array
						(
							[0] => 3
							[1] => 4
						)
					[date_create] => 1630416174000
					[status] => поступила заявка
					...
					[comment_status] => 
				)

	)
*для преобразования структуры используется метод transformCurrentData
*/
class Reg extends Registry
{
	public function saveExcel($data = array())
	{
		$this -> search($data);

		$data = $this -> out();

		$result = array();
		
		$result[] = array(
				null,
				'Дата док-та',
				'Номер док-та',
				'Автомобиль',
				'Статус',
				'Сумма',
				'Мастер',
				'Заказчик',
			);
		
		for($i = 0; $i < count($data); $i++)
		{
			$result[] = array(
				($i + 1),
				date('Y-m-d H:i:s', $data[$i]['date_document']/1000),
				$data[$i]['number'],
				$data[$i]['car'],
				$data[$i]['status'],
				$data[$i]['sum'],
				$data[$i]['master'],
				$data[$i]['customer'],
			);
		}
		return $result;
	}





	/*
	если форма передаст один и тот же файл два и более раз, то в момент загрузки файлов нельзя прикрепить один и тот же файл к одному ПСА
	дубликат будет удалён.
	Если какой-то файл уже привязан к ПСА и загружен на сервер и его попытаться снова привязать к ПСА, в рамках новой передачи данных, то
	это сработает, но файл будет переименован (см. условие (1))
	*/
	public function uploadFiles()
	{
		$dir = '/files/gsp/documents';
		createDir($dir);
		
		$data = func_get_args()[0];
		$user = func_get_args()[1];

		for($i = 0, $files_arr = array(), $dublicate = array(); $i < count($data['files']['name']); $i++)
		{
			if($data['files']['error'][$i] !== 0) continue;

			if($data['files']['size'][$i] > 25000000) continue; //ограничение по размеру файла 25 МБ

			/*switch($data['files']['type'][$i]) //проверка на тип файла
			{
				case'image/jpeg':
				case'application/pdf':
					break;
				default: continue;
			}*/

			$f_name = md5( @file_get_contents($data['files']['tmp_name'][$i]) ); //генерация нового имени файла
			
			if($dublicate[$f_name]) continue; //защита от дублирования файлов в рамках одной передачи данных(1)
			else $dublicate[$f_name] = true;

			if(!mb_strlen($data['files_name'][$i])) $data['files_name'][$i] = 'Скан-копия файла';

			$extension = pathinfo($data['files']['name'][$i], PATHINFO_EXTENSION); //расширение файла

			$files_arr[] = array(
				'date_create' => $data['date_create'],
				'parent_id' => $data['id'],
				
				'file_name' => $f_name,
				'file_extension' => $extension,
				'file_title' => $data['files_name'][$i],
				'file_type' => $data['files']['type'][$i],

				'user' => $user -> get('name'),
				'company' => $user -> get('company'),
			);
		}

		//контроль задвоенных файлов
		$files_arr = $this -> checkFilesName($files_arr);

		//запись в БД
		if(count($files_arr))
		{
			$reg = new Registry;
			$reg
				-> setDataBasetableName( $this -> database_table_name.'_files' )
				-> add($files_arr);
		}

		//перемещение временных файлов (кол-во элементов в массивах $files_arr и $tmp_names должны быть равны)
		$tmp_names = array_values(array_diff($data['files']['tmp_name'], array(null)));


		for($i = 0; $i < count($files_arr); $i++)
		{
			$file_path = sprintf("%s/%s.%s", ROOT_PATH.$dir, $files_arr[$i]['file_name'], $files_arr[$i]['file_extension']);

			move_uploaded_file($tmp_names[$i], $file_path);
		}

		$this -> setCurrentData(array('id'=>$data['id']));

		return $this;
	}


	/*изменяет название файла
	*/
	public function editFiles()
	{
		if(func_get_args()[0]['files_name'])
		{
			$reg = new Registry;
			$reg -> setDatabaseTableName($this -> database_table_name.'_files');

			foreach(func_get_args()[0]['files_name'] as $k => $v)
				$reg -> upd(array('id' => $k, 'file_title' => (mb_strlen($v) ? $v : 'Скан-копия файла')));

			$reg = null;
		}

		$this -> setCurrentData(array('id' => func_get_args()[0]['id']));

		return $this;
	}

	/*удаление одного файла
	*/
	public function delFile()
	{
		//перемещение удаляемого файла в корзину
		$query = 'SELECT file_name, file_extension FROM '.$this -> database_table_name.'_files WHERE id=?';

		$mysql_result = $this -> mysql_qw($query, func_get_args()[0]['id_del_file']);

		if($mysql_result -> num_rows)
		{
			$dir = '/files/gsp/documents';

			createDir($dir.'/delete');

			while($row = $mysql_result -> fetch_assoc())
			{
				$file_path_old = sprintf("%s/%s.%s", ROOT_PATH.$dir, $row['file_name'], $row['file_extension']);
				$file_path_new = sprintf("%s/delete/%s.%s", ROOT_PATH.$dir, $row['file_name'], $row['file_extension']);
				@rename( $file_path_old, $file_path_new );
			}
		}

		//удаление данных из БД
		$reg = new Registry;
		$reg
			-> setDatabaseTableName($this -> database_table_name.'_files')
			-> del(array('id' => func_get_args()[0]['id_del_file']));
		$reg = null;


		$this -> setCurrentData(array('id' => func_get_args()[0]['id']));

		return $this;
	}
	
	
	
	

	public function add()
	{
		parent::add(func_get_args()[0]);
		$this -> addStatusHistory(func_get_args()[1]);
		return $this;
	}
	
	public function del()
	{
		parent::del(func_get_args()[0]);
		$this -> delStatusHistory(func_get_args()[0]);
		$this -> delRelatedFiles(func_get_args()[0]);
		$this -> transformCurrentData();
		return $this;
	}

	public function upd()
	{
		parent::upd(func_get_args()[0]);
		$this -> transformCurrentData();
		$this -> addStatusHistory(func_get_args()[1]);
		return $this;
	}



	/*необходимо фиксировать изменения статусов только у тех записей, где статус меняется
	*
	*	1) собрать массив с данными статусов, где ключ - это id ПСА
	*	2) выделить id записей ПСА в отдельный массив
	*	3) запросить текущие статусы ПСА в таблице историй записей
	*	4) сравнить текущие статусы с подготовленными изменениями. Если статус не меняется заменить массив с данными на null
	*	5) выкинуть из массива status_history значения null
	*	6) сбросить ключи массива status_history
	*
	*	Про запрос к БД:
	*	чтоб получить максимальные даты нужно учесть два тонких момента:
	*	1) нужно присваивать алиас parent_id as pid, иначе запрос не отработает, т.к. поле parent_id используется в первой таблице
	*	2) группировать данные GROUP BY pid
	*	
	*	вернёт последние изменения статусов по каждому ПСА
	*/
	protected function addStatusHistory($user)
	{
		$status_history = array();

		foreach($this -> current_data as $k => $v) 					//(1)
			$status_history[ $v['id'] ] = array(
				'date_create' => $v['date_create'],
				'parent_id' => 	$v['id'],
				'status' => 	$v['status'],
				'comment' => 	$v['comment_status'],
				'user' => 		$user -> get('name'),
				'ip' => 		$user -> get('ip'),
				'rank' => 		$user -> get('rank'),
				'company' => 	$user -> get('company')
			);

		$rows_id = array_keys($status_history); 					//(2)

		$plch_id = array_fill(0, count($rows_id), '?'); 			//(3)

		$query = 'SELECT status, parent_id
					FROM gsprom_document_change_status PSA
					INNER JOIN (
						SELECT parent_id as pid, MAX(date_create) as max_date
						FROM gsprom_document_change_status
						GROUP BY pid
					) STA
					ON PSA.date_create = STA.max_date
					WHERE parent_id IN ('.implode( ', ', $plch_id ).')';

		$mysql_result = $this -> mysql_qw($query, $rows_id);

		if($mysql_result -> num_rows)
		{
			while($row = $mysql_result -> fetch_assoc()) 			//(4)
			{
				if( $status_history[ $row['parent_id'] ]['status'] === $row['status'] ) $status_history[ $row['parent_id'] ] = null;
			}
		}

		$status_history = array_diff($status_history, array(null));	//(5)

		if(!count($status_history)) return $this; //записывать нечего, статусы не меняются

		$status_history = array_values($status_history); 			//(6)

		$reg = new Registry;
		$reg
			-> setDataBasetableName( $this -> database_table_name.'_change_status' )
			-> add($status_history);

		return $this;
	}

	protected function delStatusHistory($rows)
	{
		$reg = new Registry;
		$reg
			-> setDatabaseTableName($this -> database_table_name.'_change_status')
			-> del($rows, 'parent_id');
		return $this;
	}

	/*Функция дозаписывает историю изменения статусов в результирующий набор данных Registry
	*
	*	логика:
	*	1) собрать id текущей выборки данных Registry в массив
	*	2) по этим id запросить в БД историю статусов
	*	3) собрать массив, где в качестве ключа используется parent_id статуса, а в качестве данных - массив массивов с историей статусов
	*	4) раскидать массивы с историей по текущим записям набора данных Registry
	*/
	protected function getStatusHistory()
	{
		for($i = 0, $rows_id = array(); $i < count($this -> current_data); $i++) 	//(1)
			$rows_id[] = $this -> current_data[$i]['id'];

		$plch_id = array_fill(0, count($rows_id), '?');

		$query = 'SELECT parent_id, status, date_create, user, comment FROM '.$this -> database_table_name.'_change_status WHERE parent_id IN ('.implode( ', ', $plch_id ).') ORDER BY id DESC';

		$mysql_result = $this -> mysql_qw($query, $rows_id); 						//(2)
		
		if(!$mysql_result -> num_rows) return; //записей нет

		$status_arr = array(); 														//(3)
		while($row = $mysql_result -> fetch_assoc()) //записи в таблице статусов для этого ПС-Акта есть
			$status_arr[$row['parent_id']][] = $row;

		
		for($i = 0; $i < count($this -> current_data); $i++) 						//(4)
			if( $status_arr[ $this -> current_data[$i]['id'] ] )
				$this -> current_data[$i]['status_history'] = $status_arr[ $this -> current_data[$i]['id'] ];
	}

	/*проверяет на дубликаты загружаемые файлы
	*
	*	1) собрать имена файлов в массив для запроса
	*	2) создать временную таблицу и записать в неё искомые имена файлов, при этом к имени дописать %, для возможности поиска имен файлов с любым окончанием (_1, _2 ..._n)
	*	3) объединить две таблицы с помощью оператора LIKE, если будет найден результат, то дубликаты есть
	*	4) в массив $dublicate в качестве ключа записать имя файла (без постфикса типа _21), а в качестве значения следующий по порядку постфикс копии файла
	*	5) проверить есть ли дубликаты файлов, и если есть - к имени файла присвоить новый постфикс
	*/
	protected function checkFilesName($files)
	{
		if(!count($files)) return $files;

		for($i = 0, $files_name = array(); $i < count($files); $i++) //		(1)
			$files_name[] = $files[$i]['file_name'];

		$plch = array_fill(0, count($files_name), '(CONCAT(?, "%"))'); //	(2)
		
		$mysql_result = $this -> mysql_qw('CREATE TEMPORARY TABLE patterns (pattern VARCHAR(45))');
		$mysql_result = $this -> mysql_qw('INSERT INTO patterns VALUES '.implode( ', ', $plch), $files_name);

		$query = 'SELECT F.file_name 
					FROM '.$this -> database_table_name.'_files F
					JOIN patterns P
					ON (F.file_name LIKE P.pattern)
					ORDER BY F.id'; //обязательно применять сортировку, иначе можно получить не корректное значение постфикса копии файла

		$mysql_result = $this -> mysql_qw($query, $files_name);

		$dublicate = array();
		if($mysql_result -> num_rows) //дубликаты есть 						(3)
		{
			while($row = $mysql_result -> fetch_assoc())
				$dublicate[mb_substr($row['file_name'], 0, 32)] = mb_strpos($row['file_name'], '_') ? mb_substr($row['file_name'], mb_strpos($row['file_name'], '_')+1)+1 : 1; //(4)
		}

		$mysql_result = $this -> mysql_qw('DROP TABLE patterns');

		for($i = 0; $i < count($files); $i++) // 							(5)
			if( $dublicate[ $files[$i]['file_name'] ] )
				$files[$i]['file_name'] .= '_'.$dublicate[ $files[$i]['file_name'] ];

		return $files;
	}

	/*удаление нескольких файлов
	*/
	protected function delRelatedFiles($rows)
	{
		//перемещение удаляемых файлов в корзину
		$rows_id = is_array($rows['id']) ? $rows['id'] : array($rows['id']);

		$plch_id = array_fill(0, count($rows_id), '?');

		$query = 'SELECT file_name, file_extension FROM '.$this -> database_table_name.'_files WHERE parent_id IN ('.implode( ', ', $plch_id ).')';

		$mysql_result = $this -> mysql_qw($query, $rows_id);

		if($mysql_result -> num_rows)
		{
			$dir = '/files/gsp/documents';

			createDir($dir.'/delete');

			while($row = $mysql_result -> fetch_assoc())
			{
				$file_path_old = sprintf("%s/%s.%s", ROOT_PATH.$dir, $row['file_name'], $row['file_extension']);
				$file_path_new = sprintf("%s/delete/%s.%s", ROOT_PATH.$dir, $row['file_name'], $row['file_extension']);
				@rename( $file_path_old, $file_path_new );
			}
		}


		//удаление данных из БД
		$reg = new Registry;
		$reg
			-> setDatabaseTableName($this -> database_table_name.'_files')
			-> del($rows, 'parent_id');

		return $this;
	}

	/*Функция дозаписывает данные о скан-копиях в результирующий набор данных Registry
	*
	*	логика:
	*	1) собрать id текущей выборки данных Registry в массив
	*	2) по этим id запросить в БД информацию о файлах
	*	3) собрать массив, где в качестве ключа используется parent_id файла, а в качестве данных - массив массивов с файлами
	*	4) раскидать массивы с файлами по текущим записям набора данных Registry или отдать клиенту пустые массивы, но они должны быть
	*/
	protected function getRelatedFiles()
	{
		for($i = 0, $rows_id = array(); $i < count($this -> current_data); $i++) 	//(1)
			$rows_id[] = $this -> current_data[$i]['id'];

		$plch_id = array_fill(0, count($rows_id), '?');

		$query = 'SELECT id, parent_id, CONCAT(file_name, ".", file_extension) as file_name, file_title FROM '.$this -> database_table_name.'_files WHERE parent_id IN ('.implode( ', ', $plch_id ).') ORDER BY id DESC';

		$mysql_result = $this -> mysql_qw($query, $rows_id); 						//(2)
		
		$files_arr = array(); 
		if($mysql_result -> num_rows)												//(3)
		{														
			while($row = $mysql_result -> fetch_assoc()) //записи в таблице файлов для этого ПС-Акта есть
				$files_arr[$row['parent_id']][] = $row;
		}

		for($i = 0; $i < count($this -> current_data); $i++) 						//(4)
			if( $files_arr[ $this -> current_data[$i]['id'] ] )
				$this -> current_data[$i]['files'] = $files_arr[ $this -> current_data[$i]['id'] ];
			else $this -> current_data[$i]['files'] = array(); //обязательно возвращать поле files, иначе при удалении всех файлов, последний не затрётся на клиенте до перезагрузки данных
	}

	/*Функция дозаписывает данные о УПД в результирующий набор данных Registry
	*
	*	логика:
	*	1) собрать id текущей выборки данных Registry в массив
	*	2) по этим id запросить в БД информацию о файлах
	*	3) собрать массив, где в качестве ключа используется parent_id файла, а в качестве данных - массив массивов с УПД
	*	4) раскидать массивы с УПД по текущим записям набора данных Registry
	*/
	protected function getSales()
	{
		for($i = 0, $rows_id = array(); $i < count($this -> current_data); $i++) 	//(1)
			$rows_id[] = $this -> current_data[$i]['id'];

		$plch_id = array_fill(0, count($rows_id), '?');

		$query = 'SELECT 
					parent_id, 
					sale_number, 
					sale_date, 
					invoice_number, 
					invoice_date 
				FROM gsprom_sales 
				WHERE parent_id IN ('.implode( ', ', $plch_id ).') 
				ORDER BY id DESC';

		$mysql_result = $this -> mysql_qw($query, $rows_id); 						//(2)
		
		if(!$mysql_result -> num_rows) return; //записей нет

		$sales_arr = array(); 														//(3)
		while($row = $mysql_result -> fetch_assoc()) //записи в таблице УПД и счёт-фактур для этого ПС-Акта есть
			$sales_arr[$row['parent_id']][] = $row;

		
		for($i = 0; $i < count($this -> current_data); $i++) 						//(4)
			if( $sales_arr[ $this -> current_data[$i]['id'] ] )
				$this -> current_data[$i]['sales'] = $sales_arr[ $this -> current_data[$i]['id'] ];
	}
	
	
	/*Функция дозаписывает данные о счетах в результирующий набор данных Registry
	*
	*	логика:
	*	1) собрать id текущей выборки данных Registry в массив
	*	2) по этим id запросить в БД информацию о файлах
	*	3) собрать массив, где в качестве ключа используется parent_id файла, а в качестве данных - массив массивов со счетами
	*	4) раскидать массивы со счетами по текущим записям набора данных Registry
	*/
	protected function getScores()
	{
		for($i = 0, $rows_id = array(); $i < count($this -> current_data); $i++) 	//(1)
			$rows_id[] = $this -> current_data[$i]['id'];

		$plch_id = array_fill(0, count($rows_id), '?');

		$query = 'SELECT 
					parent_id, 
					score_number, 
					score_date
				FROM gsprom_scores
				WHERE parent_id IN ('.implode( ', ', $plch_id ).') 
				ORDER BY id DESC';

		$mysql_result = $this -> mysql_qw($query, $rows_id); 						//(2)
		
		if(!$mysql_result -> num_rows) return; //записей нет

		$scores_arr = array(); 														//(3)
		while($row = $mysql_result -> fetch_assoc()) //записи в таблице УПД и счёт-фактур для этого ПС-Акта есть
			$scores_arr[$row['parent_id']][] = $row;

		
		for($i = 0; $i < count($this -> current_data); $i++) 						//(4)
			if( $scores_arr[ $this -> current_data[$i]['id'] ] )
				$this -> current_data[$i]['scores'] = $scores_arr[ $this -> current_data[$i]['id'] ];
	}


	public function search($data=array(), $start=null, $limit=null)
	{
		if( !$this -> database_table_name ) die(printf("Ошибка: не задано имя таблицы БД (файл: %s, класс: %s, метод: %s", __FILE__, __CLASS__, __METHOD__));

		$q = $this -> preProcessing($data, $start, $limit);

		$mysql_result = $this -> mysql_qw( $q['query'], $q['data'] );

		$this -> setCurrentData(); //обнуление текущих данных регистра

		if(!$mysql_result) return $this;

		while($row = $mysql_result -> fetch_array(MYSQLI_ASSOC))
		{
			$this -> setCurrentData($row, 'NOT_REWRITE');
		}

		return $this;
	}

	protected function preProcessing($data = array(), $start = null, $limit = null)
	{
		$where_search = array();
		$data_search = array();

		//текстовый поиск
		$sample = (string)$data['text_search'];
		if($sample)
		{
			$sample = '%'.trim($sample).'%';

			$checkbox= false; //флаг включения любого чекбокса

				//лямбда-функция проверки состояния чекбокса
				$checkCheckbox = function($ch_name, $column) use (&$where_search, &$data_search, $sample, &$checkbox)
				{
					if(!$ch_name) return;
					$where_search[] = $column . ' LIKE ?';
					array_push($data_search, $sample);
				};

			$checkCheckbox($data['search_for_num_order'], 'number');
			//$checkCheckbox($data['search_for_automobile'], 'car');
			
			//поиск по счёту
			if($data['search_for_num_score'])
			{
				$where_search[] = 'id IN (SELECT parent_id FROM gsprom_scores SL WHERE score_number LIKE ?)';
				array_push($data_search, $sample);
			}


			if(!$checkbox)
			{
				//$where_search[] = '(number LIKE ? OR car LIKE ?)';
				//array_push($data_search, $sample, $sample);
				
				/**рабочий запрос, но... не пригодился
				*	такой запрос не отработает ситуацию когда у ПСА есть только счёт, но нет реализации.
				*	все другие ситуации отрабатывает правильно
				*
				$where_search[] = '(
					number LIKE ? OR
					car LIKE ? OR
					id IN (SELECT SL.parent_id as parent_id
							FROM gsprom_sales SL
							LEFT JOIN gsprom_scores SC
							ON SL.parent_id=SC.parent_id
								WHERE 
									SL.sale_number LIKE ? OR 
									SC.score_number LIKE ?)
					)';
				array_push($data_search, $sample, $sample, $sample, $sample, $sample);
				/**/
				
				//этот запрос отрабатывает поиск актов, с реализацией или со счетами в любых комбинациях
				$where_search[] = '(
					number LIKE ? OR 
					car LIKE ? OR
					id IN (SELECT parent_id FROM gsprom_sales SL WHERE sale_number LIKE ?) OR
					id IN (SELECT parent_id FROM gsprom_scores SL WHERE score_number LIKE ?)
					)';
				array_push($data_search, $sample, $sample, $sample, $sample);
			}
		}

		//выпадающий список
		$company = $data['search_for_company'];
		if($company)
		{
			$where_search[] = '(customer=?)';
			array_push($data_search, $company);
		}
		
		
		//фильтра
		switch($data['filter'])
		{
		  case 'status_1': $where_search[] = '(status="поступила заявка")'; break;
		  case 'status_2': $where_search[] = '(status="ремонт окончен")'; break;
		  case 'status_3': $where_search[] = '(status="выдано в ОП")'; break;
		  case 'status_4': $where_search[] = '(status="получено из ОП")'; break;
		  case 'status_5': $where_search[] = '(status="принято заказчиком")'; break;
		  case 'status_6': $where_search[] = '(status="отправлен оригинал")'; break;
		  case 'status_7': $where_search[] = '(status="требует уточнений")'; break;
		  case 'status_8': $where_search[] = '(status="получен оригинал")'; break;
		}


		if( count($where_search) ) $where = ' WHERE '. implode( ' AND ', $where_search );

		$query = 'SELECT
				id,
				number,
				sum,
				customer,
				master,
				car,
				date_document,
				status
			FROM '.$this -> database_table_name.' DOC '. $where . ' ORDER BY ' . $this -> order_by;

		if( (int)$limit ) //лимитирование и смещение
		{
			$query .= ' LIMIT '; 

			if( !is_null($start) ) $query .= (int)$start .', ';

			$query .= (int)$limit;
		}
//dump($query);
//dump($data_search);
		return array( 'query' => $query, 'data' => $data_search );
	}
	
	/*метод трансформирует результирующую выборку
	*массив вида:
		Array
		(
			[0] => Array
				(
					[id] => Array
						(
							[0] => 3
							[1] => 4
						)

					[date_create] => 1630416174000
					[status] => поступила заявка
					[comment_status] => 
				)

		)
	* преобразует в:
		Array
		(
			[0] => Array
				(
					[id] => 3
					[date_create] => 1630416174000
					[status] => поступила заявка
					[comment_status] => 
				)
			[1] => Array
				(
					[id] => 4
					[date_create] => 1630416174000
					[status] => поступила заявка
					[comment_status] => 
				)
		)
	*/
	protected function transformCurrentData()
	{
		if(count($this -> current_data) > 1) return $this;
		if(!is_array($this -> current_data[0]['id'])) return $this;

		$current = array_diff_key($this -> current_data[0], array('id'=>null)); //выкинуть из массива данные по ключу id (массив идентификаторов)

		$id_arr = $this -> current_data[0]['id'];

		$this -> setCurrentData(); //обнуление текущих данных регистра
	
		for($i = 0; $i < count($id_arr); $i++)
		{
			$current['id'] = $id_arr[$i];
			$this -> setCurrentData($current, 'NOT_REWRITE');
		}

		return $this;
	}

	public function out()
	{
		$this -> getStatusHistory(); //добавить в массив результатов данные по изменению статусов
		$this -> getRelatedFiles(); //добавить в массив результатов данные о связанных скан-копиях
		$this -> getSales(); //добавить в массив результатов данные о связанных реализациях и счёт фактурах
		$this -> getScores(); //добавить в массив результатов данные о связанных счетах
		return parent::out();
	}
}


trimer($_p);

define('DB_TABLE_NAME', 'gsprom_document');

//клиент может передавать json кодированный массив в случае если редактируется/удаляется несколько строк
$_p['form_edit']['id'] = json_decode($_p['form_edit']['id']);


//установка компаний ГСП (для пользователей ГСП)
if($user -> get('company') !== 'БОВИД')
	$_p['form_search']['search_for_company'] = $user -> get('company');


$psa = new BaseUnit();
$psa -> set(array_merge($_p['form_edit'], array('files' => $_p['files'])));

$reg = new Reg;
$reg -> setDatabaseTableName(DB_TABLE_NAME);


//print_r('backend connect');

//print_r($_p);

//json_last_error() === JSON_ERROR_NONE //' - Ошибок нет';
//print_r($psa -> getProperties());


switch($_p['tmpl_data']['event'])
{
	case 'edit_files': $reg -> editFiles($psa -> getProperties(), $user);	break;

	case 'upload_files': $reg -> uploadFiles($psa -> getProperties(), $user);	break;

	case 'del_file': $reg -> delFile($psa -> getProperties(), $user);	break;

	case 'add': $reg -> add($psa -> getProperties(), $user); break;

	case 'edit': $reg -> upd($psa -> getProperties(), $user); break;

	case 'del': $reg -> del($psa -> getProperties());	break;

	case 'load':
		$reg
			-> setOrderBy('id')
			-> search($_p['form_search'], $_p['tmpl_data']['start'], $_p['tmpl_data']['limit']);
		break;

	case 'excel':
		//ПРОТЕСТИРОВАТЬ создание файла на сервере
		//
		//...
		$data = $reg -> saveExcel($_p['form_search']);
		$path_to_excel = '/files/gsp/documents';
		$file = ROOT_PATH.$path_to_excel.'/gsp_documents.xlsx';
		createDir( $path_to_excel );
		//$writer = WriterFactory::create( Type::XLSX ); //for XLSX files
		//$writer->openToFile($file);
		//$writer->addRows( $data );
		//$writer->close();

		//$reg -> setCurrentData( array('excel' => BASE.$path_to_excel.'/gsp_documents.xlsx') );

		//$_RESULT = BASE.$path_to_excel.'/gsp_documents.xlsx';
    break;
}

$_RESULT = array(
	'main_data' => $reg -> out(),
	'meta_data' => array(
			'event' => $_p['tmpl_data']['event'],
			'start' => $_p['tmpl_data']['start'],
		),
	);
?>