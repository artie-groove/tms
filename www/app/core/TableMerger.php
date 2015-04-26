<?php

class TableMerger extends Handler implements IStatus {

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function merge()
    {
        $data = array();
        $query = "
            SELECT DISTINCT ts.id_group
            FROM `timetable_stage` AS ts
            INNER JOIN `timetable` AS t
            ON ts.id_group = t.id_group";

        $stmt = $this->pdo->query($query);

        $code = 'ok';
        if ( $this->pdo->errorCode() != '00000' )
        {
            $this->setStatus('error', implode(' ', $this->pdo->errorInfo()));
            return false;
        }

        $data = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);        
        if ( count($data) )
        {
            $group_id_cs_list = implode(',', $data);
            $query = "
                DELETE FROM `timetable`
                WHERE id_group IN ($group_id_cs_list)";
            
            if ( $this->pdo->exec($query) === FALSE )
            {
                $this->setStatus('error', 'Ошибка при удалении существующих записей из таблицы с расписанием',                                  implode(', ', $this->pdo->errorInfo()) . ' / ' . $group_id_cs_list);
                return false;
            }
        }
        
        $query = "
            INSERT INTO timetable (id_discipline, id_group, id_lecturer, id_room, `time`, `date`, `type`, `comment`)
            SELECT id_discipline, id_group, id_lecturer, id_room, `time`, `date`, `type`, `comment`
            FROM timetable_stage";
                                 
        if ( $this->pdo->exec($query) === FALSE )
        {
            $this->setStatus('error', 'Не удалось выполнить слияние таблиц', implode(', ', $this->pdo->errorInfo()));
            return false;
        }
               
        
        $query = "TRUNCATE TABLE timetable_stage";
        
        if ( $this->pdo->exec($query) === FALSE )
        {
            $this->setStatus('error', 'Ошибка очистки промежуточной таблицы', implode(', ', $this->pdo->errorInfo()));
            return false;
        }
                
                             
        $this->setStatus('ok', 'Таблицы успешно синхронизированы');

        return true;
    }
} 

?>