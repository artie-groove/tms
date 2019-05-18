<?php

class Parser extends TableHandler
{
    public $skipped;   
    private $PHPExcel;

  
    public function __construct()
    {
        $this->skipped = 0;
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
        
        $storage = array();
        $harvesterFactory = new HarvesterFactory();
        
        $sheetsTotal = $this->PHPExcel->getSheetCount();

        for ( $s = 0; $s < $sheetsTotal; $s++ )
        {
            $sheet = $this->PHPExcel->getSheet($s);

            $table = new Table($sheet);
            $tableIsInitialised = $table->init();
            if ( ! $tableIsInitialised ) {
                $this->skipped++;
                continue;
            }
            $harvester = $harvesterFactory->getHarvester($table);

            $harvester->run();
            $data = $harvester->getHarvest();
            $type = $harvester->getType();
           
            $storage[] = array(
                'type' => $type,
                'data' => $data
            );
        }
        return $storage;
    }
    
}