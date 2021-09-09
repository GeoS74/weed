<?
class MyIterator implements Iterator
{
    private $var = array();

    public function __construct($array)
    {
        if (is_array($array)) {
            $this->var = $array;
        }
    }

    public function rewind()
    {
        //echo "��������� � ������\n";
        reset($this->var);
    }
  
    public function current()
    {
        $var = current($this->var);
        //echo "�������: $var\n";
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->var);
        //echo "����: $var\n";
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->var);
        //echo "���������: $var\n";
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        //echo "������: $var\n";
        return $var;
    }
}
?>