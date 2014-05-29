<?

interface IStatus 
{
	public function getStatusCode(); // возвращает код статуса (ok, error)
	public function getStatusDescription(); // описание статуса
	public function getStatusDetails(); // дополнительная информация (детали)
}

?>