<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

require $_SERVER['DOCUMENT_ROOT'] . '/app/bootstrap.php';

try
{
    //$dbh = new PDO("mysql:host=localhost", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $dbh->exec("set character set utf8");
    
    // shell execution of mysql utility to import table structure and data of `corp_members` to database `corp_volpi_ru`
    $command = "mysql -u{$dbuser} -h {$dbhost} -D corp_volpi_ru < {$_SERVER['DOCUMENT_ROOT']}/../db/corp_members.sql 2>&1";

    echo 'Executing: ' . shell_exec($command) . '<br />';
    
    $dbh->exec("truncate table tms.lecturers");
	$query = "
        SELECT surname, name, middlename, post, subdivision_id
        FROM corp_volpi_ru.corp_members
        WHERE
            LOWER(post) LIKE '%старший преподаватель%' OR
            LOWER(post) LIKE '%доцент%' OR
            LOWER(post) LIKE '%ассистент%' OR
            LOWER(post) LIKE '%профессор%'";
    
    $res = $dbh->query($query);

    foreach($res as $row)
    {
        print($row['surname']." ".$row['name']." ".$row['middlename']." ".$row['post']."<BR>");
        if(stristr($row['post'], "старший преподаватель")) $r="SENIOR_LECTURER";
		else if(stristr($row['post'], "доцент"))           $r="DOCENT";
		else if(stristr($row['post'], "ассистент"))        $r="ASSISTANT";
		else if(stristr($row['post'], "профессор"))        $r="PROFESSOR";
/*
		switch ($row['post'])
        {
            case "старший преподаватель": $r="SENIOR_LECTURER"; break;
            case "доцент":                $r="DOCENT";          break;
            case "ассистент":             $r="ASSISTANT";       break;
            case "профессор":             $r="PROFESSOR";       break;
        }*/

		$res = $dbh->prepare("INSERT INTO tms.lecturers (`id_department`,`name`,`surname`,`patronymic`,`position`) VALUES (?, ?, ?, ?, ?)");
		$res->execute(array($row['subdivision_id'], $row['name'], $row['surname'], $row['middlename'], $r));
    }
	$dbh = null;
}
catch (PDOException $exc)
{
    $dbh = null;
    print("Error On line:" . $exc->getLine() . " --- " . $exc->getMessage() . " --- " . $exc->getCode());
}
?>
