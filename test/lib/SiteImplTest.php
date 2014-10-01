<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 3/3/14
 * Time: 11:11 PM
 */
namespace ChristianBudde\cbweb\test;

use ChristianBudde\cbweb\model\site\SiteImpl;
use ChristianBudde\cbweb\test\util\CustomDatabaseTestCase;
use ChristianBudde\cbweb\test\stub\StubDBImpl;

class SiteImplTest extends CustomDatabaseTestCase
{


    private $db;
    /** @var  SiteImpl */
    private $site;

    function __construct()
    {
        parent::__construct(dirname(__FILE__) . '/../mysqlXML/SiteImplTest.xml');
    }


    public function setUp()
    {
        parent::setUp();
        $this->db = new StubDBImpl();
        $this->db->setConnection(self::$pdo);
        $this->site = new SiteImpl($this->db);

    }

    public function testGetSiteContentReturnSameInstance()
    {
        $this->assertTrue($this->site->getContentLibrary() === $this->site->getContentLibrary());
        $this->assertInstanceOf("ChristianBudde\\cbweb\\model\\ContentLibrary", $this->site->getContentLibrary());

    }

    public function testGetSiteContentReuseInstance()
    {
        $this->assertTrue($this->site->getContent("Test") === $this->site->getContent("Test"));
        $this->assertTrue($this->site->getContent("Test") === $this->site->getContentLibrary()->getContent("Test"));
    }

    public function testModifyWillChangeLastModified()
    {
        $t1 = $this->site->lastModified();
        $this->site->modify();
        $t2 = $this->site->lastModified();
        $this->assertGreaterThan($t1, $t2);
    }

    public function testVariablesWillReuseInstance()
    {
        $this->assertInstanceOf("ChristianBudde\\cbweb\\model\\Variables", $this->site->getVariables());
        $this->assertTrue($this->site->getVariables() === $this->site->getVariables());
    }

    public function testModifyWillBePersistent()
    {
        $t1 = $this->site->lastModified();
        $this->site->modify();
        $site = new SiteImpl($this->db);
        $t2 = $site->lastModified();
        $this->assertGreaterThan($t1, $t2);

    }


}