<? 
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
?>


<?

    require_once $_SERVER['DOCUMENT_ROOT'] . '/app/bootstrap.php';
    require 'TestParser.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/app/lib/PHPExcel.php';

    $parser = new Parser();
    $test = new TestParser($parser);
    $allTestsPassed = $test->run();

?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" href="/tests/styles.css" />

<?
    echo '<table width="100%">';
    foreach ( $test->results as $res )
    {    
        $isTestPassed = $res['result'];
        $label = $isTestPassed ? 'pass' : 'fail';
        echo "<tr><td>${res['test']}</td><td><span class='${label}'>${label}</span></td></tr>";
        if ( !$isTestPassed ) {
            echo "<tr><td colspan='2'>{$res['description']}</td></tr>";
        }
    }
    echo '</table>';

    if ( !$allTestsPassed ) {
        $test->printDump();
    }

?>