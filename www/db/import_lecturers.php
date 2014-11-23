<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

try
{
    $dbh = new PDO("mysql:host=localhost", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $dbh->exec("set character set utf8");
	$query = "SELECT surname, name, middlename, post, subdivision_id
              FROM corp.corp_members
              WHERE
              LOWER(post) like '%старший преподаватель%' OR
              LOWER(post) LIKE '%доцент%' OR
              LOWER(post) LIKE '%ассистент%' OR
              LOWER(post) LIKE '%профессор%'";
    $res = $dbh->query($query);

    foreach($res as $row)
    {
        print($row['surname']." ".$row['name']." ".$row['middlename']." ".$row['post']."<BR>");//subdivision_id
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
/*
$link = mysql_connect('localhost', 'root', '') or die('Не удалось соединиться: ' . mysql_error());
mysql_select_db('corp') or die('Не удалось выбрать базу данных');
$query = "SELECT surname,name,middlename,post,subdivision_id FROM corp_members Where post='Старший преподаватель' OR post='Доцент' OR post='Ассистент' OR post='Профессор'";
$res_SQL = mysql_query($query);
mysql_select_db('raspisanie') or die('Не удалось выбрать базу данных');

while ($row = mysql_fetch_assoc($res_SQL))
{
    print($row['surname']." ".$row['name']." ".$row['middlename']." ".$row['post']."<BR>");//subdivision_id
    switch ($row['post'])
    {
       case "Старший преподаватель":{$r="SENIOR_LECTURER";break;}
       case "Доцент":{$r="DOCENT";break;} 
       case "Ассистент":{$r="ASSISTANT";break;} 
       case "Профессор":{$r="PROFESSOR";break;}
    }
    $query="INSERT INTO lecturers (id_department,name,surname,patronymic,`position`) VALUES (".$row['subdivision_id'].",'".$row['name']."','".$row['surname']."','".$row['middlename']."','".$r."')";
    //echo $query;
    $res_SQL_2 = mysql_query($query)or die('Всё плохо' . mysql_error());
}
*/
?>
