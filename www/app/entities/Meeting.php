<?
    class Meeting
    {
        public $date;
        public $offset;
        public $discipline;        
        public $type;
        public $group;
        public $room;        
        public $lecturer;
        public $comment;
        
        public function __construct($date = '', $offset = '', $room = '',
            $discipline = '', $type = '', $lecturer = '', $group = '', $comment = '') {
            
            $this->date = $date;
            $this->offset = $offset;
            $this->room = $room;
            $this->discipline = $discipline;
            $this->type = $type;
            $this->lecturer = $lecturer;
            $this->group = $group;
            $this->comment = $comment;            
        }
        
        public function copyFrom($original)
        {
            $this->date = $original->date;
            $this->offset = $original->offset;
            $this->room = $original->room;
            $this->discipline = $original->discipline;
            $this->type = $original->type;
            $this->lecturer = $original->lecturer;
            $this->group = $original->group;
            $this->comment = $original->comment;
        }
    }
?>