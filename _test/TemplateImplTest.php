<?php
require_once dirname(__FILE__) . '/../_class/ConfigImpl.php';
require_once dirname(__FILE__) . '/../_class/PageElementFactoryImpl.php';
require_once dirname(__FILE__) . '/../_class/TemplateImpl.php';
require_once dirname(__FILE__) . '/_stub/NullPageElementFactoryImpl.php';
require_once dirname(__FILE__) . '/_stub/HelloPageElementImpl.php';
require_once dirname(__FILE__) . '/_stub/NullPageElementImpl.php';
require_once dirname(__FILE__) . '/_stub/StubUserLibraryImpl.php';
require_once dirname(__FILE__) . '/_stub/StubBackendSingletonContainerImpl.php';
require_once dirname(__FILE__) . '/_stub/StubCurrentPageStrategyImpl.php';
require_once dirname(__FILE__) . '/_stub/StubContentImpl.php';
require_once dirname(__FILE__) . '/_stub/StubPageImpl.php';
require_once dirname(__FILE__) . '/_stub/StubSiteImpl.php';
/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 5/29/12
 * Time: 10:46 AM
 * To change this template use File | Settings | File Templates.
 */
class TemplateImplTest extends PHPUnit_Framework_TestCase
{

    /** @var $backFactory BackendSingletonContainer */
    private $backFactory;

    /** @var $template TemplateImpl */
    private $template;
    private $rootPath;
    /** @var  StubPageImpl */
    private $currentPage;
    /** @var  Site */
    private $site;

    private $defaultOwner = "<siteInfo><domain name='test' extension='dk'/><owner name='Admin Jensen' mail='test@test.dk' username='asd' /></siteInfo>";

    protected function setUp()
    {
        $this->backFactory = $this->template = $this->rootPath = $this->currentPage = null;
        @session_start();

    }

    protected function tearDown()
    {
        unset($this->template);
        @session_destroy();
    }

    private function setUpConfig($config = null)
    {
        if($config == null){
            $config = "
            <config>{$this->defaultOwner}
            <pageElements>
                <class name='someElement' link='_stub/HelloPageElementImpl.php'>HelloPageElementImpl</class>
                <class name='initElement' link='_stub/CheckInitializedPageElementImpl.php'>CheckInitializedPageElementImpl</class>
            </pageElements>

            </config>";
        }

        /** @var $configXML SimpleXMLElement */
        $configXML = simplexml_load_string($config);
        $this->rootPath = dirname(__FILE__) . '/';
        $config = new ConfigImpl($configXML, $this->rootPath);
        $this->backFactory = new StubBackendSingletonContainerImpl();
        $nullPageElementFactory = new PageElementFactoryImpl($config, $this->backFactory);
        $currentPageStrategy = new StubCurrentPageStrategyImpl();
        $this->currentPage = new StubPageImpl();
        $currentPageStrategy->setCurrentPage($this->currentPage);
        $this->backFactory->setCurrentPageStrategyInstance($currentPageStrategy);
        $this->backFactory->setConfigInstance($config);
        $this->backFactory->setUserLibraryInstance(new StubUserLibraryImpl());
        $this->site = new StubSiteImpl();
        $this->backFactory->setSiteInstance($this->site);
        $this->template = new TemplateImpl($nullPageElementFactory, $this->backFactory);
    }

    public function testWillThrowExceptionIfTemplateIsNotFound()
    {

        $this->setUpConfig();
        $exceptionWasThrown = false;
        $file = new FileImpl('nonExistingFile');
        try {
            $this->template->setTemplate($file);
        } catch (Exception $exception) {
            $this->assertInstanceOf('FileNotFoundException', $exception, 'Got the wrong exception');
            /** @var $exception FileNotFoundException */
            $exceptionWasThrown = true;
            $this->assertEquals($file->getAbsoluteFilePath(), $exception->getFileName(), 'Did not expect the right file');

        }

        $this->assertTrue($exceptionWasThrown, 'Exception was not thrown');
    }


    public function testCanSetTemplateFromExistingFile(){
        $this->setUpConfig("
        <config>
            {$this->defaultOwner}
            <templates path='_stub/'>
                <template filename='templateStub.twig'>main</template>
            </templates>
        </config>");

        $this->template->setTemplateFromConfig('main');
        $this->template->render();

    }

    public function testWillThrowExceptionIfTemplateFileIsNotFoundFromConfig()
    {
        $this->setUpConfig("
        <config>
            {$this->defaultOwner}
            <templates path='folder'>
                <template filename='NonExistingFile'>main</template>
            </templates>
        </config>");

        $exceptionWasThrown = false;
        try {
            $this->template->setTemplateFromConfig('main');
        } catch (Exception $exception) {
            $this->assertInstanceOf('FileNotFoundException', $exception, 'Got the wrong exception');
            /** @var $exception FileNotFoundException */
            $exceptionWasThrown = true;
            $this->assertEquals($this->rootPath . 'folder/NonExistingFile', $exception->getFileName(), 'Did not expect the right file');

        }

        $this->assertTrue($exceptionWasThrown, 'Exception was not thrown');

    }

    public function testWillThrowExceptionIfTemplateNotInConfig()
    {
        $this->setUpConfig();
        $exceptionWasThrown = false;
        try {
            $this->template->setTemplateFromConfig('main');
        } catch (Exception $exception) {
            $this->assertInstanceOf('EntryNotFoundException', $exception, 'Got the wrong exception');
            /** @var $exception EntryNotFoundException */
            $exceptionWasThrown = true;
            $this->assertEquals('main', $exception->getEntry(), 'Could not find the right wrong entry');

        }

        $this->assertTrue($exceptionWasThrown, 'Exception was not thrown');
    }


    public function testTemplatesUsesTwig(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{%set t='World' %}Hello{{t}}");
        $this->assertEquals("HelloWorld", $this->template->render());
    }

    public function testDebugEnablesDebug(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $this->assertGreaterThan(0, strlen($this->template->render()));

    }

    public function testTemplateAddsCurrentUserName(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "current_user") !== false);
    }

    public function testTemplateAddsUserLibrary(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "user_lib") !== false);
    }

    public function testTemplateAddsCurrentPage(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "current_page") !== false);
    }


    public function testTemplateAddsCurrentPagePath(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "current_page_path") !== false);
    }

    public function testTemplateAddsPageOrder(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "page_order") !== false);
    }

    public function testTemplateAddsCSSRegister(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "css_register") !== false);
    }
    public function testTemplateAddsJSRegister(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{{ dump() }}");
        $v = $this->template->render();
        $this->assertTrue(strpos($v, "js_register") !== false);
    }

    public function testTemplateSupportsPageElementTag(){
        $this->setUpConfig();
        $this->template->setTwigDebug(true);
        $this->template->setTemplateFromString("{%page_element someElement%}");
        $v = $this->template->render();
        $this->assertEquals( "Hello World", $v);
    }

    public function testTemplateBreakIfNoPageElement(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%page_element nonExistingElement%}");
        $exception = false;
        try{
            $this->template->render();
        } catch(Twig_Error $error){
            $exception = true;
            $this->assertEquals(1, $error->getTemplateLine());
        }
        $this->assertTrue($exception);
    }

    public function testTemplateInitializePageElementIsSupported(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%init_page_element someElement%}");
        $this->assertEquals("", $this->template->render());
    }

    public function testTemplateInitializePageElementDoesJustThat(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%init_page_element initElement%}");
        $_SESSION['initialized'] = 0;
        $this->template->render();
        $this->assertEquals(1, $_SESSION['initialized']);
    }

    public function testTemplateInitializePageElementBreaksOnElementNotFound(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%init_page_element nonExistingElement%}");
        $exception = false;
        try{
            $this->template->render();
        } catch(Twig_Error $error){
            $exception = true;
            $this->assertEquals(1, $error->getTemplateLine());
        }
        $this->assertTrue($exception);
    }

    public function testTemplateWillSupportPageContent(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%page_content someElement %}");
        $this->assertEquals("", $this->template->render());
    }
    public function testTemplateWillSupportPageContentWithNoId(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%page_content asd%}");
        $this->assertEquals("", $this->template->render());
    }

    public function testTemplateWillAddPageContent(){
        $this->setUpConfig();
        $this->currentPage->getContent()->addContent("Hello World");
        $this->template->setTemplateFromString("{%page_content%}");
        $this->assertEquals("Hello World", $this->template->render());
    }


    public function testTemplateWillSupportSiteContent(){
        $this->setUpConfig();
        $this->template->setTemplateFromString("{%site_content someElement %}");
        $this->assertEquals("", $this->template->render());
    }

    public function testTemplateWillAddSiteContent(){
        $this->setUpConfig();
        $this->site->getContent("")->addContent("Hello World");
        $this->template->setTemplateFromString("{%site_content%}");
        $this->assertEquals("Hello World", $this->template->render());
    }


    public function testTemplateWillAddSiteContentWithId(){
        $this->setUpConfig();
        $this->site->getContent("someid")->addContent("Hello World");
        $this->template->setTemplateFromString("{%site_content someid%}");
        $this->assertEquals("Hello World", $this->template->render());
    }



}
