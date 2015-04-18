<?php

/**
 * Description of newPHPClass
 *
 * @author tema4ka
 */
class TestParser {
    
    private $parser;
    private $data;
    public $results;
    
    public function __construct($parser) {
        $this->parser = $parser;    
    }
    
    public function run() {
        if ( !$this->tableLayoutRecognized() ) {
            return false;
        }
        $allTestsPassed = true;
        $allTestsPassed &= $this->groupsRecognized();
        $allTestsPassed &= $this->genericCellsRecognized();
        $allTestsPassed &= $this->physicalEducationCellsRecognized();
        $allTestsPassed &= $this->customDateCellsRecognized();
        $allTestsPassed &= $this->doubleLineTitleRecognized();
        $allTestsPassed &= $this->parallelDisciplinesRecognized();   
        $allTestsPassed &= $this->complexDisciplinesRecognized();
        
        return $allTestsPassed ? true : false;
    }
    
    private function report($test, $result, $description = null) {
        $this->results[] = array('test' => $test, 'result' => $result, 'description' => $description);
    }
    
    private function tableLayoutRecognized() {
        $title = 'Распознавание контуров таблицы';
        $fileToParse = $_SERVER['DOCUMENT_ROOT'] . '/examples/fei4_140213.xlsx';
        if ( !$this->parser->parsing($fileToParse) ) {
            $code = $this->parser->getStatusCode();
            $descr = $this->parser->getStatusDescription();
            $details = $this->parser->getStatusDetails();
            $this->report($title, false, $code . ': ' . $descr . ' (' . $details . ')');
            return false;
        }

        $this->data = $this->parser->getParseData();
        $this->report($title, true);
        return true;
    }
    
    private function groupsRecognized() {
        $title = 'Распознавание групп';
        $referenceGroupList = array('ВХТ-401', 'ВВТ-406', 'ВЭ-411', 'ВЭМ-413', 'ВЭМ-5', 'ВТПЭ-5');
        $groupList = array();
        foreach ( $this->data as $pair )
        {
            if ( !in_array($pair->group, $groupList) ) $groupList[] = $pair->group;
        }
        $n = count($referenceGroupList);
        $intersection = array_intersect($referenceGroupList, $groupList);
        $areListsIdentical = count($intersection) == $n;
        if ( !$areListsIdentical ) {
            $diffList = array_diff($referenceGroupList, $groupList);
            $diff = implode(', ', $diffList);
            $groups = implode(', ', $groupList);
            $this->report($title, false, "Not recognized: ${diff}. Parser supplied the following groups: ${groups}");
            return false;
        }
        $this->report($title, true);
        return true;
    }
    
    private function findMatch($title, array $items) {
        $isTestPassed = true;
        foreach ( $items as $item ) {
            list ( $id, $pairFromTest ) = $item;
            $pairFromParser = $this->data[$id];
            $isMatch = $pairFromParser == $pairFromTest;
            $isTestPassed &= $isMatch;
            if ( !$isMatch ) {                
                $delim = ' = ';
                $strPairFromParser = implode($delim, get_object_vars($pairFromParser));
                if ( empty($strPairFromParser) ) $strPairFromParser = '(пустая строка)';
                $strPairFromTest = implode($delim, get_object_vars($pairFromTest));
                $this->report($title, false, "<pre>${strPairFromTest} (original)\n${strPairFromParser} (parsed)</pre>");
            }
        }
        return $isTestPassed ? true : false;
    }
    
    private function genericCellsRecognized() {
        $title = 'Распознавание обычных ячеек';
        $items = array();
        $items[] = array(1, new Meeting('17.02,3.03,17.03,31.03,14.04,28.04,12.05,26.05,9.06', '2', 'А-12', 'АТПП', 'лек.', 'Чичилин', 'ВХТ-401'));
        $items[] = array(127, new Meeting('21.02,7.03,21.03,4.04,18.04,2.05,16.05,30.05,13.06', '5', 'В-206', 'Экономика недвижимости', 'лек.', 'Иевлева', 'ВЭМ-413'));
        $items[] = array(149, new Meeting('22.02,8.03,22.03,5.04,19.04,3.05,17.05,31.05,14.06', '2', 'В-111', 'Деловой ин.яз.', 'пр.', 'Хван', 'ВЭМ-5'));
        $items[] = array(74, new Meeting('14.02,28.02,14.03,28.03,11.04,25.04,9.05,23.05,6.06', '2', 'В-209', 'Теор.планир.эксп.', 'лаб.', 'Короткова', 'ВВТ-406', '1п/г 2п/г'));
        $items[] = array(69, new Meeting('13.02,27.02,13.03,27.03,10.04,24.04,8.05,22.05,5.06', '1', 'Б-104', 'БЖД', 'лек.', 'Александрина', 'ВВТ-406'));
        $items[] = array(61, new Meeting('11.02,25.02,11.03,25.03,8.04,22.04,6.05,20.05,3.06', '1', 'В-206', 'Вып.выпуск.раб.', 'лек.', 'Рыбанов', 'ВВТ-406'));
                
        if ( !$this->findMatch($title, $items) ) return false;                
        $this->report($title, true);
        return true;
    }
    
    private function physicalEducationCellsRecognized() {
        $title = 'Распознавание физической культуры';
        $items = array();
        $items[] = array(4, new Meeting('18.02,4.03,18.03,1.04,15.04,13.05,27.05,10.06', '1', '', 'Физическая культура', '', 'Хаирова', 'ВХТ-401'/*, '13.00-14.30'*/));
        $items[] = array(5, new Meeting('18.02,4.03,18.03,1.04,15.04,13.05,27.05,10.06', '2', '', 'Физическая культура', '', 'Хаирова', 'ВХТ-401'/*, '13.00-14.30'*/));
                
        if ( !$this->findMatch($title, $items) ) return false;                
        $this->report($title, true);
        return true;
    }
    
    private function customDateCellsRecognized() {
        $title = 'Распознавание ячеек со встроенными датами';
        $items[] = array(150, new Meeting('24.02, 24.03, 21.04, 19.05', '4', 'В-204', 'Теория кризисного управления', 'пр.', 'Гаврилова', 'ВЭМ-5'));
        if ( !$this->findMatch($title, $items) ) return false;
        $this->report($title, true);
        return true;
    }
    
    private function doubleLineTitleRecognized() {
        $title = 'Распознавание двухстрочных дисциплин';
        // с пробелом после буквы "в"
        $items[] = array(163, new Meeting('18.02,4.03,18.03,1.04,15.04,13.05,27.05,10.06', '2', 'А-29', 'Комп.мет.и инф.сист.в техн.синт. пер.полим.', 'лаб.', 'Александрина', 'ВТПЭ-5'));
        if ( !$this->findMatch($title, $items) ) return false;
        $this->report($title, true);
        return true;
    }
    
    private function parallelDisciplinesRecognized() {
        $title = 'Распознавание дисциплин по подгруппам';
        $items[] = array(13, new Meeting('20.02,6.03,20.03,3.04,17.04,1.05,15.05,29.05,12.06', '2', 'Б-306', 'Осн.терм.и кин. синтеза ВМС', 'лаб.', 'Пучков', 'ВХТ-401', '1/2 п/г'));
        $items[] = array(15, new Meeting('20.02,6.03,20.03,3.04,17.04,1.05,15.05,29.05,12.06', '2', 'Б-309', 'БЖД', 'лаб.', 'Шиповский', 'ВХТ-401', '2/1 п/г'));
        if ( !$this->findMatch($title, $items) ) return false;
        $this->report($title, true);
        return true;
    }
    
    private function complexDisciplinesRecognized() {
        $title = 'Распознавание сложных дисциплин (подгруппы, эксплицитные даты, множественные преподаватели и аудитории и т. д.)';
        $items[] = array(174, new Meeting('14.02,14.03,11.04,6.06', '2', 'Б-008', 'Теор. и эксп. мет. иссл. в химии', 'лаб.', 'Новопольцева', 'ВТПЭ-5', ', 009'));
        $items[] = array(177, new Meeting('28.02,28.03,25.04,23.05', '3', 'Б-008', 'Рецептуростр. полим. композ', 'лаб.', 'Новопольцева', 'ВТПЭ-5', ', 009'));
        $items[] = array(100, new Meeting('23.03,6.04', '0', 'А-32', 'Хим.реакторы', 'лек.', 'Бутов', 'ВЭ-411'));
        $items[] = array(104, new Meeting('13.02,27.02,13.03,27.03,10.04,24.04,8.05,22.05,5.06', '4', 'Б-207', 'Химия', 'лаб.', 'Перевалова', 'ВЭ-411', '1 п/г'));
        $items[] = array(102, new Meeting('13.02,27.02,13.03,27.03,10.04,24.04,8.05,22.05,5.06', '3', 'А-16', 'Комп.графика', 'лаб.', 'Саразов А.В.', 'ВЭ-411'));
        $items[] = array(32, new Meeting('18.02,18.03,15.04,13.05', '0', 'Д-202', 'Концепц.совр.естествозн', 'лек.', 'Перевалова', 'ВХТ-401'));
        $items[] = array(34, new Meeting('4.03,1.04,29.04,27.05', '0', 'Б-207', 'Концепц.совр.естествозн', 'пр', 'Перевалова', 'ВХТ-401'));
        $items[] = array(37, new Meeting('21.02,21.03,18.04', '3', 'В-111', 'Управление снабж.и сбытом', 'лек.', 'Чеботарев', 'ВХТ-401', 'по 3ч.'));
        $items[] = array(38, new Meeting('7.03,4.04,16.05,30.05', '2', 'В-111', 'Управление снабж.и сбытом', 'пр.', 'Чеботарев', 'ВХТ-401', 'по 3ч.'));
        if ( !$this->findMatch($title, $items) ) return false;
        $this->report($title, true);
        return true;
    }
    
    public function printDump() {
        echo '<h2>Full parser output dump</h2>';
        echo '<table width="100%">';
        $id = -1;
        foreach ( $this->data as $pair ) {
            $id++;            
            $date = $pair->dates;
            $type = $pair->type;
            $lecturer = $pair->lecturer;
            $offset = $pair->offset;
            $discipline = $pair->discipline;
            $room = $pair->room;
            $group = $pair->group;
            $comment = $pair->comment;
            echo "<tr><td>${id}</td><td>${date}</td><td>${offset}</td><td>${discipline}</td><td>${type}</td><td>${group}</td><td>${room}</td><td>${lecturer}</td><td>${comment}</td></tr>";
        }
        echo '</table>';
    }
}

?>