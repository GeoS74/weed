<?/*[в разработке] класс нигде не используется*/
class File {

  private $DIR_FILES = '';

  private $DIR_DEL_FILES = '';


  function __construct() {
  }

  // ==public metgods== //
  
  public function getDirForFiles() {

    return $this -> DIR_FILES;

  }

  /*
  *записывает директории файлов в свойства $DIR_FILES и $DIR_DEL_FILES и создаёт директорию
  *
  *принимает строку
  *
  *ничего не возвращает
  */
  public function setDirForFiles( $dir ) {

    global $_STATION;
    
	/*
	*проверка аргумента функции на строковый тип
	*/
	if( !is_string( $dir ) ) die( 'File [setDirForFiles()]: Ошибка установки директории. Имя каталога задано не строкой' );
    
	/*
	*разбивает полученную строку по разделителю 
	*разделитель - НЕ символ, НЕ числа, НЕ знак подчеркивания
	*флаг PREG_SPLIT_NO_EMPTY означает оставить в массиве не пустые элементы
	*
	*$dir_list - получает не пустые элементы, содержащие только буквы и цифры
	*/
    $dir_list = preg_split("/[\W]+/", $dir, -1, PREG_SPLIT_NO_EMPTY);

    if( !count($dir_list) )  die( 'File [setDirForFiles()]: Ошибка установки директории. Имя каталога задано не корректно' );
	
	/*
	*формирует директорию
	*/
	for( $i = 0, $dir = '' ; $i < count($dir_list); $i++ ){
	
	  $dir .= '/' . $dir_list[$i];
	
	}
	
	$this -> DIR_FILES = '/files' . $dir;

	$this -> DIR_DEL_FILES = $this -> DIR_FILES . '/trash';
	
	createDir( $this -> DIR_DEL_FILES ); //создание директории хранения файлов

  }

  //работа с файлами
  public function uploadFile( $file, $hidden = '' ) { //эта функция должна возвращать относительный путь до файла или пустую строку
    global $_STATION;
	
    if ( !$file ) return false; //это условие точно должно быть здесь
    
	//проверяет значение свойства с именем директории файлов. Если директория не указана, устанавливает её используя имя таблицы БД, с которой работает модуль или установит 'default'
	if( !$this -> DIR_FILES ) $this -> setDirForFiles( $_STATION -> TABLE_NAME );

	if ( $file[ 'error' ] != 0 ) { //файл загружен с ошибками
	  return $hidden;  
	}
	 
    $fileName = md5( mt_rand() ) . translit( $file[ 'name' ] );
	$fileTempDir = $file[ 'tmp_name' ]; //адрес временного хранения
	$fileType = $file[ 'type' ]; // тип файла
	$fileDir = $this -> DIR_FILES . '/' . $fileName;
	

	/* защита от двойного копирования файлов
	старый вариант:
	if ( BASE . $fileDir == $hidden ) return $hidden;
	*/
	if ( $hidden ) {
	  $f = explode( '/', $hidden );
	  $fName = $f[ count( $f ) - 1 ]; //последний элемент массива это имя файла
	
	  $hiddenAbsDir = ROOT_PATH . $this -> DIR_FILES . '/' . $fName;
	  $ident = $this -> compareHashFiles( $fileTempDir, $hiddenAbsDir ); //[bool] содержит результат сравнения md5 хешей двух файлов

      if( $ident ) return $hidden; //если загружается тот же самый файл
	  else $this -> deleteFile( $hidden ); //если загружается новый файл - старый файл нужно переместить в папку trash
	}
	 
 
	//перемещение файла
	$destination = ROOT_PATH . '/' . $fileDir; //место назначения. Полный путь + имя файла + расширение

	//Функция preg_match сравнивает регулярное выражение с МИМЕ файла
	//здесь есть ошибка
	//if(preg_match('{application/(.*)}is', $fileType)) $foo = move_uploaded_file($fileTempDir, $destination);
	move_uploaded_file($fileTempDir, $destination);
	
	return BASE . $fileDir;
  }
  
  public function deleteFile( $file ) {
    global $_STATION;
	
    if ( !$file ) return false; //это условие точно должно быть здесь
	
	//проверяет значение свойства с именем директории файлов. Если директория не указана, устанавливает её используя имя таблицы БД, с которой работает модуль или установит 'default'
	if( !$this -> DIR_FILES ) $this -> setDirForFiles( $_STATION -> TABLE_NAME );
	
    $f = explode( '/', $file );
	$fName = $f[ count( $f ) - 1 ]; //последний элемент массива это имя файла
 
	$source = ROOT_PATH . $this -> DIR_FILES . '/' . $fName;
	$dest = ROOT_PATH . $this -> DIR_DEL_FILES . '/' . $fName;

    /*Два способа переместить файл
	  Первый длиннее. Поэтому выбран второй вариант.
	  copy( $source, $dest );
	  unlink( $source );
	  */
	//если файла не существует выводится сообщение об ошибке. Это сообщение надо гасить...
	return @rename( $source, $dest ); //перемещает старый файл в папку trash  
  }
  
  // ==private methods== //
  
  private function compareHashFiles( $f1, $f2) {
    return ( md5( @file_get_contents( $f1 ) ) == md5( @file_get_contents( $f2 ) ) );
  }

}
?>