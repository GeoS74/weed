<?
/*����� DatabaseInteraction
*
*��������:
*	��������� ������� ��������� ������ � �� � ��������� ���������� ����������� �������
*
*���������:
*
*	setDatabaseTableName( string $database_table_name ) - ������������� ��� ������� ��
*	setEditMode( string $edit_mode ) -     ��� ��������� �������� USE_ALL_DATA_FIELDS
*										   ���� ���������� ������� ������� ������ ��� � ������� ��
*									       ����������� ���� ����� ������� � ������� ��
*	setUpdateMode( string $update_mode ) - ��� ��������� �������� ONLY_EXISTING
*										   �������������� ������ �� ����, ������� ����� ��������
*   load( [string $start], [string $limit] )
*	add()
*	upd()
*	del()
*
*/
abstract class DatabaseInteraction  extends DataBase
{
  protected $database_table_name;
  protected $edit_mode;    //USE_ALL_DATA_FIELDS
  protected $update_mode;  //ONLY_EXISTING
  
  protected $field_names = array();
  
  
  //setters
  public function setEditMode( $edit_mode )
  {
    $this -> edit_mode = (string)$edit_mode;
	return $this;
  }

  public function setUpdateMode( $update_mode )
  {
    $this -> update_mode = (string)$update_mode;
	return $this;
  }

  public function setDatabaseTableName( $database_table_name )
  {
    $this -> database_table_name = (string)$database_table_name;
	return $this;
  }
  
  //abstract methods
  abstract protected function load( $start = null, $limit = null );
  abstract protected function add();
  abstract protected function upd();
  abstract protected function del();

  //protected
  protected function getFieldNames()
  {
    $mysql_result = $this -> mysql_qw( 'SHOW COLUMNS FROM '.$this -> database_table_name );

	while( $row = $mysql_result -> fetch_array( MYSQLI_ASSOC ) )
	{
	  $this -> field_names[] = $row['Field'];
	}
	return $this -> field_names;
  }

  protected function getTargetFields( $data )
  {
    if( $this -> edit_mode == 'USE_ALL_DATA_FIELDS' )
	{
	  $foo = array_diff_ukey( $data, array_flip($this -> field_names),
		  function( $key_1, $key_2 )
		  {
			return $key_1 != $key_2;
		  });

	  if( count($foo) )
	  {
	    $this -> addFieldsToTable( $this -> database_table_name, array_keys($foo) ); //��������� ������� � ������� ��
	    $this -> getFieldNames();
	  }
	}
	return array_intersect_key( $data, array_flip($this -> field_names) );
  }
}
?>