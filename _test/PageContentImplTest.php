<?php
require_once dirname(__FILE__).'/MySQLConstants.php';
require_once dirname(__FILE__).'/_stub/StubDBImpl.php';
require_once dirname(__FILE__).'/_stub/StubPageImpl.php';
require_once dirname(__FILE__).'/../_class/PageContentImpl.php';
require_once dirname(__FILE__).'/../_class/PageImpl.php';
require_once dirname(__FILE__) . '/TruncateOperation.php';

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 8/13/13
 * Time: 11:41 AM
 * To change this template use File | Settings | File Templates.
 */

class PageContentImplTest extends PHPUnit_Extensions_Database_TestCase{

    /** @var  DB */
    private $db;
    /** @var  PageContentImpl */
    private $existingContent;
    /** @var  PageContentImpl */
    private $nonExistingContent;
    /** @var  Page */
    private $existingPage;
    /** @var  Page */
    private $nonExistingPage;


    public function setUp(){
        parent::setUp();
        $this->db = new StubDBImpl();
        $pdo = new PDO('mysql:dbname=' . MySQLConstants::MYSQL_DATABASE. ';host=' . MySQLConstants::MYSQL_HOST, MySQLConstants::MYSQL_USERNAME, MySQLConstants::MYSQL_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        $this->db->setConnection($pdo);
        $this->existingPage = new PageImpl('testpage', $this->db);
        $this->existingContent = new PageContentImpl($this->db, $this->existingPage);

        $this->nonExistingPage = new PageImpl('nonExisting', $this->db);
        $this->nonExistingContent = new PageContentImpl($this->db, $this->nonExistingPage);
    }

    public function testListContentWillReturnArray(){
        $this->assertTrue(is_array($this->existingContent->listContentHistory()));
    }

    public function testListContentOfNonExistingWillReturnArray(){
        $this->assertTrue(is_array($this->nonExistingContent->listContentHistory()));
    }

    public function testListContentWillReturnArrayOfRightSize(){

        $this->assertEquals(1, count($ar = $this->existingContent->listContentHistory()));
        $this->assertEquals("Some Content", trim($ar[0]['content']));
        $this->assertGreaterThan(0, trim($ar[0]['time']));
        $this->assertEquals(2, count($ar[0]));
    }

    public function testFromWillLimitList(){
        $this->assertEquals(0, count($this->existingContent->listContentHistory(time())));
    }


    public function testAddContentWillAddContent(){
        $content = "Lorem Ipsum";
        $this->existingContent->addContent($content);
        $ec = $this->existingContent->listContentHistory(time()-100);
        $this->assertEquals(2, count($this->existingContent->listContentHistory()));
        $this->assertEquals(1, count($ec));
        $this->assertEquals($content, $ec[0]['content']);
    }

    public function testAddContentIsVolatile(){
        $this->assertEquals(1, count($this->existingContent->listContentHistory()));
        $this->existingContent->addContent("ASD");
        $this->assertEquals(2, count($this->existingContent->listContentHistory()));
        $this->existingContent  = new PageContentImpl($this->db, $this->existingPage);
        $this->assertEquals(2, count($this->existingContent->listContentHistory()));
    }

    public function testCantAddContentToNonExistingPage(){
        $this->nonExistingContent->addContent("lol");
        $this->assertEquals(0, count($this->nonExistingContent->listContentHistory()));
    }


    public function testCanAddContentToNewPage(){
        $this->nonExistingPage->create();
        $this->nonExistingContent->addContent("ASD");
        $this->assertEquals(1, count($this->nonExistingContent->listContentHistory()));
    }

    public function testLatestContentWillReturnLatestContent(){
        $content = "LoremIp";
        $this->existingContent->addContent($content);
        $this->assertEquals($content, $this->existingContent->latestContent());
    }

    public function testLatestContentWillReturnNullOnNoContent(){
        $this->assertNull($this->nonExistingContent->latestContent());
    }


    public function testLatestTimeWillReturnNullOnNoContent(){
        $this->assertNull($this->nonExistingContent->latestTime());
    }


    public function testLatestTimeWillReturnLatestTime(){
        $this->existingContent->addContent("ASD");
        $this->assertGreaterThan(time()-100, $this->existingContent->latestTime());
    }






    /**
     * Returns the test database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        $pdo = new PDO('mysql:dbname=' . MySQLConstants::MYSQL_DATABASE . ';host=' . MySQLConstants::MYSQL_HOST, MySQLConstants::MYSQL_USERNAME, MySQLConstants::MYSQL_PASSWORD);
        return $this->createDefaultDBConnection($pdo);
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__) . '/_mysqlXML/PageContentImplTest.xml');
    }

    public function getSetUpOperation()
    {
        $cascadeTruncates = true;
        return new PHPUnit_Extensions_Database_Operation_Composite(array(new TruncateOperation($cascadeTruncates), PHPUnit_Extensions_Database_Operation_Factory::INSERT()));
    }

}