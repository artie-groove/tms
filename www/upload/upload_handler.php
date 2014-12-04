<?
	try
    {
        include $_SERVER['DOCUMENT_ROOT']."/app/bootstrap.php";

        if ( !isset($_FILES['data_xlsx']) )
        {
            respond('error', 'Ошибка приёма файла');
            exit(1);
        }

        $uploader = new XlsxFileUploader();

        if ( !$uploader->uploadFile($_FILES['data_xlsx']) )
        {
            respond_from_object($uploader);
            exit(2);
        }

        $fileToParse = $uploader->getFullFileName();

        require_once dirname(__FILE__) . '/../app/lib/PHPExcel.php';

        $parser = new Parser();

        if ( !$parser->parsing($fileToParse) )
        {
            respond_from_object($parser);
            exit(3);
        }

        $parseData = $parser->getParseData();
        $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');

        $importer = new DataImporter();
        $DisciplineMatcher = new DisciplineMatcher();

        if ( !$importer->import($parseData, $parser->Type_stady, $DisciplineMatcher) )
        {
            respond_from_object($importer);
            exit(4);
        }

        $checker = new ImportChecker($dbh);    
        if ( !$checker->check() )
        {
            respond_from_object($checker);
            exit(5);
        }

        $merger = new TableMerger($dbh);
        if ( !$merger->merge() )
        {
            respond_from_object($merger);
            exit(6);
        }

        unlink($fileToParse);

        respond_from_object($checker);
        
    }
    catch(Exception $e)
    {
        respond_from_object('error', $e->getMessage());
    }
?>