<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Parser_factory
{
    
    function Parser_factory($fileName)
    {
        include $_SERVER['DOCUMENT_ROOT'].'/lib/Classes/PHPExcel.php';
        include $_SERVER['DOCUMENT_ROOT']."/app/Parser.php";
    }
    function getType($fileName)
    {
      // Здесь начинается лютый, беспросветный полярный лис. Функция перевода имени столбца в индекс не найдена, получить индекс максимального столбца тоже невозможно. Я не виноват!!!!
         $objPHPExcel = PHPExcel_IOFactory::load($fileName);
         $Sheat=0;
        $name_max_col = $objPHPExcel->getSheet($Sheat)->getHighestColumn();
        // print ( $name_max_col."  ");
        $coll_max=0;//максимальный заюзанный столбец.
        // print(objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn());
        do
        {
            $coll_max++;
        }
        while($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($coll_max, 1)->getColumn()!=$name_max_col);
        $coll_max++;
        $Row_Max=1;
        While($objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max)->getStyle()->getBorders()->getBottom()->getBorderStyle()==="none"&&  $objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow(0, $Row_Max+1)->getStyle()->getBorders()->getTop()->getBorderStyle()==="none")
        {
            $Row_Max++;
        }
        $Row_Max++;
        $matches[0] = false;
        for($i=1;$i<$Row_Max;$i++)
        {
            for($k=0;$k<$coll_max;$k++)
            {
                preg_match("/Заочного|Вечернего|Второго|Инженерно|Автомеханического/iu", $objPHPExcel->getSheet($Sheat)->getCellByColumnAndRow($k, $i), $matches);
                if($matches)
                {
                    switch($matches[0])
                    {
                        case "Заочного":{return 2; ;break;}
                        case "Вечернего":{return 1; ;break;}
                        case "Второго":{return 3; ;break;}
                        case "Инженерно":{return 0; ;break;}
                        case "Автомеханического":{return 0; ;break;}
                        default :{break;}
                    }
                }
            }
        }
    
 
    }
}
?>
