<?php
require_once dirname(__FILE__) . '/../../_interface/SiteFactory.php';
require_once dirname(__FILE__) . '/../../_class/ScriptChainImpl.php';

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 5/28/12
 * Time: 3:55 PM
 * To change this template use File | Settings | File Templates.
 */
class StubSiteFactoryImpl implements SiteFactory
{

    private $preScriptChain;
    private $postScriptChain;
    private $template;
    private $pageElementFactory;
    private $config;
    private $CSSRegister;
    private $JSRegister;
    private $AJAXRegister;
    private $backendSingletonContainer;

    public function __construct()
    {
        $this->preScriptChain = new ScriptChainImpl();
        $this->postScriptChain = new ScriptChainImpl();
    }

    /**
     * Builds a new PreScriptChain and returns it. This must contain prescripts specified
     * in some config (it must be ready to run).
     * @param BackendSingletonContainer $backendContainer
     * @return \ScriptChain
     */
    public function buildPreScriptChain(BackendSingletonContainer $backendContainer)
    {
        return $this->preScriptChain;
    }

    /**
     * Builds a new PostScriptChain and returns it. This must contain prescripts specified
     * in some config (it must be ready to run).
     * @param BackendSingletonContainer $backendContainer
     * @return \ScriptChain
     */
    public function buildPostScriptChain(BackendSingletonContainer $backendContainer)
    {
        return $this->postScriptChain;
    }


    public function buildConfig()
    {
        return $this->config;
    }


    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setPostScriptChain($postScriptChain)
    {
        $this->postScriptChain = $postScriptChain;
    }

    /**
     * @param ScriptChain $preScriptChain
     */
    public function setPreScriptChain($preScriptChain)
    {
        $this->preScriptChain = $preScriptChain;
    }


    /**
     * @param BackendSingletonContainer $backendSingletonContainer
     */
    public function setBackendSingletonContainer($backendSingletonContainer)
    {
        $this->backendSingletonContainer = $backendSingletonContainer;
    }


    /**
     * Builds a new BackendSingletonContainer and returns it.
     * @param Config $config
     * @return BackendSingletonContainer
     */
    public function buildBackendSingletonContainer(Config $config)
    {
        return $this->backendSingletonContainer;
    }
}
