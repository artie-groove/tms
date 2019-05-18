<?php


class HarvesterFactory
{
    public function getHarvester($table)
    {
        $type = $this->fetchType($table->getCaption());
        if ( !$type )
            throw new Exception("Неизвестный тип таблицы (лист &laquo;{$table->sheet->getTitle()}&raquo;)");

//         $customHarvesterTypes = array('Evening', 'PostalTutorials', 'Secondary');
//         $harvesterType = in_array($type, $customHarvesterTypes) ? $type : 'Fulltime';
        $harvesterClass = 'Harvester' . $type;
        
        return new $harvesterClass($table);
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