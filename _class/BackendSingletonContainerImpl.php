<?php
require_once dirname(__FILE__) . '/../_interface/BackendSingletonContainer.php';
require_once dirname(__FILE__) . '/MySQLDBImpl.php';
require_once dirname(__FILE__) . '/CSSRegisterImpl.php';
require_once dirname(__FILE__) . '/JSRegisterImpl.php';
require_once dirname(__FILE__) . '/AJAXRegisterImpl.php';
require_once dirname(__FILE__) . '/DartRegisterImpl.php';
require_once dirname(__FILE__) . '/PageOrderImpl.php';
require_once dirname(__FILE__) . '/CurrentPageStrategyImpl.php';
require_once dirname(__FILE__) . '/UserLibraryImpl.php';
require_once dirname(__FILE__) . '/DefaultPageLibraryImpl.php';
require_once dirname(__FILE__) . '/CacheControlImpl.php';
require_once dirname(__FILE__) . '/GitUpdaterImpl.php';
require_once dirname(__FILE__) . '/SiteVariablesImpl.php';

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 6/13/12
 * Time: 4:54 PM
 * To change this template use File | Settings | File Templates.
 */
class BackendSingletonContainerImpl implements BackendSingletonContainer
{

    private $config;
    /** @var $database null | DB  */
    private $database = null;
    /** @var $cssRegister null | CSSRegister */
    private $cssRegister = null;
    /** @var $jsRegister null | JSRegister */
    private $jsRegister;
    /** @var $ajaxRegister null | AJAXRegister */
    private $ajaxRegister;
    /** @var $pageOrder null | PageOrder */
    private $pageOrder;
    /** @var $pageOrder null | CurrentPageStrategy */
    private $currentPageStrategy;
    /** @var $userLibrary null | UserLibrary */
    private $userLibrary;
    /** @var DefaultPageLibrary */
    private $defaultPageLibrary;
    /** @var DartRegister */
    private $dartRegister;
    /** @var  CacheControl */
    private $cacheControl;
    /** @var  Updater */
    private $updater;
    /** @var  Variables */
    private $siteVariables;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * This will return a DB. The same from time to time
     * @return DB
     */
    public function getDBInstance()
    {
        if ($this->database === null) {
            $this->database = new MySQLDBImpl($this->config);
        }

        return $this->database;
    }

    /**
     * This will return an css register, and reuse it from time to time
     * @return CSSRegister
     */
    public function getCSSRegisterInstance()
    {
        if ($this->cssRegister === null) {
            $this->cssRegister = new CSSRegisterImpl();
        }
        return $this->cssRegister;
    }

    /**
     * This will return an js register, and reuse it from time to time
     * @return JSRegister
     */
    public function getJSRegisterInstance()
    {
        if ($this->jsRegister === null) {
            $this->jsRegister = new JSRegisterImpl();
        }
        return $this->jsRegister;
    }

    /**
     * This will return an ajax register, and reuse it from time to time
     * @return AJAXRegister
     */
    public function getAJAXRegisterInstance()
    {
        if ($this->ajaxRegister === null) {
            $this->ajaxRegister = new AJAXRegisterImpl($this);
        }
        return $this->ajaxRegister;
    }

    /**
     * This will return an instance of PageOrder, and reuse it.
     * @return PageOrder
     */
    public function getPageOrderInstance()
    {
        if ($this->pageOrder === null) {
            $this->pageOrder = new PageOrderImpl($this->getDBInstance());
        }
        return $this->pageOrder;
    }

    /**
     * This will return an instance of CurrentPageStrategy, and reuse it.
     * @return CurrentPageStrategy
     */
    public function getCurrentPageStrategyInstance()
    {
        if ($this->currentPageStrategy === null) {
            $this->currentPageStrategy = new CurrentPageStrategyImpl($this->getPageOrderInstance(),$this->getDefaultPageLibraryInstance());
        }
        return $this->currentPageStrategy;
    }

    /**
     * Will return an instance of Config, this might be the same as provided in constructor
     * @return Config
     */
    public function getConfigInstance()
    {
        return $this->config;
    }


    /**
     * Will create and reuse an instance of UserLibrary
     * @return UserLibrary
     */
    public function getUserLibraryInstance()
    {
        if ($this->userLibrary === null) {
            $this->userLibrary = new UserLibraryImpl($this->getDBInstance());
        }
        return $this->userLibrary;
    }


    /**
     * Will create and reuse an instance of DefaultPageLibrary
     * @return DefaultPageLibrary
     */
    public function getDefaultPageLibraryInstance()
    {
        if($this->defaultPageLibrary === null){
            $this->defaultPageLibrary = new DefaultPageLibraryImpl($this->getConfigInstance());
        }

        return $this->defaultPageLibrary;
    }

    /**
     * This will return an dart register, and reuse it from time to time
     * @return DartRegister
     */
    public function getDartRegisterInstance()
    {
        if($this->dartRegister === null){
            $this->dartRegister = new DartRegisterImpl();
        }
        return $this->dartRegister;
    }

    /**
     * Will create and reuse an instance of CacheControl
     * @return CacheControl
     */
    public function getCacheControlInstance()
    {
        if($this->cacheControl == null){
            $this->cacheControl = new CacheControlImpl($this->getCurrentPageStrategyInstance());
        }
        return $this->cacheControl;
    }

    /**
     * Will create and reuse an instance of Updater
     * @return mixed
     */
    public function getUpdater()
    {
        if($this->updater == null){
            $this->updater = new GitUpdaterImpl($this->getConfigInstance()->getRootPath());
        }
        return $this->updater;
    }

    /**
     * Will create and reuse an instance of Variables.
     * These should reflect the site scoped variables.
     * @return Variables
     */
    public function getSiteVariablesInstance()
    {
        return $this->siteVariables == null? $this->siteVariables = new SiteVariablesImpl($this->getDBInstance()):$this->siteVariables;
    }
}
