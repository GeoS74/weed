<?
class Log
{
  protected static $path;
  protected static $f_name;
  protected static $report;
  protected static $fp;
  
  public static function write( $path )
  {
	$args = func_get_args();

	self::$path = $path;

	if( !self::$f_name ) self::setFileName();

	self::$report = date('Y-m-d H:i:s ');
	self::$report .= is_array( $args[1] ) ? implode( ' ', $args[1] ) : implode( ' ', array_slice( $args, 1 ) );
	self::$report .= "\r\n";

	createDir( self::$path );
    self::$fp = fopen( ABS_PATH . self::$path .'/' . self::$f_name, 'a+t' );
	fwrite( self::$fp, self::$report );
	fclose( self::$fp );
	//return call_user_func_array( "sprintf", $args );
  }
  
  /*
  protected static function makeReport()
  {
  }
  */

  public static function setFileName( $f_name='' )
  {
	if( $f_name )
	{
      self::$f_name = $f_name.'.txt';
	  return;
	}

	self::$f_name = date('Y-m-d').'.txt';
  }
}
?>