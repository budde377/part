<?php

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 6/13/12
 * Time: 4:59 PM
 * To change this template use File | Settings | File Templates.
 */

use ChristianBudde\cbweb\BackendSingletonContainerImpl;

class BackendSingletonContainerImplTest extends CustomDatabaseTestCase
{

    /** @var $config StubConfigImpl */
    private $config;
    /** @var $backContainer BackendSingletonContainerImpl */
    private $backContainer;
    /** @var  array */
    private $connectionArray;

    protected function setUp()
    {
        $this->connectionArray = array(
            'host' => self::$mysqlOptions->getHost(),
            'user' => self::$mysqlOptions->getUsername(),
            'password' => self::$mysqlOptions->getPassword(),
            'database' => self::$mysqlOptions->getDatabase());
        $this->config = new StubConfigImpl();
        $this->config->setMysqlConnection($this->connectionArray);
        $this->backContainer = new BackendSingletonContainerImpl($this->config);
    }

    public function testGetDBInstanceReturnsSameInstanceOfDB()
    {
        $db1 = $this->backContainer->getDBInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\DB', $db1, 'Did not return instance of DB');
        $this->config->setMysqlConnection(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $db2 = $this->backContainer->getDBInstance();
        $this->assertTrue($db1 == $db2, 'Did not reuse instance');
    }

    public function testGetCSSRegisterReturnsSameInstanceOfCSSRegister()
    {
        $css1 = $this->backContainer->getCSSRegisterInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\CSSRegister', $css1, 'Did not return instance of CSSRegister');
        $this->config->setMysqlConnection(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $css2 = $this->backContainer->getCSSRegisterInstance();
        $this->assertTrue($css1 === $css2, 'Did not reuse instance');

    }

    public function testGetDartRegisterReturnsSameInstanceOfDartRegister()
    {
        $dart1 = $this->backContainer->getDartRegisterInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\DartRegister', $dart1, 'Did not return instance of CSSRegister');
        $this->config->setMysqlConnection(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $dart2 = $this->backContainer->getDartRegisterInstance();
        $this->assertTrue($dart1 === $dart2, 'Did not reuse instance');

    }

    public function testGetJSRegisterReturnsSameInstanceOfJSRegister()
    {
        $js1 = $this->backContainer->getJSRegisterInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\JSRegister', $js1, 'Did not return instance of JSRegister');
        $this->config->setMysqlConnection(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $js2 = $this->backContainer->getJSRegisterInstance();
        $this->assertTrue($js1 === $js2, 'Did not reuse instance');

    }

    public function testGetAJAXServerReturnsSameInstanceOfAJAXServer()
    {
        $ajax1 = $this->backContainer->getAJAXServerInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\AJAXServer', $ajax1, 'Did not return instance of AJAXServer');
        $this->config->setMysqlConnection(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $ajax2 = $this->backContainer->getAJAXServerInstance();
        $this->assertTrue($ajax1 === $ajax2, 'Did not reuse instance');

    }

    public function testGetPageOrderRegisterReturnsSameInstanceOfPageOrder()
    {
        $this->config->setMysqlConnection($this->connectionArray);
        $pageOrder1 = $this->backContainer->getPageOrderInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\PageOrder', $pageOrder1, 'Did not return instance of PageOrder');
        $pageOrder2 = $this->backContainer->getPageOrderInstance();
        $this->assertTrue($pageOrder1 === $pageOrder2, 'Did not reuse instance');

    }

    public function testGetCurrentPageStrategyReturnsSameInstanceOfCurrentPageStrategy()
    {
        $this->config->setMysqlConnection($this->connectionArray);
        $pageOrder1 = $this->backContainer->getCurrentPageStrategyInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\CurrentPageStrategy', $pageOrder1, 'Did not return instance of CurrentPageStrategy');
        $pageOrder2 = $this->backContainer->getCurrentPageStrategyInstance();
        $this->assertTrue($pageOrder1 === $pageOrder2, 'Did not reuse instance');


    }

    public function testGetUserLibraryInstanceReturnsSameInstanceOfUserLibrary()
    {
        $this->config->setMysqlConnection($this->connectionArray);
        $ret1 = $this->backContainer->getUserLibraryInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\UserLibrary', $ret1, 'Did not return instance of SiteLibrary');
        $ret2 = $this->backContainer->getUserLibraryInstance();
        $this->assertTrue($ret1 === $ret2, 'Did not reuse instance');


    }


    public function testGetConfigInstanceWillReturnSameInstanceOfConfig()
    {
        $ret1 = $this->backContainer->getConfigInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\Config', $ret1, 'Did not return instance of Config');
        //$this->config->setMysqlCon(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $ret2 = $this->backContainer->getConfigInstance();
        $this->assertTrue($ret1 === $ret2, 'Did not reuse instance');

    }

    public function testGetDefaultPageLibraryWillReturnASameInstanceOfDefaultPageLibrary()
    {
        $ret1 = $this->backContainer->getDefaultPageLibraryInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\DefaultPageLibrary', $ret1, 'Did not return instance of DefaultPageLibrary');
        //$this->config->setMysqlCon(array('host' => 'lol', 'user' => 'lol', 'database' => '', 'password' => ''));
        $ret2 = $this->backContainer->getDefaultPageLibraryInstance();
        $this->assertTrue($ret1 === $ret2, 'Did not reuse instance');

    }

    public function testGetCacheControlWillReturnSameInstanceOfCacheControl()
    {
        $ret1 = $this->backContainer->getCacheControlInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\CacheControl', $ret1);
        $ret2 = $this->backContainer->getCacheControlInstance();
        $this->assertTrue($ret1 === $ret2);
    }

    public function testGetUpdaterWillReturnSameInstanceOfUpdater()
    {
        $ret1 = $this->backContainer->getUpdater();
        $this->assertInstanceOf('ChristianBudde\cbweb\Updater', $ret1);
        $ret2 = $this->backContainer->getUpdater();
        $this->assertTrue($ret1 === $ret2);
    }

    public function testGetSiteVariablesWillReturnSameInstanceOfUpdater()
    {
        $ret1 = $this->backContainer->getSiteInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\Site', $ret1);
        $ret2 = $this->backContainer->getSiteInstance();
        $this->assertTrue($ret1 === $ret2);
    }

    public function testGetFileLibraryWillReturnSameInstance()
    {
        $ret1 = $this->backContainer->getFileLibraryInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\FileLibrary', $ret1);
        $ret2 = $this->backContainer->getFileLibraryInstance();
        $this->assertTrue($ret1 === $ret2);
    }

    public function testGetLogWillReturnSameInstance()
    {
        $p = "/some/path";
        $this->config->setLogPath($p);
        $ret1 = $this->backContainer->getLoggerInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\Logger', $ret1);
        $ret2 = $this->backContainer->getLoggerInstance();
        $this->assertTrue($ret1 === $ret2);
    }

    public function testGetLogWillReturnNullIfNoPathInConfig()
    {
        $ret1 = $this->backContainer->getLoggerInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\Logger', $ret1);
        $ret2 = $this->backContainer->getLoggerInstance();
        $this->assertTrue($ret1 === $ret2);

    }
    public function testGetMailDomainLibraryWillReturnNullIfNoPathInConfig()
    {
        $ret1 = $this->backContainer->getMailDomainLibraryInstance();
        $this->assertInstanceOf('ChristianBudde\cbweb\MailDomainLibrary', $ret1);
        $ret2 = $this->backContainer->getMailDomainLibraryInstance();
        $this->assertTrue($ret1 === $ret2);

    }

}
