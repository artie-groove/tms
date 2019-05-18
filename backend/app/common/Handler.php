<?php

class Handler
{
	protected $status = NULL;
	
	public function setStatus($code, $description, $details = '')
	{
		$this->status = array('code' => $code, 'description' => $description, 'details' => $details);
	}

    public function getStatusCode()
    {
        return $this->status['code'];
    }

    public function getStatusDescription()
    {
        return $this->status['description'];
    }

    public function getStatusDetails()
    {
        return $this->status['details'];
    }
}
?>