<?php

class MemberLecturerMapper
{
	public $tms_post;
	
	public function __construct(PDO $dbh)
	{
		//
	}
	
	public function sync($force = false)
	{
		$this->tms_post = array('ASSISTANT' => '���������', 'SENIOR_LECTURER' => '������� �������������', 'DOCENT' => '������', 'PROFESSOR' => '���������');
		$total = count($this->tms_post)-1;
		$counter = 0;
		//--- ��������� ������ �������: ---------------------------------------------------------------
		//--- post = ��������� or post = ������� ������������� or post = ������ or post = ��������� ---
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