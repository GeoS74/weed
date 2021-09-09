<?
class FTPReader
{
  private $connect_id; //тип - Resource
  private $login_result; //тип - bool
  
  protected $errors = array();

  public function getErrors()
  {
	if( count($this -> errors) ) return $this -> errors;
	else false;
  }
  
  protected function setErrors( $error )
  {
	$this -> errors[] = $error;
  }

  protected function downloadFile($file) //скачивает файл с сервера по FTP, возвращает путь до скачанного файла
  {
	if( !$this -> connect_id ) die("Отсутствует подключение к FTP-серверу class: FTPReader method: downloadFile()");

	$download_path = "/files/eis_download";

	createDir($download_path);


	/*чтобы избежать конфликта имен временных файлов, в случае когда классы наследуемые от FTPReader одновременно производят чтение
	в качестве имени временного файла используется имя вызывающего класса.
	К примеру, одновременное чтение могут выполнять классы EISReader и NSIReader
	Чтобы получить имя вызывающего класса надо прокинуть $this, иначе вернёт имя: FTPReader
	
	старая реализация: $temp_zip = ABS_PATH.$download_path."/temp_.zip";
	
	UPDATE: добавил генерацию случайных чисел mt_rand(), теперь можно без get_class($this), но так красивее ;-)
	*/
	$temp_zip = ABS_PATH.$download_path."/temp_".get_class($this).mt_rand().".zip";


	/*BUG REPORT
	*	при запуске скрипта с помощью crontab ftp_get не отрабатывает в режиме FTP_ASCII
	*	скорее всего дело в настройках сервера beget, т.к. denwer-у без разницы какой режим используется
	*/

	/*BUG DETECTED - в случае ошибки чтения надо возвращать 0
	*	иначе метод чтения zip-архива вылетает с ошибкой (см. где вызывается метод в производных классах)
	*/
	
	//echo 'FTP_TIMEOUT_SEC: '.ftp_get_option($this -> connect_id, FTP_TIMEOUT_SEC);

	if(ftp_get($this -> connect_id, $temp_zip, $file, FTP_BINARY) === true)
	{
		$this -> bad_opening_binary_mode = 0;
		return $temp_zip;
	}
	else
	{
		$this -> bad_opening_binary_mode++;
		
		Log::write("/log/eis_reader/error", "ошибка скачивания файла ".$file." \nвременное имя: ".$temp_zip);
		
		
		$this -> disconnect();
		sleep(3);
		$this -> connect($this -> ftp_server, $this -> ftp_user_name, $this -> ftp_user_pass);
		
		//Log::write("/log/eis_reader/error", "повторная попытка чтения");
		//return $this -> bad_opening_binary_mode < 50 ? $this -> downloadFile($file) : 0;
		
		if($this -> bad_opening_binary_mode < 5)
		{
			Log::write("/log/eis_reader/error", "повторная попытка чтения №".$this -> bad_opening_binary_mode);
			return $this -> downloadFile($file);
		}
		else
		{
			Log::write("/log/eis_reader/error", "первышен лимит попыток чтения. Архив не загружен.\n".$file);
			return 0;
		}
		
		
		return 0;
	}
	
	//return ftp_get($this -> connect_id, $temp_zip, $file, FTP_BINARY) === true ? $temp_zip : 0;
  }

  protected function getFileLastMod($file_name) //возвращает время последней модификации файла
  {
	if( !$this -> connect_id ) die("Отсутствует подключение к FTP-серверу class: FTPReader method: getFileLastMod()");
	return ftp_mdtm($this -> connect_id, $file_name);
  }

  protected function getFileSize($file_name) //возвращает размер файла или папки, причём папки имеют размер "-1"
  {
	if( !$this -> connect_id ) die("Отсутствует подключение к FTP-серверу class: FTPReader method: getFileSize()");
	return ftp_size($this -> connect_id, $file_name);
  }

  protected function readDirectory($dir) //возвращает список файлов и папок из $dir в виде массива
  {
	if( !$this -> connect_id ) die("Отсутствует подключение к FTP-серверу class: FTPReader method: readDirectory()");
	return ftp_nlist($this -> connect_id, $dir);
  }

  protected function connect($ftp_server, $ftp_user_name, $ftp_user_pass) //открытие соединения
  {
	$this -> connect_id = ftp_connect($ftp_server);

	$this -> login_result = ftp_login($this -> connect_id, $ftp_user_name, $ftp_user_pass);

	// проверка соединения
    if((!$this -> connect_id) || (!$this -> login_result))
	  die("Ошибка подключения к FTP-серверу class: FTPReader Не удалось установить соединение");
	
	ftp_pasv($this -> connect_id, true); //пассивный режим
	
	/*провалилась попытка исправить ошибку <b>Warning</b>:  ftp_get(): Opening BINARY mode data connection for
	*	https://www.php.net/manual/en/function.ftp-set-option.php
	*	эту ошибку вызывает return в методе downloadFile
	*/
	//ftp_set_option($connect_id, FTP_TIMEOUT_SEC, 900);
	
	return $this;
  }

  protected function disconnect() //закрытие соединения
  {
	if($this -> connect_id)
	{
	  ftp_close($this -> connect_id);
	  $this -> login_result = null;
	  $this -> connect_id = null;
	}
	return $this;
  }

  protected function getSystemType() //получение типа системы
  {
	if(!$this -> connect_id) die("Отсутствует подключение к FTP-серверу class: FTPReader method: getSystemType()");

	return ($type = ftp_systype($this -> connect_id)) ? $type : false;
  }
}
?>