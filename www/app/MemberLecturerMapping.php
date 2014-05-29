<?
class MemberLecturerMapping
{
	public $tms_post;
	
	public function __construct(PDO $dbh)
	{
		//
	}
	
	public function sync($force = false)
	{
		$this->tms_post = array('ASSISTANT' => 'Ассистент', 'SENIOR_LECTURER' => 'Старший преподаватель', 'DOCENT' => 'Доцент', 'PROFESSOR' => 'Профессор');
		$total = count($this->tms_post)-1;
		$counter = 0;
		//--- формирует строку запроса: ---------------------------------------------------------------
		//--- post = Ассистент or post = Старший преподаватель or post = Доцент or post = Профессор ---
		foreach ($this->tms_post as $key => $value)
		{
			if ($counter == $total)
			{
				$sql_post .= " post = ".$value;
			}
			else
			{
				$sql_post .= " post = ".$value." or ";
			}
			$counter++;
		}
		//---------------------------------------------------------------------------------------------
	}
}