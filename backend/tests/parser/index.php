<? 
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');

    function exception_handler_debug(Exception $e) {
        $msg = $e->getFile() . ':' . $e->getLine() . '<br>';
        $msg .= '<strong>' . $e->getMessage() . '</strong><br><br><br>';
        foreach ( $e->getTrace() as $trace ) {
            if ( empty($trace['line']) ) $trace['line'] = 'anonymous function';
            if ( empty($trace['file']) ) $trace['file'] = 'anonymous function';
            $msg .= $trace['file'] . ':' . $trace['line'] . '<br>';
            $args = array();
            foreach ( $trace['args'] as $argument )
                $args[] = ( is_numeric($argument) || is_string($argument) ) ? $argument : gettype($argument);
            
            $msg .= $trace['function'] . '(' . implode(',', $args) . ')' . '<br><br>';
        }
        echo '<pre>' . $msg . '</pre>';
    }
?>


<?

    require_once $_SERVER['DOCUMENT_ROOT'] . 'app/bootstrap.php';
    set_exception_handler('exception_handler_debug');
    require 'TestHarvester.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . 'app/lib/PHPExcel.php';

/*
    $inputFileName = $_SERVER['DOCUMENT_ROOT'] . '/examples/fei4_140213.xlsx';
    $inputFileType = 'Excel2007';
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    if ( !$objReader->canRead($inputFileName) ) throw new Exception('Unsupported file format');
    $worksheetData = $objReader->listWorksheetInfo($inputFileName);
    print_r($worksheetData);
    exit;
*/

    $parser = new Parser();
    $testSet = array('Fulltime', 'Evening', 'PostalTutorials', 'PostalSession');

    foreach ( $testSet as $type )
    {
        echo '<h1>' . $type . '</h1>';
        $tester = new TestHarvester($parser, $type);
        $allTestsPassed = $tester->run();
        printTestResults($tester, $allTestsPassed);
        echo '<br />';
    }
    
    

?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="/tests/styles.css" />

<?
    function printTestResults($tester, $allTestsPassed)
    {
        echo '<table width="100%"><col width="50" />';
        foreach ( $tester->results as $res )
        {    
            $isTestPassed = $res['result'];
            $label = $isTestPassed ? 'pass' : 'fail';
            echo "<tr><td><span class='${label}'>${label}</span></td><td>${res['test']}</td></tr>";
            if ( !$isTestPassed ) {
                echo "<tr><td colspan='2'>{$res['description']}</td></tr>";
            }
        }
        echo '</table>';

        if ( !$allTestsPassed ) {
            $tester->printDump();
        }
    }

?>