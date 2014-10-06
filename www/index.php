<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>
</head>
<body>
<?php

       
        error_reporting(E_ALL);
        ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
        
        include $_SERVER['DOCUMENT_ROOT'].'/app/bootstrap.php';
       
        include $_SERVER['DOCUMENT_ROOT'].'/app/lib/PHPExcel.php';
       // include $_SERVER['DOCUMENT_ROOT']."/app/entities/Pair.php";
        //include $_SERVER['DOCUMENT_ROOT']."/app/Parser.php";
        //include $_SERVER['DOCUMENT_ROOT']."/app/core/DataImporter.php";
       // include $_SERVER['DOCUMENT_ROOT']."/app/parserDayEvening.php";
        /**/
       
        $test = new parserExtramuralLong();
       // var_dump($test);
        ;
        if($test->load("summer_session_postal_4course_140603.xls"))
        {$test->parsing();
        var_dump($test->getParseData());
        }
       
       // $objPHPExcel = PHPExcel_IOFactory::load($fileName);
        //$objPHPExcel->getSheet($Sheet)->getCellByColumnAndRow($coll, $row)->getStyle()->getFont()->getColor()->getRGB();
       // $test2= new BD_Pusher();//fei4_140213.xlsx  fei5.xlsx vf5_140213.xlsx
        
        /** / if($test->parsing("fei5.xlsx"))
        {
            var_dump($test->getParseData()); 
            $push= new BD_Pusher();
            $push->push($test->getParseData(),0);
        }
        /**/
        
        
       //getParseData;
        //$test2->push();
        //$s= new Pair();
        
       // error_reporting(E_ALL);
       // ini_set('display_errors', 'on');
       // include '/lib/excel_reader2.php';
      //  require_once dirname(__FILE__) . '/lib/Classes/PHPExcel.php';
      //  $test = new Parser();
      //  var_dump($test->parsing("fei5.xlsx"));
        
       // objPHPExcel = PHPExcel_IOFactory::load("fei5.xlsx");
       //fei4_140213
       //vf5_140213.xlsx
       //objPHPExcel = PHPExcel_IOFactory::load("vf5_140213.xlsx");//postal_3course_140506.xlsx
       //objPHPExcel = PHPExcel_IOFactory::load("postal_3course_140506.xlsx");
      //-----------------------------------------------------------------------  
      // Функциональная зона. Возвращает: 0)Предмет. 1) Тип занятия. 2)Аудитории (массив) 3) даты 4) преподаватель 5) комментарий. 6)Число строк 7) число столбцов
      
      
      
      //----------------------------------------------------------------------- функции общего назначения
       
      
        
      //--------------------------------------------------------------------- функции для дневного и вечернего отделения.
      
        
      
       //----------------------------------------------------------------------
       


       
     //echo $objPHPExcel->getSheet(0)->getColumnDimensionByColumn(0)->getWidth();
     //$objPHPExcel->getSheet()->removeRow($Section_width)
      /*
     echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ";
      echo $objPHPExcel->getSheet()->getCellByColumnAndRow(15, 11);
     $objPHPExcel->getSheet()->removeColumnByIndex(15);     
     echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ";
     echo $objPHPExcel->getSheet()->getCellByColumnAndRow(15, 11);
       */
      //echo $this->get_typ_raspisania(0);
       //echo  $objPHPExcel->getSheet()->getColumnDimensionByColumn(15)->getWidth()." ! ".$objPHPExcel->getSheet(0)->getColumnDimensionByColumn(15)->getVisible();
      // $objPHPExcel->getSheet(0)->getColumnDimensionByColumn(15)->getVisible();
              
        
        ?>
</body>
</html>
