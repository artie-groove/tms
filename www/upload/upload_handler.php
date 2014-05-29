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
            include $_SERVER['DOCUMENT_ROOT']."/app/helpers/BD_Pusher.php";
			$parser = new Parser();
			
			$fileToParse = $loader->getFullFileName();
			//Добавить проверку работы парсинга и обработку ситуаций 
			//когда парсинг завершился с ошибкой и когда без ошибки
            if ( $parser->parsing($fileToParse) )
            {
                $parseData = $parser->getParseData();
                $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');
                $pusher = new BD_Pusher();
                if ( $pusher->push($parseData,$parser->Type_stady) )
                {
                    $status = array('status' => 'ok', 'details' => 'Запись в базу произведена успешно');
                }
                else $status = array('status' => 'error', 'details' => 'Ошибка записи в базу данных 1');
            }
            else
            {
                $status = array('status' => 'error', 'details' => 'Ошибка распознавания данных 2');
            }
            unlink($fileToParse);
            echo json_encode($status);
		}
	}
?>