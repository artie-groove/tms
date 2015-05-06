<?

class Parser extends TableHandler
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_WIDTH = 120;      // максимальное количество столбцов, формирующих таблицу
    
    private $PHPExcel;

    
    // === Зондировать лист на предмет наличия таблицы
    // если найдена граница в верхней части листа, возвращает номер строки
    
    private function probeTable($sheet)
    {
        for ( $r = 1; $r < self::MAX_PROBE_DEPTH; $r++ )
        {
            $reachedBottomBorder = $this->hasBottomBorder($sheet, 0, $r);
            if ( $reachedBottomBorder ) return $r + 1;
        }
        return false;
    }
    
    
    // === Определить тип таблицы
    private function getTableType($sheet, $bottomRow)
    {        
        $caption = '';
        for ( $r = 1; $r < $bottomRow; $r++ )
            for ( $c = 0; $c < self::MAX_WIDTH; $c++)
                $caption .= $sheet->getCellByColumnAndRow($c, $r);
        
        $caption = preg_replace('/\s/u', '', $caption);
        $caption = mb_strtolower($caption);
        
        $matches = array();
        $pattern = '/расписание(занятий|консультаций|сессии).*(инженерно|автомеханического|вечернего|заочного)/u';
        
        if ( preg_match($pattern, $caption, $matches) )
        {
            switch ( $matches[1] )
            {
                case 'занятий':
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                            return 'Fulltime';
                        
                        case 'вечернего':
                            return 'Evening';
                        
                        default:
                            return false;
                    }
                
                case 'консультаций':
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                        case 'вечернего':
                            return 'BasicTutorials';
                        
                        case 'заочного':
                            return 'PostalTutorials';
                        
                        default:
                            return false;
                    }
                
                case 'сессии':
                    switch ( $matches[2] )
                    {
                        case 'инженерно':
                        case 'автомеханического':
                        case 'вечернего':
                            return 'BasicSession';
                        
                        case 'заочного':
                            return 'PostalSession';
                        
                        default:
                            return false;
                    } 
                
                default: return false;
            }
        }
        return false;
    }

    
    // === Запустить анализ файла расписания
    
    public function run($filename)
    {        
        $this->PHPExcel = PHPExcel_IOFactory::load($filename);
        $storage = array();
        $sheetsTotal = $this->PHPExcel->getSheetCount();
        for ( $s = 0; $s < $sheetsTotal; $s++ )
        {
            $sheet = $this->PHPExcel->getSheet($s);
            
            $rx = $this->probeTable($sheet);           
            if ( ! $rx ) break;
            
            $tableType = $this->getTableType($sheet, $rx);
            $harvesterClass = 'Harvester' . $tableType;
            $harvester = new $harvesterClass($sheet, $rx);
            $data = $harvester->run();
            $storage[] = array('type' => $tableType, 'data' => $data);
        }
        return $storage;
    }
    
}