<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Parser_Day_Evening
 *
 * @author Киба
 */
class parserDayEvening extends parserBase {
   
    public function load($fileName)
    {
        if(parent::load($fileName))
        {
           $this->setStatus("OK", "Успешно открыли файл $fileName");
           return true;
        } 
        else{
           $this->setStatus("ERROR", "Не удалось открыть файл $fileName");
           return false;
        }
        
    }
    

    private    function get_orientirs($Sheat)//определяет границы таблицы, а так же ширину колонки для группы.Устанавливает глобальные переменные. // В дневной и вечерний
    {
        $this->objPHPExcel;
        $this->Coll_Start;//начало таблицы (непосредственно данных)//инициализирует
        $this->Coll_End;//за концом таблицы//инициализирует
        $this->Row_Start;//начало таблицы//инициализирует
        $this->Row_End;//за концом таблицы//инициализирует
        $this->Row_Start_Date;//начало данных//инициализирует
        $this->Shirina_na_gruppu;//инициализирует
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
            $this->Row_Start++;
        }
        $this->Row_Start++;
        //Print $Row_Start;
        $this->Row_Start_Date =  $this->Row_Start+1;
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(1, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
            $this->Row_Start_Date++;
        }
        $this->Row_End=$this->Row_Start_Date;
        While($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_End)->getStyle()->getFill()->getStartColor()->getRGB()!=="FFFFFF"&&$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $this->Row_Start_Date)->getStyle()->getFill()->getStartColor()->getRGB()!=="000000")
        {
            $this->Row_End++;
        }
        while (!preg_match("/[А-Яа-я]+( )*-+( )*\d+/",trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_Start, $this->Row_Start))))
        {
            $this->Coll_Start++;
        }
        $count_z=0;
        $coll=$this->Coll_Start;
        while($count_z<1)//рассчитываем ширину на группу по первой ячейке для группы.
        {
            $coll++;
            $this->Shirina_na_gruppu++;
            if(trim($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll+1, $this->Row_Start))!="")
            {
                $count_z++;
            }
        }
        //print($Shirina_na_gruppu);
        $this->Coll_End=$this->Coll_Start;
        While(($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End, $this->Row_Start)->getStyle()->getBorders()->getLeft()->getBorderStyle()!=="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($this->Coll_End-1, $this->Row_Start)->getStyle()->getBorders()->getRight()->getBorderStyle()!=="none"))
        {
            $this->Coll_End+=$this->Shirina_na_gruppu;
        }
        $this->Coll_End-=$this->Shirina_na_gruppu;
    }
    private  function get_day_raspisanie ()// анализирует дневное и вечернее распсиание.  // В дневное и вечернее
    {
       
        $this->Coll_Start;//начало таблицы (непосредственно данных)
        $this->Coll_End;//за концом таблицы
        $this->Row_Start;//начало таблицы
        $this->Row_End;//за концом таблицы
        $this->Row_Start_Date;//начало данных
        $this->Group;//массив с данными.
        $this->Shirina_na_gruppu;//Число ячеек, отведённых на одну группу.
        $this->gani; //массив хранит границы дней недели
        $this->date_massiv;
        $this->Type_stady;
        for($Sheat=0;$Sheat<$this->objPHPExcel->getSheetCount();$Sheat++)
        {
            $this->Coll_Start=1;//начало таблицы (непосредственно данных)
            $this->Coll_End=1;//за концом таблицы
            $this->Row_Start=0;//начало таблицы
            $this->Row_End=0;//за концом таблицы
            $this->Row_Start_Date=0;//начало данных
            $this->Group=array();//массив с данными.
            $this->Shirina_na_gruppu=1;//Число ячеек, отведённых на одну группу.
            $this->gani=false; //массив хранит границы дней недели
            $this->date_massiv=false;
            $this->Order_66($Sheat);
            $this->get_orientirs($Sheat);
            $this->group_init($this->Coll_Start,$this->Coll_End,$this->Row_Start,$Sheat,$this->Shirina_na_gruppu);
            $this->dey_gran($this->Row_Start_Date,$this->Row_End,$Sheat);
            $this->get_mounday($this->Coll_Start,$this->Row_Start,$Sheat,$this->Row_Start_Date);
            for($i=$this->Row_Start_Date;$i<$this->Row_End;$i++)
            {
                for($k=$this->Coll_Start;$k<$this->Coll_End;$k++)
                {
                    if(($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getLeft()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k-1, $i)->getStyle()->getBorders()->getRight()->getBorderStyle()!="none")&&($this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i)->getStyle()->getBorders()->getTop()->getBorderStyle()!="none"||$this->objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i-1)->getStyle()->getBorders()->getBottom()->getBorderStyle()!="none"))
                    {

                        $res= $this->read_cell($i,$k,$Sheat);
                        $nau= floor(($k-$this->Coll_Start)/$this->Shirina_na_gruppu);
                        if($res[0]!="")//Если есть название предмета
                        {                       //var_dump($this->Group[$nau]);
                            $nau_par_count =count($this->Group[$nau]["Para"]);
                            if($nau_par_count>0)//Проверяем, если у нас пара не первая
                            {   //["Para"]

                                $Prev_par=$nau_par_count-1;
                                if($this->Group[$nau]["Para"][$Prev_par]->Predmet==false)//если у предыдущей пары нет предмета
                                {
                                    $NewPar=array_pop($this->Group[$nau]["Para"]);//Вытягиваем предыдущую пару на заполнение.
                                }
                                else
                                {
                                    $NewPar=new Pair();  // Иначе создаём новую пару.
                                }
                            }
                            else// если у нас первая пара
                            {
                                $Prev_par=$nau_par_count;
                                $NewPar=new Pair();
                            }

                            if($res[3]=="")//если у нас нет дат в ячейке
                            {
                                for($d=0;$d<count($this->date_massiv);$d++)
                                {
                                    $moun=$this->Mesac_to_chislo ($this->date_massiv[$d]["month"]);
                                    $f=0;
                                    while($i>$this->gani[$f])
                                    {
                                        $f++;
                                    }
                                    $dart=$this->date_massiv[$d]["date"][$f];
                                    $dart= explode("|",$dart);
                                    for($l=0;$l<count($dart)-1;$l++)
                                    {
                                        $NewPar->Date.=$dart[$l].".".$moun.",";
                                    }
                                }
                            }
                            else// если даты в ячейке есть
                            {
                                $NewPar->Date=$res[3];
                            }
                            if(preg_match("/\.$/",$NewPar->Date,$m))
                            {
                                str_replace($m, "", $NewPar->Date);
                            }
                            $NewPar->Predmet=$res[0];
                            $NewPar->Type=$res[1];
                            $NewPar->Auditoria=$res[2];
                            $NewPar->Prepod=$res[4];
                            $NewPar->Comment.=trim($res[5]);
                            $group_count= floor($res[7]/$this->Shirina_na_gruppu);
                            $this->get_par_number($i,$this->Coll_Start,$Sheat,&$NewPar);
                            if($group_count==0&& !is_int(($k-$this->Coll_Start+$this->Shirina_na_gruppu)/$this->Shirina_na_gruppu))
                            {
                                $this->exchangePrev($nau,&$NewPar);
                             $par_count=floor($res[6]/2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.
                                for($d=0;$d<$par_count;$d++)
                                {
                                    $par_temp= new Pair();
                                    $par_temp->copy($NewPar);
                                    $par_temp->ParNumber+=$d;
                                    $par_temp->Group=$this->Group[$nau]["NameGroup"];
                                    array_push( $this->Group[$nau]["Para"],$par_temp);
                                }
                            }
                            else
                            {
                                $par_count=floor($res[6]/2);//ЗАМЕТКА!!!!!_______ потом рассчитать длинну в стоках для пары. На основе размера ячейки с указанием номера пары.

                                if($group_count==0)
                                {
                                    $group_count=1;
                                }
                                for($l=0;$l<$group_count;$l++)
                                {//$par_temp= new Pair();
                                    if(count($this->Group[$nau+$l]["Para"])>0)
                                    {
                                        if($this->Group[$nau+$l]["Para"][count($this->Group[$nau+$l]["Para"])-1]->Predmet=="")
                                        {
                                            array_pop($this->Group[$nau+$l]["Para"]);
                                        }

                                    }
                                    for($z=0;$z<$par_count;$z++)
                                    {
                                        $par_temp= new Pair();
                                        $par_temp->copy($NewPar);
                                        $par_temp->ParNumber+=$z;
                                        $par_temp->Group=$this->Group[$nau+$l]["NameGroup"];
                                        array_push( $this->Group[$nau+$l]["Para"],$par_temp);
                                    }
                                }
                            }
                        }
                        else // названия предммета нет.
                        {
                            if(trim($res[5])!="")
                            {
                                $NewPar= new Pair();
                                $NewPar->Predmet=false;
                                $NewPar->Comment=$res[5];
                                $NewPar->Group=$this->Group[$nau]["NameGroup"];
                                array_push( $this->Group[$nau]["Para"],$NewPar);
                            }
                        }
                    }

                }
            }
            // writ_to_bd_d();
        }
    }
    public function parsing()  // В общее
    {
        try {
            $this->get_day_raspisanie();//Запускаю парсинг.
            $this->setStatus("OK", "Парсинг прошёл успешно.");
            return true;
            }
        catch(Exception $e)
        {
            // print($e);
            $this->setStatus("Error", "Парсинг провалился: что-то пошло не так.");
            return false;
        }
    }
}

?>
