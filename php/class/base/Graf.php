<?
/*Класс Graf
*
*Описание:
*	создаёт и работает с древовидной структурой данных
*
*Интерфейс:
*	getGraf() - возвращает граф
*	sortBy( string $sorting_field ) - сортирует граф по полю sorting_field и возвращает его
*	
*/
class Graf
{
	protected $graf = null;
  
	protected $source_array = array();
  
	protected $sorting_field = null;

	function __construct($arr, $parent_id = 0)
	{
		if( !is_array( $arr ) ) die( 'Ошибка: аргумент функции не является массивом (Класс: '.__CLASS__.', Метод: '.__METHOD__.')' );
	
		$this -> graf = $this -> makeGraf( $arr, $parent_id );

		$this -> source_array = $arr;
	}


	function __destruct() {}


	public function each($func)
	{
		$index = 0;
		$f = function($graf, $func) use (&$f, &$index) //функция обходчик
		{
		  foreach($graf as $val)
		  {
			call_user_func($func, ++$index, $val);

			if($val['next'] !== null) call_user_func($f, $val['next'], $func);
		  }
		};

		call_user_func($f, $this -> graf, $func);
	}





	public function getChildrenById( $children_id )
	{
		$children = array();
		$parent_id;

		$this -> each(function($i, $el) use (&$children, $children_id, &$parent_id)
		{
			if($el['id'] == $children_id )
			{
				$children[] = $el;

				$parent_id = $el['parent_id'];
			}
		});

		return new Graf($children, $parent_id);
	}


	public function getGraf(){return $this -> graf;}


	public function sortBy($sorting_field)
	{
		$this -> sorting_field = $sorting_field;
		
		if(!function_exists('userSort'))
		{
			function userSort($key)
			{
				return function($a, $b) use ($key) //Используя конструкцию use мы наследуем переменную из родительской области видимости в локальную область видимости функции
				{
					if( !$a[ $key ] ) $a[ $key ] = 1000;
					if( !$b[ $key ] ) $b[ $key ] = 1000;

					return ( $a[ $key ] > $b[ $key ] ) ? 1 : -1;
				};
			}
		}
		usort( $this -> source_array, userSort( $this -> sorting_field ) );

		$this -> graf = $this -> makeGraf( $this -> source_array );

		return $this;
	}

  
  
	/*BUG REPORT
		метод собирает древовидную структуру из одномерного массива.
		в случае если передать методу уже сформированную древовидную структуру, то строка (1) может затереть данные по ключу [next], а их необходимо сохранить.
		в этом случае условие в строке (2) не сработает, а сама вложенная структура будет взята из значения исходного массива $arr
		
		Array(
			[1] => Array(
					[id] => 2
					[parent_id] => 1
					[level] => 2
					[next] => Array(
								[3] => Array(
									[id] => 4
									[parent_id] => 2
									[level] => 3
									[next] => 
									[name] => foo
									)
								)

					[name] => bar
					)
			)


		ранее условие (2) было записано так: if( $i == 'next' and $e[ $i ] ) continue;
		
		эта запись ошибочна, т.к. в результирующей переменной $graf значение next может быть равно null,
		а значение $e['next'] при этом будет существовать, поэтому условие (2) не позволяет записать вложенную структуру
	*/
	protected function makeGraf($arr, $parent_id = 0, $level = 0)
	{
		$level++;

		foreach($arr as $key => $e)
		{
			if($e['parent_id'] == $parent_id)
			{
				$graf[ $key ] = array(
									  'id' => $e[ 'id' ],
									  'parent_id' => $e[ 'parent_id' ],
									  'level' => $level,
									  'next' => $this -> makeGraf( $arr, $e[ 'id' ], $level ), //(1)
									  );


				foreach($e as $i => $v)
				{
					if($i == 'id' or $i == 'parent_id') continue; //значения под ключами id, parent_id не перезаписываем

					if($i == 'next' and $graf[ $key ]['next']) continue; //(2) значение под ключом существует не перезаписывем его (на случай если по ключу next записано свойство null)

					$graf[$key][$i] = $v;
				}
			}
		}
		return $graf ? $graf : null;
	}
}
?>