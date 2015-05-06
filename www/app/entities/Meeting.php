<?

class Meeting
{
    public $dates;
    public $time;
    public $discipline;        
    public $type;
    public $group;
    public $room;        
    public $lecturer;
    public $comment;
    public $offset;

    public function __construct($params = null) {
        if ( !is_array($params) || count($params) === 0 ) return;
        $fields = array( 'dates', 'time', 'room', 'discipline', 'type', 'lecturer', 'group', 'comment' );
        foreach ( $fields as $i => $f )
            $this->$f = isset($params[$i]) ? $params[$i] : null;
    }

    public function copyFrom($original)
    {
        //$fields = array_keys(get_class_vars(__CLASS__));
        $fields = array( 'dates', 'time', 'room', 'discipline', 'type', 'lecturer', 'group', 'comment' ); 
        foreach ( $fields as $f )
            $this->$f = $original->$f;
    }

    public function initFromArray($src)
    {
        //$fields = array_keys(get_class_vars(__CLASS__));
        $fields = array( 'dates', 'time', 'discipline', 'type', 'group', 'room', 'lecturer', 'comment' ); 
        foreach ( $fields as $f )
            if ( array_key_exists($f, $src) ) $this->$f = $src[$f];
    }
    
    public function __get($property)
    {
           
    }
}