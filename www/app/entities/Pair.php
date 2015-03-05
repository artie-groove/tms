<?
    class Pair
    {
        public $Date;
        public $ParNumber;
        public $Predmet;        
        public $Type;
        public $Group;
        public $Auditoria;        
        public $Prepod;
        public $Comment;
        
        public function __construct($date = '', $offset = '', $room = '',
            $discipline = '', $type = '', $lecturer = '', $group = '', $comment = '') {
            
            $this->Date = $date;
            $this->ParNumber = $offset;
            $this->Auditoria = $room;
            $this->Predmet = $discipline;
            $this->Type = $type;
            $this->Prepod = $lecturer;
            $this->Group = $group;
            $this->Comment = $comment;            
        }
        
        public function copy($old)
        {
            $this->Predmet = $old->Predmet;
            $this->Prepod = $old->Prepod;
            $this->Type= $old->Type;
            $this->Auditoria= $old->Auditoria;
            $this->ParNumber= $old->ParNumber;
            $this->Date= $old->Date;
            $this->Comment= $old->Comment;
            $this->Group= $old->Group;
        }
    }
?>