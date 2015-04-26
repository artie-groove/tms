<?

	//try {
        include $_SERVER['DOCUMENT_ROOT'] . "/app/bootstrap.php";

        $uploader = new XlsxFileUploader();

        if ( !$uploader->uploadFile($_FILES['data_xlsx']) ) {
            respond_from_object($uploader);
            exit(2);
        }

        $fileToParse = $uploader->getFullFileName();

        require_once dirname(__FILE__) . '/../app/lib/PHPExcel.php';

        $parser = new Parser();
        $data = $parser->run($fileToParse);
        if ( !$data ) {
            respond_from_object($parser);
            unlink($fileToParse);
            exit(3);
        }
        
       
        
        $status = array('status' => 'ok', 'details' => 'Распознавание прошло успешно');        
        
        $importer = new DataImporter();
        $DisciplineMatcher = new DisciplineMatcher();

        if ( !$importer->import($data, $DisciplineMatcher) ) {
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