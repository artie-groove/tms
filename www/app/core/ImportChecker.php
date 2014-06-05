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
        SELECT
            t.id, t.date, g.name AS `group`, d.name AS discipline, r.name AS room,
            CONCAT(l.surname, ' ', SUBSTR(l.name, 1, 1), '. ', SUBSTR(l.patronymic, 1, 1), '.') AS lecturer,
            t.offset, t.type
        FROM timetable AS t
        LEFT JOIN groups AS g ON t.id_group = g.id
        LEFT JOIN disciplines AS d ON t.id_discipline = d.id
        LEFT JOIN rooms AS r ON t.id_room = r.id
        LEFT JOIN lecturers AS l ON t.id_lecturer = l.id
        WHERE t.date IS NULL
            OR t.id_group IS NULL
            OR t.id_discipline IS NULL
            OR t.id_room IS NULL
            OR t.id_lecturer IS NULL";

        $stmt = $this->pdo->query($query);

        $code = 'ok';
        if ( $this->pdo->errorCode() != '00000' )
        {
            $this->setStatus('error', implode(' ', $this->pdo->errorInfo()));
            return false;
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setStatus('ok', 'Сверка произведена успешно', $data);

        return true;
    }
} 