<?php
	/*
		Тестирование обычных классов PHP
		Для тестирования необходимо создать тестовый класс, расширяющий базовый класс PHPUnit_Framework_TestCase.
		
		Затем создать в этом классе публичные методы, начинающиеся со слова test.
		Полученный класс phpunit последовательно вызовет все тестовые методы.

		Для подготовки системы в нужное состояние и уменьшения дублирования кода, 
		в классе PHPUnit_Framework_TestCase созданы защищенные методы 
		setUp и tearDown, имеющие пустую реализацию. 
		Эти методы вызываются перед и после  запуска очередного тестового метода соответственно.

		В тестовом классе, расширяющем PHPUnit_Framework_TestCase, можно переопределить 
		эти методы и поместить повторяющийся ранее в каждом тестовом методе код в них. 
		В результате последовательность вызова методов при прогонке тестов будет следующая:
		
		setUp()       {} // Установили систему в нужное состояние 
		testMethod1() {} // протестировали метод 1 класса
		tearDown()    {} // Очистили систему 
		setUp()       {} // Установили систему в нужное состояние
		testMethod2() {} // протестировали метод 2 класса
		tearDown()    {} // Очистили систему
		…
		setUp()       {} // Установили систему в нужное состояние
		testMethodN() {} // протестировали метод N класса
		tearDown()    {} // Очистили систему
	
	*/
	
	/*
		Тестирование БД.
		
		Иточники: 
		http://habrahabr.ru/post/113872/
		http://habrahabr.ru/post/61710/ - реализация вставки DataSet.
		https://phpunit.de/manual/current/en/database.html
		http://habrahabr.ru/post/138862/ - Решения проблем с внешними ключами, неправильного сравнения датасетов,
										   функция очистки бд...
		http://habrahabr.ru/post/139727/ - транзакции
		
		Порядок.
		1) Очистка базы. При первом запуске мы не знаем в каком состоянии находится БД, 
			поэтому мы обязаны «начать с чистого листа»;
		2) Вставка начальных данных (фикстур). 
		Обычно приложению нужны какие-либо начальные данные, которые оно извлекает из базы для последующей обработки. 
		именно их и надо вставить в только что очищенную базу;
		3) Собственно выполнение тестов и проверка результатов. Без комментариев.
		
		Нужно реализовать два абстрактных метода — getConnection() и getDataSet()
		getConnection - для  установки соединения. Для подключения к базе нужно использовать PDO
		getDataSet - перевод БД в начальное состояние. Метод getDataSet() вызывается методом setUp(), 
		т.е после прогона каждого теста.
		
		DataTables & DataSets

		Под DataSet в терминологии DbUnit понимается набор из одной или более таблиц.
		DataTable и DataSet — это абстракция для таблиц и записей в реальной БД.
		Такая абстракция необходима для сравнения ожидаемого контента базы и реального. 
		
		В конечном итоге тестами будут подвергаться именно эти объекты, а не реальные таблицы из БД.
				
		DataTable и DataSet может быть заполнен различными способами - XML, CSV, массивы PHP. 
		Комадна для создания xml для DataSet через mysqldump
		
		sudo mysqldump --xml -t -u root -p root rabbitmq_test > "/var/www/test_rabbit/ttttt.xml"
		
		Также DataSet и DataTable используются для задания начального состояния базы данных перед выполнением теста. 
		
		Схема работы TestInsertToDB:
		1) Вставляем подготовленные данные
		2) Получаем DataSet текущей
		3) Получаем DataSet ожидания
		4) Сравниваем DataSet
		
		DataSet можно собирать различными способами: мержить, исключать лишние таблицы, добавлять таблицы...
	*/
	
	require_once "PHPUnit/Extensions/Database/TestCase.php";

	class MyTest extends PHPUnit_Extensions_Database_TestCase
	{
		 // only instantiate pdo once for test clean-up/fixture load
		static private $pdo = null;

		// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
		private $conn = null;
		/*
		 установка соединения.
		 (getConnection() должен использовать PDO для подключения к базе)
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