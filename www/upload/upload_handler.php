<?

	//try {
        include $_SERVER['DOCUMENT_ROOT'] . "/app/bootstrap.php";

        $uploader = new FileUploader();

        if ( !$uploader->uploadFile($_FILES['data']) ) {
            respond_from_object($uploader);
            exit(2);
        }

        $fileToParse = $uploader->getFullFileName();

        require_once dirname(__FILE__) . '/../app/lib/PHPExcel.php';

        $parser = new Parser();
        $storage = $parser->run($fileToParse);
        if ( count($storage) === 0 ) {
            respond_from_object($parser);
            unlink($fileToParse);
            exit(3);
        }
        
        $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');        
        
        $importer = new DataImporter();
        $DisciplineMatcher = new DisciplineMatcher();

        //throw new Exception(var_export($storage, true));

        if ( !$importer->import($storage, $DisciplineMatcher) ) {
            respond_from_object($importer);
            unlink($fileToParse);
            exit(4);
        }
        
        

        $checker = new ImportChecker($dbh);
        if ( !$checker->check() ) {
            respond_from_object($checker);
            unlink($fileToParse);
            exit(5);
        }

        $merger = new TableMerger($dbh);
        if ( !$merger->merge() ) {
            respond_from_object($merger);
            unlink($fileToParse);
            exit(6);
        }

        unlink($fileToParse);

        respond_from_object($checker);
        
    //}  catch (Exception $e) {
    //        respond('error', $e->getMessage());
    //}
?>