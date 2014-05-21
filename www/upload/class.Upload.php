<?
class Upload
{
	private $uploadPath = '/files/';
	private $mimeFileType = array('xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	private $maxUploadFileSize = 1;
	private $uploadFileName;
	protected $rezult;
	
	private function setRezult($name, $message)
	{
		$this->rezult[$name] = $message;
	}
	
	public function getRezult()
	{
		return json_encode($this->rezult);
	}
	
	public function uploadFile($File)
	{
		if ( !is_uploaded_file($File['tmp_name']) )
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Файл не загружен на сервер.');
			return false;
		}
		
		if ( strcmp($File['type'], $this->mimeFileType['xlsx'])!=0 )
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Загруженый файл не соответствует формату xlsx.');
			return false;
		}
		
		if ( $File['size']>=($this->maxUploadFileSize*1024*1024) ) // размер файла >= 1Mb
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Максимальный допустимый размер загружаемого файла 1 мегабайт.');
			return false;
		}
		
		$this->uploadFileName = $_SERVER['DOCUMENT_ROOT'].$this->uploadPath.date("ymdHi").".xlsx";
		
		if ( !rename($File['tmp_name'], $this->uploadFileName) )
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Ошибка при загрузке файла '.$File['name'].' в директорию '.$this->uploadPath.'.');
			return false;
		}
		
		$this->setRezult('status', 'Ok');
		return true;
	}
}
?>