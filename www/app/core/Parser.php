<?

class Parser extends TableHandler
{
    const MAX_PROBE_DEPTH = 15; // количество строк при "прощупывании" верхней границы таблицы
    const MAX_PROBE_WIDTH = 5;  // количество столбцов при "прощупывании" левой границы таблицы
    const MAX_WIDTH = 120;      // максимальное количество столбцов, формирующих таблицу
    
    private $PHPExcel;

    
    // === Зондировать лист на предмет наличия таблицы
    // если найдена граница в верхней части листа, возвращает номер строки
    
    private function probeTable($sheet)
    {
        $c = 0;
        for ( $r = 1; $r < self::MAX_PROBE_DEPTH; $r++ ) {
            $reachedBottomBorder = $this->hasBottomBorder($sheet, 0, $r);
            if ( $reachedBottomBorder ) return array($c, $r+1); 
        }
        
        for ( $c = 0; $c < self::MAX_PROBE_WIDTH; $c++ ) {
            $reachedLeftBorder = $this->hasRightBorder($sheet, $c, $r);
            if ( $reachedLeftBorder ) {
                while ( $r >= 1 ) {
                    $r--;
                    $cantSeeBorderAnymore = !$this->hasRightBorder($sheet, $c, $r);
                    if ( $cantSeeBorderAnymore && $this->hasBottomBorder($sheet, $c+1, $r) ) {
                        return array($c+1, $r+1);
                    }
                }
                break;
            }
        }
        
        return false;
    }
    
    
    // === Получить заголовок таблицы таблицы
    private function getTableCaption($sheet, $bottomRow)
    {        
        $caption = '';
        for ( $r = 1; $r < $bottomRow; $r++ )
            for ( $c = 0; $c < self::MAX_WIDTH; $c++)
                $caption .= $sheet->getCellByColumnAndRow($c, $r);
        
        return $caption;
    }
    
    
    // === Запустить анализ файла расписания
    
    public function run($filename)
    {        
        /**  Identify the type of $filename  **/
        $inputFileType = PHPExcel_IOFactory::identify($filename);
        /**  Create a new Reader of the type that has been identified  **/
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        /**  Load $inputFileName to a PHPExcel Object  **/
        $this->PHPExcel = $objReader->load($filename);

        
//         $this->PHPExcel = PHPExcel_IOFactory::load($filename);
        $storage = array();
        $sheetsTotal = $this->PHPExcel->getSheetCount();
        for ( $s = 0; $s < $sheetsTotal; $s++ )
        {
            $sheet = $this->PHPExcel->getSheet($s);
            
            $coords = $this->probeTable($sheet);
            if ( ! $coords ) break;
            list ( $cx, $rx ) = $coords;
            
            $caption = $this->getTableCaption($sheet, $rx);
            
            $harvesterFactory = new HarvesterFactory();
            $harvester = $harvesterFactory->getHarvester($caption, $sheet, $cx, $rx);
            $data = $harvester->run();
           
            $storage[] = array(
                'type' => $harvester->getType(),
                'data' => $data);
        }
        return $storage;
    }
    
}