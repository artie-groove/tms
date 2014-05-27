<?
	include "class.Upload.php";

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
			
			include $_SERVER['DOCUMENT_ROOT'].'/lib/Classes/PHPExcel.php';
			include $_SERVER['DOCUMENT_ROOT']."/app/helpers/Pair.php";
			include $_SERVER['DOCUMENT_ROOT']."/app/helpers/Parser.php";
			$parser = new Parser();
			
			$fileToParse = $loader->getFullFileName();
			//Добавить проверку работы парсинга и обработку ситуаций 
			//когда парсинг завершился с ошибкой и когда без ошибки
			$parseData=$parser->parsing($fileToParse);
			var_dump($parseData[0]);
			
		}
	}
?>