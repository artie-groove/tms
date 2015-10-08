<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.06.14
 * Time: 2:32
 */

/*
require_once 'interfaces.php';
require_once 'Handler.php';
*/

class ImportChecker extends Handler implements IStatus {

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function check()
    {
        $data = array();
               
        $query = "
            SELECT DISTINCT
                COUNT(*) AS n, g.name AS `group`, d.name AS discipline,
                IF(t.id_room = 0, 0, r.name) AS room,
                IF(t.id_lecturer = 0, 0, CONCAT(l.surname, ' ', SUBSTR(l.name, 1, 1), '. ', SUBSTR(l.patronymic, 1, 1), '.')) AS lecturer,
                TIME_FORMAT(t.`time`, '%k:%i') AS time, t.type, t.comment, t.debug
            FROM timetable_stage AS t
            LEFT JOIN groups AS g ON t.id_group = g.id
            LEFT JOIN disciplines AS d ON t.id_discipline = d.id
            LEFT JOIN rooms AS r ON t.id_room = r.id
            LEFT JOIN lecturers AS l ON t.id_lecturer = l.id
            WHERE t.date IS NULL
                OR t.id_group IS NULL
                OR t.id_discipline IS NULL
                OR ( t.id_room IS NULL AND t.comment NOT LIKE '%@%' )
                /* OR t.id_room IS NULL */
                OR t.id_lecturer IS NULL
                OR t.type IS NULL
                OR t.time IS NULL
            GROUP BY t.id_discipline, t.id_group, t.id_lecturer, t.id_room, t.`time`, t.type 
            ORDER BY t.`date`";
        
        
        /*
        $query = "
            SELECT
                DATE_FORMAT(t.date,'%d.%m') AS date, g.name AS `group`, d.name AS discipline, r.name AS room,
                CONCAT(l.surname, ' ', SUBSTR(l.name, 1, 1), '. ', SUBSTR(l.patronymic, 1, 1), '.') AS lecturer,
                t.time, t.type, t.comment
            FROM timetable_stage AS t
            LEFT JOIN groups AS g ON t.id_group = g.id
            LEFT JOIN disciplines AS d ON t.id_discipline = d.id
            LEFT JOIN rooms AS r ON t.id_room = r.id
            LEFT JOIN lecturers AS l ON t.id_lecturer = l.id
            WHERE t.date IS NULL
                OR t.id_group IS NULL
                OR t.id_discipline IS NULL
                OR t.id_room IS NULL
                OR t.id_lecturer IS NULL
            ORDER BY t.date";
        */
        
        
        $stmt = $this->pdo->query($query);

        $code = 'ok';
        if ( $this->pdo->errorCode() != '00000' )
        {
            $this->setStatus('error', implode(' ', $this->pdo->errorInfo()));
            return false;
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ( count($data) )
        {
            $description = 'Расписание успешно загружено, однако некоторые элементы распознать не удалось. Пожалуйста, исправьте исходный документ и загрузите файл заново';
            $this->setStatus('ok', $description, $data);
            return false;
        }
        
        $description = 'Расписание успешно загружено';
        $this->setStatus('ok', $description);

        return true;
    }
} 