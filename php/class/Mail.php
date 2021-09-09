<?
class Mail extends BaseUnit
{
  protected $to = array();
  protected $subject = '';
  protected $message = '';
  protected $from = '';
  
  public function getTo()
  {
    return $this -> to;
  }
  
  //функция отправки email
  public function send()
  {
	//отправка всем сразу
	mail(
	    implode(', ', $this -> to),
	    $this -> subject,
	    $this -> message,
		"From: ".$this -> from
	  );
	
	/*отправка перебором
    for( $i = 0; $i < count($this -> to); $i++ )
	{
      mail(
	    $this -> to[$i],
	    $this -> subject,
	    $this -> message,
		"From: ".$this -> from
	  );
	}
	*/
	return $this;
  }
  
  //функция очистки свойств объекта
  public function clear()
  {
	$this -> to = array();
	$this -> subject = '';
	$this -> message = '';
	$this -> from = '';
	return $this;
  }
  
  //setters
  public function setTo( $to )
  {
	trimer($to);

    if( is_string($to) )
	{
	  if( !in_array($to, $this -> to) ) $this -> to[] = $to;
	  return $this;
	}
	
	if( is_array($to) )
	{
	  foreach( $to as $val )
	  {
		  if( !$val ) continue;
		  if( !in_array($val, $this -> to) ) $this -> to[] = $val;
	  }
	}
	return $this;
  }

  public function setSubject( $subject )
  {
    $this -> subject .= (string)$subject;
	return $this;
  }
  
  public function setMessage( $message )
  {
    $this -> message .= (string)$message;
    return $this;
  }
  
  public function setFrom( $from )
  {
	trimer($from);

    $this -> from = (string)$from;
    return $this;
  }
}
?>