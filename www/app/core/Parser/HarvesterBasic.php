<?php

abstract class HarvesterBasic extends TableHandler
{
    protected $sheet;
    protected $firstRow;
    
    public function __construct($sheet, $firstRow)
    {
        $this->sheet = $sheet;
        $this->firstRow = $firstRow;
    }
    
    
    // === Запустить сбор данных
    abstract public function run();
    
    
    // === Прочесать локацию
    // извлекает данные из локации и возвращает массивом 
    
    protected function extractLocation($sheet, $cx, $w, $rx, $h)
    {
        // начальный столбец
        $row = $rx;
        // начальная строка
        $col = $cx;
        $width = 0;
        
        // массив результатов
        $result = array(
            'discipline' => '',
            'type' => '',
            'room' => '',
            'lecturer' => '',
            'dates' => '',
            'time' => '',
            'comment' => ''
        );
        
        
        for ( $r = $rx; $r < $rx + $h; $r++ )
        {
            for ( $c = $cx; $c < $cx + $w; $c++ )
            {
                $str = trim($sheet->getCellByColumnAndRow($c, $r));
                if ( ! empty($str) )
                {
                    $matches = array();
                    
                    // если текст помечен жирным, то это название дисциплины
                    if ( $sheet->getCellByColumnAndRow($c, $r)->getStyle()->getFont()->getBold() == 1 )
                    {
                        // кроме названия дисциплины в строке могут находиться и другие сведения
                        if ( preg_match('/((?:[А-яA-Z]+[\s.,\/-]{0,3})+)(?:\((?1)\))?/u', $str, $matches) !== false )
                        {   
                            // конкатенация для тех случаев, когда название дисциплины
                            // продолжается в следующей ячейке
                            $result['discipline'] .= $matches[0] . ' ';
                            $str = str_replace($matches[0], '', $str);
                            //$str = mb_substr($str, mb_strlen($matches[0]));
                            //$result['comment'] .= ' ' . $str;                 
                        }                        
                    }
                    
                    // поиск типа занятия                        
                    if ( preg_match("/(?:^|\s)((?:лаб|лек|пр)\s*\.?)/u", $str, $matches) )
                    {
                        $result['type'] = $matches[1];
                        $str = str_replace($matches[1], '', $str);
                    }

                    // ищем временные диапазоны (типа коментариев: с 18:30 или 13:00-16:00)

                    /*
                        // если этого не сделать, то эти данные могут быть интерпретированы как даты                        
                        if ( preg_match("/(?:с\s+)?\d{1,2}\.\d\d\s*-\s*\d{1,2}\.\d\d/i", $str, $matches) )
                        {
                            $result['comment'] .= ' ' . $matches[0];
                            $str = str_replace($matches[0], '', $str);                           
                        }
                        */

                    if ( preg_match('/(?:[СCсc]\s*)?([012]?\d:[0-5]0)(?:\s*-\s*(?-1))?/u', $str, $matches) )
                    {
                        $result['time'] = $matches[1];
                        $result['comment'] .= ' ' . $matches[0];
                        $str = str_replace($matches[0], '', $str);
                        //if ( $matches[1] == "11:50" ) throw new Exception($matches[0] . '/' . $str);
                    }

                    // поиск аудитории                        
                    if ( preg_match("/((?:[АБВД]|БЛК)-\d{2,3}|ВПЗ|гараж\s№3)(?:\s|$|,)/u", $str, $matches) )
                    {   
                        $result['room'] = $matches[1];
                        $str = str_replace($matches[0], '', $str);
                    }

                    // поиск эксплицитных дат
                    //if ( preg_match('/(\d{1,2}\.\d{2}(?:,\s?|$))+/u', $str, $matches) )
                    if ( preg_match('/(?:([1-3]?\d\.[01]\d)(?:\s?(?=,),\s*|(?:(?!\1)|(?!))))+/u', $str, $matches) )
                    {   
                        $result['dates'] = preg_replace('/\s/u', '', $matches[0]);
                        $str = str_replace($matches[0], "", $str);
                    }
                    
                    /*
                        // вырезаем подстроку с датами из комментария
                        $posFrom = $mc[0][1];
                        $posTo = $posFrom + mb_strlen($mc[0][0]);
                        $data['comment'] = mb_substr($data['comment'], 0, $posFrom) . mb_substr($data['comment'], $posTo);
                        // вырезаем все пробелы из строки с датами                                    
                        $data['dates'] = preg_replace('/\s/u', '', $mc[0][0]);
                    */

                    // поиск преподавателя                        
                    if ( preg_match('/[А-Я][а-я]+(?:\s*[А-Я]\.){0,2}/u', $str, $matches) )
                    {                            
                        $result['lecturer'] = $matches[0];
                        $str= str_replace($matches[0], '', $str);
                    }

                    // оставшийся кусок строки цепляем к комментарию
                    $str = trim($str);
                    if ( !empty($str) ) $result['comment'] .= $str . " ";                    
                }
            }        
        }
        $result['comment'] = trim($result['comment']);
        $result['discipline'] = trim($result['discipline']);
        return $result;
    }
    
    
    // === Замерить локацию (клетку с описанием занятия)
    // ищем границы локации: упираемся в правую и находим ширину, затем в нижнюю и находим высоту
    // пока ищем высоту, выискиваем "дырки" в правой границе
    // если обнаружена "дырка" - ныряем до упора: граница типа B
    // если нет - проходимся по строкам локации в поисках внутренней границы типа А
    // факт существования границы говорит о том, что у локации нарушена целостность
    
    protected function inspectLocation($sheet, $cx, $rx)
    {        
        $summary = array(               // выходные данные локации:
            'width' => '',              // ширина (в ячейках)
            'height' => '',             // высота (в ячейках)
            'offset' => 0               // смещение в столбцах до внутренней границы
        );
        $row = $rx; // начальная строка
        $col = $cx; // начальный столбец
        
        // находим правую границу локации        
        while ( ! $this->hasRightBorder($sheet, $col, $rx) ) $col++;
        $summary['width'] = $col - $cx + 1;
        // ищем нижнюю границу локации начиная со второй строки
        $row++;
        while ( ! $this->hasBottomBorder($sheet, $col, $row - 1) ) {
            // в это же время поглядываем на правую границу
            if ( ! $this->hasRightBorder($sheet, $col, $row) ) {
                // если справа "дырка", то фиксируем это в протокол,
                // смещаемся до правой границы, корректируем ширину и падаем на дно 
                $summary['offset'] = $col - $cx + 1;     
                while ( ! $this->hasRightBorder($sheet, $col, $row) ) $col++;
                $summary['width'] = $col - $cx + 1;
                while ( ! $this->hasBottomBorder($sheet, $col, $row) ) $row++;             
                $summary['height'] = $row - $rx; 
            }     
            $row++;
        }  
        $summary['height'] = $row - $rx;
        if ( ! empty($summary['offset']) ) return $summary;
        if ( $summary['height'] === 1 ) throw new Exception("Некорректная локация близ ячейки $col:$row");
        
        // ищем внутренние границы
        for ( $r = $rx + 1; $r <= $row; $r++ ) {
            for ( $c = $cx; $c < $col; $c++ ) {                
                if ( $this->hasRightBorder($sheet, $c, $r) ) {
                    $summary['offset'] = $c - $cx + 1;                
                    return $summary;
                }
            }
        }
        return $summary;
    }
    
    
    // === Определить размеры таблицы (ширину и высоту)
    // таблица просматривается поячеечно вправо и вниз
    // до тех пор, пока не встретится ячейка, лишённая границ
    
    protected function inspectTableGeometry($sheet, $rx)
    {
        $w = 1; // cols
        $h = 0; // rows
    
        while ( $this->hasRightBorder($sheet, $w, $rx)      || $this->hasBottomBorder($sheet, $w, $rx) 
             || $this->hasRightBorder($sheet, $w-1, $rx)    || $this->hasBottomBorder($sheet, $w, $rx-1) )
        {
            $w++;    
        }
        $w--;
        
        $c = $w - 1;
        while ( $this->hasRightBorder($sheet, $c, $rx+$h)   || $this->hasBottomBorder($sheet, $c, $rx+$h)
             || $this->hasRightBorder($sheet, $c-1, $rx+$h) || $this->hasBottomBorder($sheet, $c, $rx+$h-1))
        {
            $h++;
        }
        $h--;
        return array('width' => $w, 'height' => $h);
    }
    
    
    // === Проверить целостность границ таблицы

    protected function validateTableBorders($sheet, $rx, $w, $h)
    {
        // проверяем правую границу
        for ( $r = $rx; $r < $rx + $h; $r++ )  
            if ( ! $this->hasRightBorder($sheet, $w - 1, $r) )            
                throw new Exception("Нарушена целостность правой границы на строке $r");   
                //return false;
            
        // проверяем нижнюю границу
        for ( $c = 0; $c < $w; $c++ )
            if ( ! $this->hasBottomBorder($sheet, $c, $rx + $h - 1) )
                throw new Exception("Нарушена целостность нижней границы в столбце $c");  
                //return false;            
        
        return true;
    }
    
    
    // === Препроцессинг таблицы
    // удаляет все невидимые строки и столбцы, а также сносит плашки первой и второй недель
    
    protected function cleanupTable($sheet, $rx, &$w, &$h)
    {
        // избавляемся от пустых столбцов
        for ( $c = 0; $c < $w; $c++ )
        {
            if ( ! $sheet->getColumnDimensionByColumn($c)->getVisible() ) {
                $sheet->removeColumnByIndex($c);
                $w--;
            }
        }
        
        // избавляемся от пустых строк
        for ( $r = $rx; $r < $rx + $h; $r++ )
            if ( ! $sheet->getRowDimension($r)->getVisible() ) {
                $sheet->removeRow($r);
                $h--;
            }
        
        // сносим плашки первой и второй недель
        for ( $r = $rx + 1; $r < $rx + $h; $r++ )
        {
            $cellColor = $sheet->getCellByColumnAndRow(1, $r)->getStyle()->getFill()->getStartColor()->getRGB();            
            $currentCellIsNotWhite = $cellColor !== "FFFFFF";
            $currentCellIsNotTransparent = $cellColor !== "000000";
            if ( $currentCellIsNotWhite && $currentCellIsNotTransparent ) {     
                $sheet->removeRow($r, 1);                
                $h--;
            }
        }
    }
    
    
    // === Распознать группы
    
    protected function exploreGroups($sheet, $cx, $tableWidth, $rx, $groupWidth)
    {
        $groups = array();
        
        for ( $c = $cx; $c < $tableWidth; $c += $groupWidth )
        {   
            $groupName = trim($sheet->getCellByColumnAndRow($c, $rx));            
            
            if ( !preg_match('/В[А-Я]{1,3}-(\d|\d{3})/u', $groupName) )
                throw new Exception('Неверное название группы: "' . $groupName . '"');
            
            $groups[] = $groupName;   
        }        
        return $groups;
    }
    
    
    // === Взаимодополнить поля двух занятий
   
    protected function crossFillItems($m1, $m2) {
        $basis = array('discipline', 'type', 'room', 'lecturer', 'dates', 'time');
        foreach ( $basis as $el ) {
            if ( empty($m1->$el) ) $m1->$el = $m2->$el;
            else if ( empty($m2->$el) ) $m2->$el = $m1->$el;
        }
        $comment = trim($m1->comment . ' ' . $m2->comment);
        $m1->comment = $comment;
        $m2->comment = $comment;      
    }
    
    
    
    
}