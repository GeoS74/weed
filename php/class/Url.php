<?/**
*	v.2.0	31/05/2021
*/
class Url
{
	private $root;
	private $path;
	
	function __construct()
	{
		$this -> setRoot();
		$this -> setPath();
	}
	
	public function getRoot()
	{
		return $this -> root;
	}
	
	public function getPath($num = null)
	{
		return is_null($num) ? $this -> path : $this -> path[$num];
	}
	
	private function setRoot()
	{
		$this -> root = mb_substr($_SERVER['PHP_SELF'], 0, mb_strlen($_SERVER['PHP_SELF'])-10);
	}
	
	private function setPath()
	{
		$this -> path = explode('/', mb_substr(parse_url($_SERVER['REQUEST_URI'])['path'], mb_strlen($this -> root)+1));
	}
}
?>