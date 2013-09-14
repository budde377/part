<?php
require_once dirname(__FILE__) . '/../_class/ConfigImpl.php';
require_once dirname(__FILE__) . '/../_class/PageElementFactoryImpl.php';
require_once dirname(__FILE__) . '/../_class/TemplateImpl.php';
require_once dirname(__FILE__) . '/_stub/NullPageElementFactoryImpl.php';
require_once dirname(__FILE__) . '/_stub/HelloPageElementImpl.php';
require_once dirname(__FILE__) . '/_stub/NullPageElementImpl.php';
require_once dirname(__FILE__) . '/_stub/NullBackendSingletonContainerImpl.php';
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

    protected function setUp()
    {
        @session_start();
        $this->backFactory = new NullBackendSingletonContainerImpl();
    }
    protected function tearDown(){
        @session_destroy();
    }

    private function setUpConfig($config = '<config></config>')
    {
        /** @var $configXML SimpleXMLElement */
        $configXML = simplexml_load_string($config);
        $this->rootPath = dirname(__FILE__) . '/';
        $config = new ConfigImpl($configXML, $this->rootPath);
        $nullPageElementFactory = new PageElementFactoryImpl($config, $this->backFactory);
        $this->template = new TemplateImpl($config, $nullPageElementFactory);
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

    public function testWillThrowExceptionIfTemplateFileIsNotFoundFromConfig()
    {
        $this->setUpConfig("
        <config>
            <templates>
                <template link='NonExistingFile'>main</template>
            </templates>
        </config>");

        $exceptionWasThrown = false;
        try {
            $this->template->setTemplateFromConfig('main');
        } catch (Exception $exception) {
            $this->assertInstanceOf('FileNotFoundException', $exception, 'Got the wrong exception');
            /** @var $exception FileNotFoundException */
            $exceptionWasThrown = true;
            $this->assertEquals($this->rootPath . 'NonExistingFile', $exception->getFileName(), 'Did not expect the right file');

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


    public function testSetTemplateWillThrowExceptionIfNotValidXML(){
        $this->setUpConfig();
        $exceptionWasThrown = false;
        try {
            $this->template->setTemplateFromString('asd');
        } catch (Exception $exception) {
            $this->assertInstanceOf('InvalidXMLException', $exception, 'Got the wrong exception');
            /** @var $exception InvalidXMLException*/
            $exceptionWasThrown = true;
        }

        $this->assertTrue($exceptionWasThrown, 'Exception was not thrown');
    }

    public function testGetModifiedTemplateWillReturnTemplateWithNoModificationsIfNonModifiable()
    {
        $this->setUpConfig();
        $oldTemplate = '<html xmlns="http://www.w3.org/1999/xhtml"><head></head><body>Hello</body></html>';
        $this->template->setTemplateFromString($oldTemplate);
        $newTemplate = $this->template->getModifiedTemplate();
        $this->assertEquals($oldTemplate, $newTemplate, 'Did not return the right template');
    }

    public function testGetModifiedTemplateWillReturnTemplateWithNoModificationsIfNonModifiableFromFile()
    {
        $this->setUpConfig();
        $file = dirname(__FILE__) . '/_stub/templateStub.xml';
        $f = new FileImpl($file);
        $oldTemplate = $f->getContents();
        $this->template->setTemplate($f);
        $newTemplate = $this->template->getModifiedTemplate();
        $this->assertEquals($oldTemplate, $newTemplate, 'Did not return the right template');
    }

    public function testGetModifiedTemplateWillReturnTemplateWithNoModificationsIfNonModifiableFromConfig()
    {
        $this->setUpConfig("
        <config>
            <templates>
                <template link='_stub/templateStub.xml'>main</template>
            </templates>
        </config>");


        $file = $this->rootPath . '_stub/templateStub.xml';
        $oldTemplate = file_get_contents($file);
        $this->template->setTemplateFromConfig('main');
        $newTemplate = $this->template->getModifiedTemplate();
        $this->assertEquals($oldTemplate, $newTemplate, 'Did not return the right template');
    }



    public function testGetModifiedTemplateReturnsTemplateWithChangesIfModifiable()
    {
        $this->setUpConfig('
        <config>
            <pageElements>
                <class name="main" link="_stub/HelloPageElementImpl.php">HelloPageElementImpl</class>
                <class name="main2" link="_stub/NullPageElementImpl.php">NullPageElementImpl</class>
            </pageElements>
        </config>');

        $this->template->setTemplateFromString("<html xmlns='http://www.w3.org/1999/xhtml' xmlns:cb='http://christianbud.de/template'>
        <head></head>
        <body>
        prepend <cb:page-element name='main' /> something <cb:page-element name='main2' />something else
        </body>
        </html>");
        $modTemplate = $this->template->getModifiedTemplate();
        $helloElement = new HelloPageElementImpl();
        $nullElement = new NullPageElementImpl();
        $expectedString = 'prepend ' . $helloElement->getContent() . ' something ' . $nullElement->getContent() . ' something else ';
        $this->assertTrue(strpos($modTemplate, $expectedString) !== FALSE);
    }

    public function testGetModifiedTemplateReturnsTemplateWithEntryNotFoundStillThere()
    {
        $this->setUpConfig('
        <config>
            <pageElements>
                <class name="main" link="_stub/HelloPageElementImpl.php">HelloPageElementImpl</class>
            </pageElements>
        </config>');

        $this->template->setTemplateFromString("
        <html xmlns='http://www.w3.org/1999/xhtml'>
        <head></head>
        <body>
        prepend <!-- pageElement:main --> something <!-- pageElement:main2 --> something else
        </body>
        </html>");
        $modTemplate = $this->template->getModifiedTemplate();
        $helloElement = new HelloPageElementImpl();
        $expectedString = 'prepend ' . $helloElement->getContent() . ' something <!-- pageElement:main2 --> something else ';
        $this->assertEquals($expectedString, $modTemplate, 'Did not return expected template.');
    }

    public function testGetModifiedTemplateWithAbsoluteExtension(){
        $this->setUpConfig();
        $templateFile = dirname(__FILE__).'/_stub/templateStub.xml';
        $this->template->setTemplateFromString("
        <extend-template xmlns='http://christianbud.de/template' url='$templateFile'/>");
        $modTemplate = $this->template->getModifiedTemplate();
        $f = new FileImpl(dirname(__FILE__).'/_stub/templateStub.xml');
        $this->assertEquals($f->getContents(), $modTemplate, 'Did not return expected template.');

    }

    public function testGetModifiedTemplateWithExtraElementsInExtension(){
        $this->setUpConfig('
        <config>
            <pageElements>
                <class name="main" link="_stub/HelloPageElementImpl.php">HelloPageElementImpl</class>
            </pageElements>
        </config>');
        $templateFile = dirname(__FILE__).'/_stub/templateStub2.xml';
        $this->template->setTemplateFromString("
         <extend-template xmlns='http://christianbud.de/template' url='$templateFile'>
            <replace-page-element name='someElement'>
            <page-element name='main'/>
            </replace-page-element>
         </extend-template>");
        $modTemplate = $this->template->getModifiedTemplate();
        $this->assertEquals("Hello World",$modTemplate,'Did not return expected template');
    }

    public function testGetModifiedTemplateWithExtendAndExtendedReplace(){
        $this->setUpConfig('
        <config>
            <pageElements>
                <class name="main" link="_stub/HelloPageElementImpl.php">HelloPageElementImpl</class>
            </pageElements>
        </config>');
        $templateFile = dirname(__FILE__).'/_stub/templateStub2.xml';
        $this->template->setTemplateFromString("
        <extend-template xmlns='http://christianbud.de/template' url='$templateFile'>
            <replace-page-element name='someElement'>
            Hello DEAD Fellow
            </replace-page-element>
         </extend-template>");
        $modTemplate = $this->template->getModifiedTemplate();
        $this->assertEquals("Hello DEAD FellowHello World",$modTemplate,'Did not return expected template');
    }


    public function testGetModifiedTemplateHasReversedOrderOfExecutionOfElements(){
        $this->setUpConfig('
        <config >
            <pageElements>
                <class name="main" link="_stub/ReturnIncrementPageElementImpl.php">ReturnIncrementPageElementImpl</class>
                <class name="main2" link="_stub/ReturnIncrementPageElementImpl.php">ReturnIncrementPageElementImpl</class>
            </pageElements>
        </config>');
        $this->template->setTemplateFromString("
        <html xmlns='http://www.w3.org/1999/xhtml' xmlns:cb='http://christianbud.de/template'>
            <cb:page-element name='main' />:<cb:page-element name='main' />
        </html>");
        $result = $this->template->getModifiedTemplate();
        $result = explode(':',$result);

        $this->assertGreaterThan($result[1],$result[0],'Wrong order of execution');
    }
    public function testGetModifiedTemplateHasReversedOrderOfExecutionOfElementsWithReplace(){
        $this->setUpConfig('
        <config >
            <pageElements>
                <class name="main2" link="_stub/ReturnIncrementPageElementImpl.php">ReturnIncrementPageElementImpl</class>
            </pageElements>
        </config>');
        $templateFile = dirname(__FILE__).'/_stub/templateStub3.xml';
        $this->template->setTemplateFromString("
        <extend-template xmlns='http://christianbud.de/template' url='$templateFile'>
            <replace-page-element name='main'>
                <page-element name='main2'/>
            </replace-page-element>
         </extend-template>");
        $result = $this->template->getModifiedTemplate();
        $result = explode(':',$result);

        $this->assertGreaterThan($result[1],$result[0],'Wrong order of execution');
    }

    public function testWillInitializeOnSet(){
        $this->setUpConfig('
        <config >
            <pageElements>
                <class name="main" link="_stub/CheckInitializedPageElementImpl.php">CheckInitializedPageElementImpl</class>
            </pageElements>
        </config>');
        $_SESSION['initialized'] = 0;
        $origVal = $_SESSION['initialized'];
        $this->template->setTemplateFromString("<html xmlns='http://www.w3.org/1999/xhtml' xmlns:cb='http://christianbud.de/template'>
            <cb:page-element name='main' />
        </html>");
        $newVal = $_SESSION['initialized'];

        $this->assertGreaterThan($origVal,$newVal,'Did not initialize');
    }

    public function testWillNotCallGetContentOnSet(){
        $this->setUpConfig('
        <config >
            <pageElements>
                <class name="main" link="_stub/ReturnIncrementPageElementImpl.php">ReturnIncrementPageElementImpl</class>
            </pageElements>
        </config>');
        $orgVal = $_SESSION['inc'] = 0;
        $this->template->setTemplateFromString("<html xmlns='http://www.w3.org/1999/xhtml' xmlns:cb='http://christianbud.de/template'>
            <cb:page-element name='main' />
        </html>");
        $newVal = $_SESSION['inc'];
        $this->assertEquals($orgVal,$newVal);
    }
}
