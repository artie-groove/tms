<?
class Handler
{
	protected $status = NULL;
	
	public function setStatus($code, $description, $details=0)
	{
		$this->status = array('Code' => $code, 'Description' => $description, 'Details' => $details);
	}
}
?>