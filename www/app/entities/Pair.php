<?
    class Pair  //Pair
    {
        public $Predmet;
        public $Prepod;
        public $Type;
        public $Auditoria;
        public $ParNumber;
        public $Date;
        public $Comment;
        public $Group;

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