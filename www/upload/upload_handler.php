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

    $fileToParse = $uploader->getFullFileName();

    require_once dirname(__FILE__) . '/../app/lib/PHPExcel.php';

    $parser = new Parser();

    if ( !$parser->parsing($fileToParse) )
    {
        respond_from_object($parser);
        exit();
    }

    $parseData = $parser->getParseData();
    $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');

    $importer = new DataImporter();
    $DisciplineMatcher = new DisciplineMatcher();

    if ( !$importer->import($parseData, $parser->Type_stady, $DisciplineMatcher) )
    {
        respond_from_object($importer);
        exit();
    }

    $checker = new ImportChecker($dbh);    
    if ( !$checker->check() )
    {
        respond_from_object($checker);
        exit();
    }

    $merger = new TableMerger($dbh);
    if ( !$merger->merge() )
    {
        respond_from_object($merger);
        exit();
    }

    unlink($fileToParse);

    respond_from_object($checker);
?>