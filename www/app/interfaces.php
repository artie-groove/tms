<?

interface IStatus 
{
	public function getStatusCode(); // ���������� ��� ������� (ok, error)
	public function getStatusDescription(); // �������� �������
	public function getStatusDetails(); // �������������� ���������� (������)
}

?>