<?
    include "config.php";
    include "interfaces.php";

	function __autoload($class_name)
	{
		$lookupPaths = array('/', '/core/', '/common/', '/helpers/', '/entities/');
		foreach ( $lookupPaths as $subpath )
		{
			$classFileName = $_SERVER['DOCUMENT_ROOT'] . '/app' . $subpath . $class_name . '.php';
			if ( file_exists($classFileName) )
			{
				require_once $classFileName;
				return;
			}
		}
	}

    function respond($status_code, $description, $details = '')
    {
        $response = array(
            'status' => $status_code,
            'description' => $description,
            'details' => $details
        );
        echo json_encode($response);
    }

    function respond_from_object(IStatus $obj)
    {
        $status_code = $obj->getStatusCode();
        $description = $obj->getStatusDescription();
        $details = $obj->getStatusDetails();
        respond($status_code, $description, $details);
    }

    // Setting up PDO
    try {
        $dbh = new PDO($dsn, $dbuser, $dbpass);
    } catch ( PDOException $e ) {
        respond('error', 'Ошибка подключения к базе данных',  $e->getMessage());
        exit();
    }

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	//$dbh->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8');
	
	$dbh->exec('SET NAMES utf8');
	//$dbh->exec('SET NAMES utf8 COLLATE');

?>