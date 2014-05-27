<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 27.05.14
 * Time: 23:59
 */

/*
 *          public $Predmet;
            public $Prepod;
            public $Type;
            public $Auditoria;
            public $ParNumber;
            public $Date;
            public $Comment;
            public $Group;
 */
class BD_Pusher
{
    public function push($par_mass)//Запись в базу данных массива GROUP
    {
        $link = mysql_connect('localhost', 'root', '') or die('Не удалось соединиться: ' .mysql_error());
        mysql_select_db('raspisanie') or die('Не удалось выбрать базу данных');
        for($i=0;$i<count($par_mass);$i++)
        {
            $group_id=0;
            if(trim($par_mass[$i]->Group)!="")
            {
                $query = "SELECT id,name FROM groups Where name='".$par_mass[$i]->Group."'";
                $res_SQL = mysql_query($query)or die('Провал запроса на гркппу');
                $row = mysql_fetch_assoc($res_SQL);
                if($row)
                {
                    $group_id= $row['id'];
                }
            }

            $prepod_id=0;
            if(trim($par_mass[$i]->Prepod)!="")
            {
                $inicial=array();
                if(preg_match_all("/[А-Я]\./ui", $Group[$i]["Para"][$k]->Prepod, $matches,PREG_PATTERN_ORDER)>0)
                {
                    for($l=0;$l<count($matches[0]);$l++)
                    {
                        $par_mass[$i]->Prepod=trim(str_replace($matches[0][$l],"", $par_mass[$i]->Prepod));
                        $inicial[$l]=trim(rtrim($matches[0][$l],'.'));
                    }
                    $query = "SELECT surname,name,patronymic FROM lecturers Where surname='".$Group[$i]["Para"][$k]->Prepod."',name LIKE '".$matches[0][0]."%',patronymic LIKE '".$matches[0][1]."'";
                    //echo $query;
                    $res_SQL = mysql_query($query)or die('Не нашли препода: ' . mysql_error());;
                    while ($row = mysql_fetch_assoc($res_SQL))
                    {
                        var_dump($row); print($row['name'][0]." ".$row['patronymic'][0]."!=".$inicial[0]." ".$inicial[1]);
                        //print("<BR>");
                        if($row['name'][0]==$inicial[0]&&$row['patronymic'][0]==$inicial[1])
                        {
                            //print($row['name']." ".$row['patronymic']);
                        }
                    }
                }
                else
                {

                }
            }

        }


        /*

                     }/** /

                   }


                 }



                // print("<br>");
               /** /

                $res_SQL = mysql_query($query);
                $temp=mysql_fetch_array($res_SQL);

                if($temp)
                {
                 $prepod_id= $temp['ID_Lecturer'];
                }
              /** /   else
                 {
                     $query="INSERT INTO lecturer (Family,Department_ID) VALUES ('".$Group[$i]["Para"][$k]->Prepod."',1)";
                     mysql_query($query) or die('Не удалось добавить преподавателя ' .mysql_error());
                     $query = "SELECT * FROM lecturer Where Family='".$Group[$i]["Para"][$k]->Prepod."'";
                     $res_SQL = mysql_query($query);
                     $temp=mysql_fetch_array($res_SQL);
                if($temp)
                {
                    $prepod_id=$temp['ID_Lecturer'];
                }
                else
                {
                   die('Не удалось найти добавленную преподавателя');
                }
             }/** /
                //проверяем, есть ли такой кабинет
                 if($Group[$i]["Para"][$k]->Auditoria=="")
                 {
                    $Group[$i]["Para"][$k]->Auditoria[0]=-1;
                    //print("!!!НЕТ АУДИТОРИИ!!!");
                 }
                 else
                 {
                     for($ts=1;$ts<count($Group[$i]["Para"][$k]->Auditoria);$ts++)
                     {
                        $Group[$i]["Para"][$k]->Comment.=" ".$Group[$i]["Para"][$k]->Auditoria[$ts];
                     }

                 }
                  $query = "SELECT * FROM classroom Where Number_classroom='".$Group[$i]["Para"][$k]->Auditoria[0]."'";
                  $res_SQL = mysql_query($query);
                  $temp=mysql_fetch_array($res_SQL);
                  $Auditoria_id=false;
                 if($temp)
                 {
                 $Auditoria_id= $temp['ID_Classroom'];
                 }
                 else
                 {
                     $query="INSERT INTO classroom (Number_classroom,Building) VALUES ('".$Group[$i]["Para"][$k]->Auditoria[0]."',0)";
                     mysql_query($query) or die('Не удалось добавить аудиторию ' .mysql_error());
                     $query = "SELECT * FROM classroom Where Number_classroom='".$Group[$i]["Para"][$k]->Auditoria[0]."'";
                     $res_SQL = mysql_query($query);
                     $temp=mysql_fetch_array($res_SQL);
                if($temp)
                {
                    $Auditoria_id=$temp['ID_Classroom'];
                }
                else
                {
                   die('Не удалось найти добавленную аудиторию');
                }
             }


             //!!!!!!!!

                //проверяем, есть ли такой предмет
                $query = "SELECT * FROM subject Where Subject_Name='".$Group[$i]["Para"][$k]->Predmet."'";
                $res_SQL = mysql_query($query);
                $temp=mysql_fetch_array($res_SQL);
                $Subject_id=false;
                if($temp)
                {
                 $Subject_id= $temp['ID_Subject'];
                }
                 else
                 {
                     $query="INSERT INTO subject (Subject_Name) VALUES ('".$Group[$i]["Para"][$k]->Predmet."')";
                     mysql_query($query) or die('Не удалось добавить предмет ' .mysql_error());
                     $query = "SELECT * FROM subject Where Subject_Name='".$Group[$i]["Para"][$k]->Predmet."'";
                     $res_SQL = mysql_query($query);
                     $temp=mysql_fetch_array($res_SQL);
                if($temp)
                {
                    $Subject_id= $temp['ID_Subject'];
                }
                else
                {
                   die('Не удалось найти добавленный предмет');
                }
             }
             //!!!!!!!!


             $type=0;
             //Тип занятия : 1 - лаба, 2 - лекция, 4 - практика.
             if(preg_match("/лаб(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
             {
              $type=1;
             }elseif(preg_match("/лек(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
             {
               $type=2;
             }elseif(preg_match("/пр(( )*\.)?/", $Group[$i]["Para"][$k]->Type,$maches))
             {
               $type=3;
             }
             //заносим в расписание.
              $date_m = explode(",",$Group[$i]["Para"][$k]->Date);
              $nau_ear = date("Y");
              $correct=0;
              if(trim($date_m[count($date_m)-1])=="")
              {
                  $correct=1;
              }
              for($in=0;$in<count($date_m)-$correct;$in++)
              {
                $d_a_m = explode(".",$date_m[$in]);
               $query="INSERT INTO timetable
(ID_Grup,ID_Lecturer,ID_classroom,Time,Date,Type,ID_Subject,Comment) VALUES (".$group_id.",".$prepod_id.",".$Auditoria_id.",".$Group[$i]["Para"][$k]->ParNumber.",'".$nau_ear."-".$d_a_m[1]."-".$d_a_m[0]."',".$type.",". $Subject_id.",'".$Group[$i]["Para"][$k]->Comment."')";
               mysql_query($query) or die('Не удалось добавить пару ' . mysql_error());
              }
              print("<BR>");
            /** /

            }

         }
        * /**/
    }

}