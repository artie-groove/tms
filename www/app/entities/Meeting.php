<?
    class Meeting
    {
        public $dates;
        public $offset;
        public $discipline;        
        public $type;
        public $group;
        public $room;        
        public $lecturer;
        public $comment;
        
        public function __construct($dates = '', $offset = '', $room = '',
            $discipline = '', $type = '', $lecturer = '', $group = '', $comment = '') {
            $fields = array_keys(get_class_vars(__CLASS__));
            foreach ( $fields as $f )
                $this->$f = $$f;
            /*
            $this->date = $date;
            $this->offset = $offset;
            $this->room = $room;
            $this->discipline = $discipline;
            $this->type = $type;
            $this->lecturer = $lecturer;
            $this->group = $group;
            $this->comment = $comment;    
            */
        }
        
        public function copyFrom($original)
        {
            $fields = array_keys(get_class_vars(__CLASS__));
            foreach ( $fields as $f )
                $this->$f = $original->$f;
            /*
            $this->date = $original->date;
            $this->offset = $original->offset;
            $this->room = $original->room;
            $this->discipline = $original->discipline;
            $this->type = $original->type;
            $this->lecturer = $original->lecturer;
            $this->group = $original->group;
            $this->comment = $original->comment;
            */
        }
        
        public function initFromArray($src)
        {
            $fields = array_keys(get_class_vars(__CLASS__));
            foreach ( $fields as $f )
                $this->$f = $src[$f];
        }
    }