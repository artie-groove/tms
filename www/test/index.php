<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link rel="stylesheet" href="styles.css" />

<?

require 'TestDisciplineMatcher.php';

$modules = array(
    'TestDisciplineMatcher' => array(
            'testAll'
        ),
);

foreach ( $modules as $module => $tests )
{
    $m = new $module();
    foreach ( $tests as $test )
    {
        $result = $m->$test(true);
        $label = $result ? 'pass' : 'fail';
        echo "${module}::${test} <span id='${label}'>${label}</span><br />";
    }               
}

?>