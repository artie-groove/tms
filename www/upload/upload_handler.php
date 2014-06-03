<?
	include $_SERVER['DOCUMENT_ROOT']."/app/bootstrap.php";
        
	if ( !isset($_FILES['data_xlsx']) )
    {
        respond('error', 'Ошибка приёма файла');
        exit();
    }

    $uploader = new XlsxFileUploader();

    if ( !$uploader->uploadFile($_FILES['data_xlsx']) )
    {
        respond_from_object($uploader);
        exit();
    }

require_once dirname(__FILE__) . '/../app/lib/PHPExcel.php';

    $parser = new Parser();

    $fileToParse = $uploader->getFullFileName();

    if ( !$parser->parsing($fileToParse) )
    {
        respond_from_object($parser);
        exit();
    }

    $parseData = $parser->getParseData();
    $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');

    $importer = new DataImporter();

    if ( !$importer->import($parseData, $parser->Type_stady) )
    {
        respond_from_object($importer);
        exit();
    }

    $checker = new ImportChecker($dbh);
    $checker->check();

    unlink($fileToParse);

    respond_from_object($checker);
?>