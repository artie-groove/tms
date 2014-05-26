<?
	include "class.Upload.php";
	//include "index.inc.php";

	if ( isset($_FILES['data_xlsx']) )
	{
        //echo json_encode(array('status' => 'got file'));
        //exit(0);
		$loader = new Upload;
		if ( !$loader->uploadFile($_FILES['data_xlsx']) )
		{
			//Вывод сообщения об ошибке
			$error = $loader->getRezult();
			echo $error;
		}
		else
		{
			$message = $loader->getRezult();
			echo $message;
		}
	}
?>