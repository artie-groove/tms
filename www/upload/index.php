<?
	include "class.Upload.php";
	include "index.inc.php";

	if ( isset($_FILES['data_xls']) )
	{
		$loader = new Upload;
		if ( !$loader->uploadFile($_FILES['data_xls']) )
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