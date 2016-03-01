<?php

class DebugException extends Exception
{
    public function __construct($msg, $details)
    {
        if ( is_array($details) && ! empty($details) ) {
            $details = var_export($details, true);
        }
        parent::__construct($msg . ' | ' . $details);
    }
}

?>