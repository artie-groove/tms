<?
class Upload
{
	private $uploadPath = '/files/';
	private $mimeFileType = array('xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //private $mimeFileType = array('xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	private $maxUploadFileSize = 1; // MB
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

        // Функции серии finfo_* требуют особой настройки php:
        // во-первых, в php.ini нужно раскоментировать строку php_fileinfo.dll
        // во-вторых, позаботиться о том, чтобы эта библиотека была в наличии в папке php/ext на сервере
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $File['tmp_name']);
        finfo_close($finfo);

		if ( !in_array( $mime, $this->mimeFileType ) )
		{
			$this->setRezult('status', 'Error');
			//$this->setRezult('details', 'Загруженный файл не соответствует формату xlsx. File type: ' . $File['type']);
            $this->setRezult('details', 'Принимаются только файлы Microsoft Office Excel (xlsx). Загруженный файл имеет тип: ' . $mime);
            return false;
		}

		
		if ( $File['size']>=($this->maxUploadFileSize*1024*1024) ) // размер файла >= 1 MB
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Максимально допустимый размер загружаемого файла 1 мегабайт.');
			return false;
		}
		
		$this->uploadFileName = $_SERVER['DOCUMENT_ROOT'].$this->uploadPath.date("ymdHi").".xlsx";
		
		if ( !rename($File['tmp_name'], $this->uploadFileName) )
		{
			$this->setRezult('status', 'Error');
			$this->setRezult('details', 'Ошибка при загрузке файла '.$File['name'].' в директорию '.$this->uploadPath.'.');
			return false;
		}
		
		$this->setRezult('status', 'Файл успешно загружен на сервер');
		return true;
	}
}
?>