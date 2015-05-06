<?php

/**
 * Description of newPHPClass
 *
 * @author tema4ka
 */
class TestHarvester {
    
    public $results;
    
    private $parser;
    private $harvest;
    private $type;
    private $section;
    
    public function __construct($parser, $type)
    {
        $this->parser = $parser;
        //$this->type = substr(__CLASS__, 13);
        $this->type = $type;
    }
    
    public function run()
    {
        $testFile = __DIR__ . '/Test' . $this->type . '.xlsx';
        $storage = $this->parser->run($testFile);
        $this->harvest = $storage[0]['data'];
        if ( empty($this->harvest) ) return false;
        
        $this->loadDataFromCsv();

        $allTestsPassed = true;
        
        foreach ( $this->sections as $section => $samples )
        {
            $allTestsPassed &= $passed = $this->findMatch($section, $samples);
            if ( $passed ) $this->report($section, true);
        }
      
        return $allTestsPassed ? true : false;
    }
    
    private function report($test, $result, $description = null) {
        $this->results[] = array('test' => $test, 'result' => $result, 'description' => $description);
    }
    
    protected function loadDataFromCsv()
    {
        $fileName = __DIR__ . '/Sample' . $this->type . '.csv';
        $rawData = file($fileName);
        foreach ( $rawData as $str ) {
            if ( $str{0} === "\n" ) continue;
            if ( $str{0} === '[' ) {
                $section = substr($str, 1, -2);
                continue;
            }
            $str = rtrim($str);
            $meetingData = explode('|', $str);
            $id = array_shift($meetingData);
            $m = new Meeting($meetingData);
            $this->sections[$section][] = array($id, new Meeting($meetingData));
        }
    }
   
    /*
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
    */
    
    private function findMatch($title, array $items) {
        $isTestPassed = true;
        foreach ( $items as $item ) {
            list ( $id, $sampleMeeting ) = $item;
            $harvestedMeeting = $this->harvest[$id];
            $isMatch = $harvestedMeeting == $sampleMeeting;
            $isTestPassed &= $isMatch;
            if ( !$isMatch ) {                
                $delim = '|';
                $strHarvestedMeeting = implode($delim, get_object_vars($harvestedMeeting));
                if ( empty($strHarvestedMeeting) ) $strHarvestedMeeting = '(пустая строка)';
                $strSampleMeeting = implode($delim, get_object_vars($sampleMeeting));
                $this->report($title, false, "<pre>{$strSampleMeeting} (original)\n{$strHarvestedMeeting} (parsed)</pre>");
            }
        }
        return $isTestPassed ? true : false;
    }
    
    
    public function printDump() {
        echo '<h2>Full parser output dump</h2>';
        echo '<table width="100%">';
        $id = -1;
        foreach ( $this->harvest as $pair ) {
            $id++;    
            $date = $pair->dates;
            $type = $pair->type;
            $lecturer = $pair->lecturer;
            $time = $pair->time;
            $discipline = $pair->discipline;
            $room = $pair->room;
            $group = $pair->group;
            $comment = $pair->comment;
            echo "<tr><td>${id}</td><td>${date}</td><td>${time}</td><td>${discipline}</td><td>${type}</td><td>${group}</td><td>${room}</td><td>${lecturer}</td><td>${comment}</td></tr>";
        }
        echo '</table>';
    }
}

?>