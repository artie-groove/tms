<?php


class HarvesterFactory
{
    public function getHarvester($caption, $sheet, $cx, $rx)
    {
        $type = $this->fetchType($caption);
        if ( !$type ) throw new Exception("Неизвестный тип таблицы (лист &laquo;{$sheet->getTitle()}&raquo;)");
        $customHarvesterTypes = array('PostalTutorials', 'Secondary');
        $harvesterType = in_array($type, $customHarvesterTypes) ? $type : 'Fulltime';
        $harvesterClass = 'Harvester' . $harvesterType;
        return new $harvesterClass($type, $sheet, $cx, $rx);
    }
    
    public function fetchType($caption)
    {
        $caption = preg_replace('/\s/u', '', $caption);
        $caption = mb_strtolower($caption);
    
        $matches = array();
        $pattern = '/расписание(занятий|консультаций|сессии).*(инженерно|автомеханического|вечернего|заочн(?:ого|ая|ое)|второго)/u';
        
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
                        
                        case 'заочного':
                        case 'заочная':
                        case 'заочное':
                            return 'PostalSession';
                        
                        case 'второго':
                            return 'Secondary';
                        
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
                        case 'заочная':
                        case 'заочное':                        
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
}

?>