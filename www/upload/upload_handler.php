<?
	include $_SERVER['DOCUMENT_ROOT']."/app/core.php";
        
	if ( isset($_FILES['data_xlsx']) )
	{
		$loader = new Upload;
		if ( !$loader->uploadFile($_FILES['data_xlsx']) )
		{
			//Вывод сообщения об ошибке
			$errorCode = $loader->getStatusCode();
			$errorDescription = $loader->getStatusDescription();
			echo $errorCode.": ".$errorDescription;
		}
		else
		{
            $errorCode = $loader->getStatusCode();
			$errorDescription = $loader->getStatusDescription();
			echo $errorCode.": ".$errorDescription;
			
			include $_SERVER['DOCUMENT_ROOT'].'/lib/Classes/PHPExcel.php';

			$parser = new Parser();
			
			$fileToParse = $loader->getFullFileName();
			//Добавить проверку работы парсинга и обработку ситуаций 
			//когда парсинг завершился с ошибкой и когда без ошибки
            if ( $parser->parsing($fileToParse) )
            {
                $parseData = $parser->getParseData();
                $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');
                $pusher = new BD_Pusher();

                if ( $pusher->push($parseData, $parser->Type_stady) )
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