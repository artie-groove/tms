<? 
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
?>


<?

    function remap($x, $values = array(), $offset = 0) {
        
        $n = count($values);
        if ( $n === 0 || $offset >= $n || $x < $values[$offset] ) return $x;
        for ( $i = $offset; $i < $n; $i++ ) {
            if ( $x >= $values[$i] ) {           
                while ( $i < $n - 1 && $values[$i+1] - $values[$i] === 1 ) {
                    $i++;
                }
                $dx = $i - $offset + 1;
                $offset = $i + 1;
            }
            break;
        }
        
        // debug
        /*
        static $c = 1;
        echo 'step' . $c++ . ': ' . $x . ' => ' . ( $x + $dx ) . '<br>';
        */
        
        $x += $dx;
        
        return remap($x, $values, $offset);
    }

    function removeHiddenRows($sheet) {
        
        $hiddenRowsIndexes = array();
        $highestRow = $sheet->getHighestRow();
        for ( $r = 1; $r <= $highestRow; $r++ ) {
            $visible = $sheet->getRowDimension($r)->getVisible();
            //echo $r . ' => ' . ( $visible ? 'true' : 'false' ) . '<br />';
            if ( ! $visible ) {
                $hiddenRowsIndexes[] = $r;
            }
        }

        $i = 0;
        foreach ( $hiddenRowsIndexes as $row ) {
            $sheet->removeRow($row - $i);
            $i++;
        }
        
        return $hiddenRowsIndexes;
    }

    function removeHiddenCols($sheet) {
        
        $hiddenColsIndexes = array();
        $highestCol = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        for ( $c = 0; $c <= $highestCol; $c++ ) {
            $visible = $sheet->getColumnDimensionByColumn($c)->getVisible();
            echo $c . ' => ' . ( $visible ? 'true' : 'false' ) . '<br />';
            if ( ! $visible ) {
                $hiddenColsIndexes[] = $c;
            }
        }

        $i = 0;
        foreach ( $hiddenColsIndexes as $col ) {
            $sheet->removeColumnByIndex($col - $i);
            $i++;
        }
        
        return $hiddenColsIndexes;
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . 'app/bootstrap.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . 'app/lib/PHPExcel.php';

    $inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/tests/cleaner/hidden.xlsx';
    $inputFileType = 'Excel2007';
    $reader = PHPExcel_IOFactory::createReader($inputFileType);
    if ( !$reader->canRead($inputFileName) ) throw new Exception('Unsupported file format');

    /**  Identify the type of $filename  **/
    //$inputFileType = PHPExcel_IOFactory::identify($filename);
    /**  Create a new Reader of the type that has been identified  **/
    //$objReader = PHPExcel_IOFactory::createReader($inputFileType);
    /**  Load $inputFileName to a PHPExcel Object  **/
    //$this->PHPExcel = $objReader->load($filename);

    //$worksheetData = $reader->listWorksheetInfo($inputFileName);
    
    $PHPExcel = $reader->load($inputFileName);
    $sheet = $PHPExcel->getActiveSheet();



     

    $hiddenRowsIndexes = removeHiddenRows($sheet);
    $hiddenColsIndexes = removeHiddenCols($sheet);

    $highestRow = $sheet->getHighestRow();
    $highestCol = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());

    

    for ( $r = 1; $r <= $sheet->getHighestRow(); $r++ ) {
        $visible = $sheet->getRowDimension($r)->getVisible();
        echo $r . ' => ' . ( $visible ? 'true' : 'false' ) . ' | ' . $sheet->getCellByColumnAndRow(0, $r) . '<br />';
    }

    echo '<h6>Rows remapping</h6>';
    foreach ( range(1, $highestRow) as $r )
        echo $r . ' => ' . remap($r, $hiddenRowsIndexes) . '<br />';

    echo '<h6>Columns remapping</h6>';
    foreach ( range(0, $highestCol - 1) as $c )
        echo $c . ' => ' . remap($c, $hiddenColsIndexes) . '<br />';
    

    $info = array(
        'highestRow' => $sheet->getHighestRow(),
        'highestColumn' => $sheet->getHighestColumn(),
        'highestColumnIndex' => $highestCol,
        'hiddenRows' => $hiddenRowsIndexes,
        'hiddenCols' => $hiddenColsIndexes
    );

    print_r($info);
?>
