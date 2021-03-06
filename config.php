<?
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
define('BASE', '');
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');
define('REGISTRATE_MODE', false);
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////


define('DB_CHARSET', 'utf8'); //в функции set_charset нельзя передавать кодировку с '-' (utf-8). Почитать: https://www.php.net/manual/ru/mysqlinfo.concepts.charset.php
define('CHARSET', 'utf-8');

//добавить эту константу
//define('BASE', $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].BASE_DIR);

/*
*корневая папка
*ABS_PATH и ROOT_PATH в принципе одно и тоже
*разница заключается в контексте подключения файла config.php
*если подключает главный контроллер, то используется ABS_PATH
*если подключает backend-AJAX, то используется ROOT_PATH. Т.к. в этом случае ABS_PATH будет содержать путь до папки с backend-AJAX файлом

BUG REPORT
  если подключать в шаблоне другие файлы .htm, то использование константы ABS_PATH приведёт к ошибке
  причина в том, что ABS_PATH содержи обратные слеши '\', а ROOT_PATH - прямые '/'
  в файлах шаблонах следует использовать ROOT_PATH
*/
define('ABS_PATH', dirname(__FILE__));
define('ROOT_PATH', getenv('DOCUMENT_ROOT').BASE);

//define('LOG_SALT', '$2a$10$mysaltdeer1reessddeerb$'); //это статичная соль. Её не надо использовать

//устанавливает кодировку в серверных настройках
ini_set('default_charset', CHARSET);

//путь к подключаемым файлам устанавливается в файле index.php

//session https://www.php.net/manual/ru/session.configuration.php
ini_set('session.use_cookies', true);
ini_set('session.use_only_cookies', true);
ini_set('session.use_strict_mode', true);
ini_set('session.cookie_httponly', true);
ini_set('session.use_trans_sid', false);

//errors
//ini_set('error_reporting', E_ALL );
//ini_set('error_reporting', E_ALL & ~E_NOTICE);

//REGEXP
define('REGEXP_DIGIT', '/([+-])\D*|(\d+)|[\.\,]+.*/');
define('REGEXP_USER_NAME', '/[^a-zA-Z0-9-_+\s]/');
define('REGEXP_USER_PASS', '/[^a-zA-Z0-9-_*?#~%]/');
define('REGEXP_USER_EMAIL', '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,4}/');

date_default_timezone_set('UTC'); //Устанавливает временную зону по умолчанию для всех функций даты/времени в скрипте

/*принудительно указываем внутреннюю кодировку скрипта
*
*здесь и во всех backend скриптах устанавливается внутренняя кодировка скрипта
*сделано это скорее из-за паранои, т.к. на данный момент я не знаю ни одного бага в системе, возникшего из-за отсутствия этой команды
*если НЕ выставить UTF-8, то будет ISO-8859-1
*поэтому для единообразия кодировку можно изменить
*
*Единственный момент, это использование функции strlen.
*Если строка из кириллицы, то strlen отработает не правильно. Если применить mb_strlen( $str ) - результат тоже будет не правильный, т.к. будет использована внутренняя кодировка
*Чтобы результат был правильный нужно использовать так: mb_strlen( $str, CHARSET )
*Но, чтобы не указывать кодировку при вызове mb_strlen можно изменить внутреннюю кодировку скрипта вручную, тогда вызывать можно так: mb_strlen( $str )
*/
mb_internal_encoding( CHARSET );
?>