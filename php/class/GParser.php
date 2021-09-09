<?
/*Класс GParser
*
*Описание:
*	Класс GParser предназначен для парсинга строк
*
*Интерфейс:
*	getParse - основной метод для парсинга
*/
class GParser
{
  private $pattern = '/[a-zA-Z1-9][a-zA-Z0-9]*/';
  private $uaz_pattern = '/[a-zA-Z0-9]+/';

  private $character_map = array( //таблица соответствий символов

	  'а'=>'a',  'к'=>'k',  'ф'=>'f',  'А'=>'A',  'К'=>'K',  'Ф'=>'F',
	  'б'=>'s',  'л'=>'l',  'х'=>'x',  'Б'=>'S',  'Л'=>'L',  'Х'=>'X',
	  'в'=>'b',  'м'=>'m',  'ц'=>'v',  'В'=>'B',  'М'=>'M',  'Ц'=>'V',
	  'г'=>'g',  'н'=>'h',  'ы'=>'r',  'Г'=>'G',  'Н'=>'H',  'Ы'=>'R',
	  'д'=>'d',  'о'=>'o',  'э'=>'eu', 'Д'=>'D',  'О'=>'O',  'Э'=>'Eu',
	  'е'=>'e',  'п'=>'n',             'Е'=>'E',  'П'=>'N',
	  'ж'=>'j',  'р'=>'p',             'Ж'=>'J',  'Р'=>'P',
	  'з'=>'z',  'с'=>'c',             'З'=>'Z',  'С'=>'C',
	  'и'=>'i',  'т'=>'t',             'И'=>'I',  'Т'=>'T',
	  'й'=>'y',  'у'=>'u',             'Й'=>'Y',  'У'=>'U', 

      'ё'=>'yo',  'Ё'=>'Yo',   
	  'ч'=>'w',   'Ч'=>'W',  
	  'ш'=>'sh',  'Ш'=>'Sh',  
	  'щ'=>'sch', 'Щ'=>'Sch', 
	  'ъ'=>'q',   'Ъ'=>'Q',    
	  'ь'=>'qu',  'Ь'=>'Qu',  
	  'ю'=>'yu',  'Ю'=>'Yu',  
	  'я'=>'ya',  'Я'=>'Ya', 
      );

	 
  /*старый вариант транслитерации символов
  private $character_map = array( //таблица соответствий символов

	  'а'=>'a',  'к'=>'k',  'ф'=>'f',  'А'=>'A',  'К'=>'K',  'Ф'=>'F',
	  'б'=>'b',  'л'=>'l',  'х'=>'x',  'Б'=>'B',  'Л'=>'L',  'Х'=>'X',
	  'в'=>'v',  'м'=>'m',  'ц'=>'c',  'В'=>'V',  'М'=>'M',  'Ц'=>'C',
	  'г'=>'g',  'н'=>'n',  'ы'=>'y',  'Г'=>'G',  'Н'=>'N',  'Ы'=>'Y',
	  'д'=>'d',  'о'=>'o',  'э'=>'ey', 'Д'=>'D',  'О'=>'O', 'Э'=>'Ey',
	  'е'=>'e',  'п'=>'p',             'Е'=>'E',  'П'=>'P',
	  'ж'=>'zh', 'р'=>'r',             'Ж'=>'Zh', 'Р'=>'R',
	  'з'=>'z',  'с'=>'s',             'З'=>'Z',  'С'=>'S',
	  'и'=>'i',  'т'=>'t',             'И'=>'I',  'Т'=>'T',
	  'й'=>'j',  'у'=>'u',             'Й'=>'J',  'У'=>'U', 

      'ё'=>'yo', 'Ё'=>'Yo',   
	  'ч'=>'ch', 'Ч'=>'Ch',  
	  'ш'=>'sh', 'Ш'=>'Sh',  
	  'щ'=>'shh','Щ'=>'Shh', 
	  'ъ'=>'yj', 'Ъ'=>'Yj',    
	  'ь'=>'uy', 'Ь'=>'Uy',  
	  'ю'=>'yu', 'Ю'=>'Yu',  
	  'я'=>'ya', 'Я'=>'Ya', 
      );
  */

  
  /*методы для альтернативного сравнения артикулов*/
  public function setMainArticle( $article )
  {
	if(!$article) $this -> main_article = array();
	else $this -> main_article = explode(' ', $this -> getParse($article));
	//print_r($this -> main_article);
  }
  
  public function setCurrArticle( $article )
  {
	if(!$article) $this -> curr_article = array();
	else $this -> curr_article = explode(' ', $this -> getParse($article));
	//print_r($this -> curr_article);
  }
  
  public function compareArticle()
  {
	  if( !count($this -> main_article) or !count($this -> curr_article) ) return false;
	  if( count($this -> main_article) != count($this -> curr_article) ) return false;

	  //print_r('compareArticle');
	  
      for($i = 0; $i < count($this -> main_article); $i++)
	  {
        if( array_search($this -> main_article[$i], $this -> curr_article) === false )
		{
			return false;
			break;
		}
	  }
	  
	  for($i = 0; $i < count($this -> curr_article); $i++)
	  {
        if( array_search($this -> curr_article[$i], $this -> main_article) === false )
		{
			return false;
			break;
		}
	  }
	  
	  
	  return true;
  }
  /**/
  
  
  
  
  
  
  /*парсит строку
  *
  *Описание:
  *		string getParse( string $str, [string $mode] )
  *
  *		возвращает строку (string) разбитую на блоки по регулярке с символами в нижнем регистре
  *		либо пустую строку
  *		блоки разделены пробелом
  *
  *Список аргументов:
  *		str - строка, которую нужно разбить
  *		mode - режим разбора строки
  *			   если указан 'COMPLETE_PARSE' при разборе блоки добиваются нулями до длины не менее 4-х символов
  *			   по умолчанию выключен, строки разбиваются на блоки только по регулярке без контроля длины блока
  */
  public function getParse( $str, $mode='' )
  {
    if( !is_string( $str ) ) return ''; //проверка на строку

	$str = mb_strtolower( trim( $str ) ); //привести строку к нижнему регистру и обрезать пробелы

	if( $mode == 'UAZ_NUMBER_PARSE' )
	{
	  $pattern = $this -> uaz_pattern;
	}
	else $pattern = $this -> pattern;
	
    preg_match_all( $pattern, $this -> translit( $str ), $out, PREG_PATTERN_ORDER ); //разобрать по блокам

	if( $mode )
	{
	  for( $i = 0; $i < count( $out[0] ); $i++ )
	  {
	    if( $mode == 'COMPLETE_PARSE' )
	    {
          if( strlen($out[0][$i]) < 4) $out[0][$i] = str_pad( $out[0][$i], 4, '0' ); //если блок меньше 4-х символов добить нулями
        }
		elseif( $mode == 'UAZ_NUMBER_PARSE' )
		{
		  if( strlen($out[0][$i]) < 4) //если блок меньше 4-х символов добить нулями
		  {
		    $out[0][$i] = str_pad( $out[0][$i], 4, '0' );
			continue;
		  }

		  if( strlen($out[0][$i]) < 15 ) continue; //не брать блок меньше 15 символов
		  preg_match_all ("/\d/", $out[0][$i], $blocks, PREG_PATTERN_ORDER); // PREG_PATTERN_ORDER  / PREG_SET_ORDER
	      if( count($blocks[0]) != 15 ) continue; //массив должен состоять из 15 элементов

	      $head =    implode('', array_slice($blocks[0], 0, 4) ); //head
	      $prefix =  implode('', array_slice($blocks[0], 4, 2) ); //prefix
	      $body =    implode('', array_slice($blocks[0], 6, 7) ); //body
	      $postfix = implode('', array_slice($blocks[0], 13, 2) );//postfix
		  
		  //префикс и постфикс добить нулями до 4-х символов
		  $prefix = str_pad( $prefix, 4, '0' );
		  $postfix = str_pad( $postfix, 4, '0' );
		  
		  $result = array();
		  $result[] = $head;
		  $result[] = $prefix;
		  $result[] = $body;
		  $result[] = $postfix;

		  $out[0][$i] = implode(' ', $result);
		}
		elseif( $mode == 'LADA_NUMBER_PARSE' )
		{
		  print_r('LADA_NUMBER_PARSE');
		  if( strlen($out[0][$i]) < 4) //если блок меньше 4-х символов добить нулями
		  {
		    $out[0][$i] = str_pad( $out[0][$i], 4, '0' );
			continue;
		  }

		  if( strlen($out[0][$i] < 14) ) continue; //не брать блок меньше 14 символов
		  preg_match_all ("/\d/", $out[0][$i], $blocks, PREG_PATTERN_ORDER); // PREG_PATTERN_ORDER  / PREG_SET_ORDER
	      if( count($blocks[0]) != 14 ) continue; //массив должен состоять из 14 элементов

	      $head =    implode('', array_slice($blocks[0], 0, 5) ); //head
	      $body =    implode('', array_slice($blocks[0], 5, 7) ); //body
	      $postfix = implode('', array_slice($blocks[0], 12, 2) );//postfix
		  
		  //префикс и постфикс добить нулями до 4-х символов
		  $postfix = str_pad( $postfix, 4, '0' );
		  
		  $result = array();
		  $result[] = $head;
		  $result[] = $body;
		  $result[] = $postfix;

		  $out[0][$i] = implode(' ', $result);
		}
      }
	}

	return implode(' ', $out[0]); //склеить в строку
  }

  private function translit( $str ){ return strtr( $str, $this -> character_map ); }

  private function reTranslit( $str ){ return strtr( $str, array_flip( $this -> character_map ) ); }
}
?>