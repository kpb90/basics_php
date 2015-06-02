<?php
	/*
		������������ ������� ������� PHP
		��� ������������ ���������� ������� �������� �����, ����������� ������� ����� PHPUnit_Framework_TestCase.
		
		����� ������� � ���� ������ ��������� ������, ������������ �� ����� test.
		���������� ����� phpunit ��������������� ������� ��� �������� ������.

		��� ���������� ������� � ������ ��������� � ���������� ������������ ����, 
		� ������ PHPUnit_Framework_TestCase ������� ���������� ������ 
		setUp � tearDown, ������� ������ ����������. 
		��� ������ ���������� ����� � �����  ������� ���������� ��������� ������ ��������������.

		� �������� ������, ����������� PHPUnit_Framework_TestCase, ����� �������������� 
		��� ������ � ��������� ������������� ����� � ������ �������� ������ ��� � ���. 
		� ���������� ������������������ ������ ������� ��� �������� ������ ����� ���������:
		
		setUp()       {} // ���������� ������� � ������ ��������� 
		testMethod1() {} // �������������� ����� 1 ������
		tearDown()    {} // �������� ������� 
		setUp()       {} // ���������� ������� � ������ ���������
		testMethod2() {} // �������������� ����� 2 ������
		tearDown()    {} // �������� �������
		�
		setUp()       {} // ���������� ������� � ������ ���������
		testMethodN() {} // �������������� ����� N ������
		tearDown()    {} // �������� �������
	
	*/
	
	/*
		������������ ��.
		
		��������: 
		http://habrahabr.ru/post/113872/
		http://habrahabr.ru/post/61710/ - ���������� ������� DataSet.
		https://phpunit.de/manual/current/en/database.html
		http://habrahabr.ru/post/138862/ - ������� ������� � �������� �������, ������������� ��������� ���������,
										   ������� ������� ��...
		http://habrahabr.ru/post/139727/ - ����������
		
		�������.
		1) ������� ����. ��� ������ ������� �� �� ����� � ����� ��������� ��������� ��, 
			������� �� ������� ������� � ������� �����;
		2) ������� ��������� ������ (�������). 
		������ ���������� ����� �����-���� ��������� ������, ������� ��� ��������� �� ���� ��� ����������� ���������. 
		������ �� � ���� �������� � ������ ��� ��������� ����;
		3) ���������� ���������� ������ � �������� �����������. ��� ������������.
		
		����� ����������� ��� ����������� ������ � getConnection() � getDataSet()
		getConnection - ���  ��������� ����������. ��� ����������� � ���� ����� ������������ PDO
		getDataSet - ������� �� � ��������� ���������. ����� getDataSet() ���������� ������� setUp(), 
		�.� ����� ������� ������� �����.
		
		DataTables & DataSets

		��� DataSet � ������������ DbUnit ���������� ����� �� ����� ��� ����� ������.
		DataTable � DataSet � ��� ���������� ��� ������ � ������� � �������� ��.
		����� ���������� ���������� ��� ��������� ���������� �������� ���� � ���������. 
		
		� �������� ����� ������� ����� ������������ ������ ��� �������, � �� �������� ������� �� ��.
				
		DataTable � DataSet ����� ���� �������� ���������� ��������� - XML, CSV, ������� PHP. 
		������� ��� �������� xml ��� DataSet ����� mysqldump
		
		sudo mysqldump --xml -t -u root -p root rabbitmq_test > "/var/www/test_rabbit/ttttt.xml"
		
		����� DataSet � DataTable ������������ ��� ������� ���������� ��������� ���� ������ ����� ����������� �����. 
		
		����� ������ TestInsertToDB:
		1) ��������� �������������� ������
		2) �������� DataSet �������
		3) �������� DataSet ��������
		4) ���������� DataSet
		
		DataSet ����� �������� ���������� ���������: �������, ��������� ������ �������, ��������� �������...
	*/
	
	require_once "PHPUnit/Extensions/Database/TestCase.php";

	class MyTest extends PHPUnit_Extensions_Database_TestCase
	{
		 // only instantiate pdo once for test clean-up/fixture load
		static private $pdo = null;

		// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
		private $conn = null;
		/*
		 ��������� ����������.
		 (getConnection() ������ ������������ PDO ��� ����������� � ����)
		*/
		final public function getConnection()
		{
			 if ($this->conn === null) {
				if (self::$pdo == null) {
					self::$pdo = new \PDO('mysql:dbname=rabbitmq_test;host=localhost', 'root', '');
				}
				$this->conn = $this->createDefaultDBConnection(self::$pdo, 'rabbitmq_test');
			}

			return $this->conn;
		}
		
		public function getDataSet()
		{
			return $this->createMySQLXMLDataSet(dirname(__FILE__).'/fixture/firstable_state.xml');
		}
		
		public function testInsertToDB () 
		{
			$id = '3726'; 
			$message = '6.2.1b3nr2oXMHMhGVzPcUByWndV0xfWY732XlubxVSSO533nxocjvIieHnMdlc';
			$publisher = '6';
			$consumer = '2';
			$date = '2015-05-29 13:40:07';

			$sql = "INSERT INTO `level_2` (`id`,`data`,`publisher`,`consumer`, `date`) VALUES ('{$id}','{$message}','{$publisher}','$consumer','$date')";
			$statement = $this->getConnection()->getConnection()->query($sql);
			$queryTable = $this->getConnection()->createQueryTable('level_2', 'SELECT `id`,`data`,`publisher`,`consumer`, `date` FROM level_2');
	/*
			$ds = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
			$ds->addTable('level_2', , 'SELECT `id`,`data`,`publisher`,`consumer`, `date` FROM level_2');
			$queryTable = $ds->getTable("level_2")

	*/
			$expectedTable = $this->createMySQLXMLDataSet(dirname(__FILE__).'/fixture/state_testInsertToDB.xml')->getTable("level_2");
			$this->assertTablesEqual($queryTable->getTable("level_2"), $expectedTable);
		}
	}
?>