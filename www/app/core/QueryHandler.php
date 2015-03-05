<?

class QueryHandler extends Handler implements IStatus {

    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCurrentActivity()
    {
        $data = array();
        $query = "
            SELECT
                DATE_FORMAT(t.date,'%d.%m') AS date, g.name AS `group`, d.name AS discipline, r.name AS room,
                CONCAT(l.surname, ' ', SUBSTR(l.name, 1, 1), '. ', SUBSTR(l.patronymic, 1, 1), '.') AS lecturer,
                t.offset, t.type, t.comment
            FROM timetable AS t
            LEFT JOIN groups AS g ON t.id_group = g.id
            LEFT JOIN disciplines AS d ON t.id_discipline = d.id
            LEFT JOIN rooms AS r ON t.id_room = r.id
            LEFT JOIN lecturers AS l ON t.id_lecturer = l.id
            WHERE t.date BETWEEN NOW() AND NOW() + INTERVAL 2 DAY
            ORDER BY t.date";

        $stmt = $this->pdo->query($query);

        $code = 'ok';
        if ( $stmt === false )
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

?>