<?
	function __autoload($class_name)
	{
		$lookupPaths = array('/', '/helpers/');
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
	
	include "interfaces.php";

?>