<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');

try
{
	$dbh = new PDO("mysql:host=localhost;dbname=corp", "root", "", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	$dbh->exec("set character set utf8");
	$res = $dbh->query("SELECT surname,name,middlename,post,subdivision_id FROM corp_members Where post='Старший преподаватель' OR post='Доцент' OR post='Ассистент' OR post='Профессор'");
	$dbh->exec("use raspisanie");

	while ($row = $res->fetch(PDO::FETCH_ASSOC))
	{
		print($row['surname']." ".$row['name']." ".$row['middlename']." ".$row['post']."<BR>");//subdivision_id
		switch ($row['post'])
		{
			case "Старший преподаватель": $r="SENIOR_LECTURER"; break;
			case "Доцент":                $r="DOCENT";          break;
			case "Ассистент":             $r="ASSISTANT";       break;
			case "Профессор":             $r="PROFESSOR";       break;
		}

		$res = $dbh->prepare("INSERT INTO lecturers (`id_department`,`name`,`surname`,`patronymic`,`position`) VALUES (?, ?, ?, ?, ?)");
		$res->execute(array($row['subdivision_id'], $row['name'], $row['surname'], $row['middlename'], $r));
	}
	$dbh = null;
}
catch (PDOException $exc)
{
	$dbh = null;
	$this->setStatus("Error", "On line:" . $exc->getLine() . " --- " . $exc->getMessage());
}

?>
