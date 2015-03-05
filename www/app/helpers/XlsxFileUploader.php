<?
class XlsxFileUploader extends Handler implements IStatus
{
	private $uploadPath = '/files/';
	private $mimeFileType = array('xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	private $maxUploadFileSize = 1; // MB
	private $uploadFileName;
	
	public function getFullFileName()
	{
		return $this->uploadFileName;
	}
	
	public function uploadFile()
	{        
        $formFileName = 'data_xlsx';
        try
        {
            if ( !isset($_FILES[$formFileName]) )
            {
                $this->setStatus('Error', 'Ошибка приёма файла');
                return false;
            }
            
            $uploadedFileInfo = $_FILES[$formFileName];
            
            if ( !is_uploaded_file($uploadedFileInfo['tmp_name']) )
            {
                $this->setStatus('Error', 'Файл не загружен на сервер.');
                return false;
            }

            // Закомментировано by Mednopers 27.05.14
            // Функции серии finfo_* требуют особой настройки php:
            // во-первых, в php.ini нужно раскоментировать строку php_fileinfo.dll
            // во-вторых, позаботиться о том, чтобы эта библиотека была в наличии в папке php/ext на сервере
            /*$finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ( !in_array( $mime, $this->mimeFileType ) )
            {
                $this->setStatus('status', 'Error');
                //$this->setStatus('details', 'Загруженный файл не соответствует формату xlsx. File type: ' . $file['type']);
                $this->setStatus('details', 'Принимаются только файлы Microsoft Office Excel (xlsx). Загруженный файл имеет тип: ' . $mime);
                return false;
            }*/


            if ( $uploadedFileInfo['size']>=($this->maxUploadFileSize * 1024 * 1024) ) // размер файла >= 1 MB
            {
                $this->setStatus('Error', 'Максимально допустимый размер загружаемого файла 1 мегабайт.');
                return false;
            }

            date_default_timezone_set('UTC');
            //$this->uploadFileName = realpath($_SERVER['DOCUMENT_ROOT'] . '/../' . $this->uploadPath) . '/' . date("ymdHis") . ".xlsx";
            
            //file_put_contents('debug.log', $this->uploadFileName . ' and tmp name = ' . $file['tmp_name']);

            /*
            if ( !rename($file['tmp_name'], $this->uploadFileName) )
            {
                $this->setStatus('Error', 'Ошибка при загрузке файла ' . $file['name'] . ' в директорию ' . $this->uploadPath);
                return false;
            }
            */
            
            $this->uploadFileName = $uploadedFileInfo['tmp_name'];

            $this->setStatus('ok', 'Файл успешно загружен на сервер');
            return true;        
	    }
        catch (Exception $e)
        {
            $this->setStatus('error', 'XlsxFileUploader Exception', $e->getLine() . ' ' . $e->getMessage());
            return false;
        }
    }
}
?>