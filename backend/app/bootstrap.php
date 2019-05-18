<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');

    function exception_handler(Throwable $e)
    {
        $msg = $e->getFile() . ':' . $e->getLine() . '<br>';
        $msg .= '<strong>' . $e->getMessage() . '</strong><br><br><br>';
        
        foreach ( $e->getTrace() as $trace )
        {
            if ( empty($trace['line']) ) $trace['line'] = 'anonymous function';
            if ( empty($trace['file']) ) $trace['file'] = 'anonymous function';
            $msg .= $trace['file'] . ':' . $trace['line'] . '<br>';
            $args = array();
            if ( $trace['args'] ) {
                foreach ( $trace['args'] as $argument )
                    $args[] = ( is_numeric($argument) || is_string($argument) ) ? $argument : gettype($argument);
            }
            $msg .= $trace['function'] . '(' . implode(',', $args) . ')' . '<br><br>';
        }
   
        respond('error', "Ба-бац!", $msg);
    }

    set_exception_handler("exception_handler");
    mb_internal_encoding("UTF-8");
    mb_regex_encoding("UTF-8");
    date_default_timezone_set("Europe/Volgograd");
    // setlocale(LC_ALL, 'en_GB');

    include "config.php";
    include "interfaces.php";

	spl_autoload_register( function ($class_name) 
    {
		$lookupPaths = array('/', '/core/', '/core/Parser/', '/common/', '/helpers/', '/entities/', '/lib/');
		foreach ( $lookupPaths as $subpath )
		{
			$classFileName = $_SERVER['DOCUMENT_ROOT'] . '/app' . $subpath . $class_name . '.php';
			if ( file_exists($classFileName) )
			{
				require_once $classFileName;
				return;
			}
		}
	});

    function respond($status_code, $description, $details = '')
    {
        $expectedEncoding = 'CP1251';
        $details = mb_check_encoding($details, $expectedEncoding)
            ? iconv($expectedEncoding, 'UTF-8', $details)
            : utf8_encode($details);
     
        $responseData = array(
            'status' => $status_code,
            'description' => $description,
            'details' => $details
        );

        $response = json_encode($responseData, JSON_HEX_TAG);
        
        if ( ! $response )
            respond('error', 'Failed to encode JSON response');
        
        else
            echo $response;
        
        exit();
    }

    function respond_from_object(IStatus $obj)
    {
        $status_code = $obj->getStatusCode();
        $description = $obj->getStatusDescription();
        $details = $obj->getStatusDetails();
        respond($status_code, $description, $details);
    }

    // Setting up PDO
    try
    {
        $dbh = new PDO($dsn, $dbuser, $dbpass);
    }
    catch ( PDOException $e )
    {
        respond('error', 'Ошибка подключения к базе данных', $e->getMessage());
        exit();
    }

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	//$dbh->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
	
	$dbh->exec('SET NAMES utf8');
   
?>