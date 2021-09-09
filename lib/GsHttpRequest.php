<?
class GsHttpRequest
{
	function __construct()
	{
		ob_start();
	}

	function __destruct()
	{
		$buffer = ob_get_contents();
		ob_end_clean();

		global $_RESULT;

		echo json_encode( array('buffer' => $buffer, 'result' => $_RESULT) );
	}
}
?>