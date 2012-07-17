<?php
require_once dirname(__FILE__) . '/../_class/OptimizerFactoryImpl.php';

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 6/11/12
 * Time: 9:41 AM
 * To change this template use File | Settings | File Templates.
 */
class OptimizerFactoryImplTest extends PHPUnit_Framework_TestCase
{


    public function testWillReturnNullIfOptimizerIsNil()
    {
        $configXML = simplexml_load_string('<config></config>');
        $config = new ConfigImpl($configXML, dirname(__FILE__) . '/');
        $optimizerFactory = new OptimizerFactoryImpl($config);
        $element = $optimizerFactory->getOptimizer('NilElement');
        $this->assertNull($element, 'Did not return null on element not in list');
    }

    public function testWillReturnOptimizerIfElementInList()
    {
        /** @var $configXML SimpleXMLElement */
        $configXML = simplexml_load_string('
        <config>
        <optimizers>
            <class name="someElement" link="_stub/NullOptimizerImpl.php">NullOptimizerImpl</class>
        </optimizers>
        </config>');
        $config = new ConfigImpl($configXML, dirname(__FILE__) . '/');
        $optimizerFactory = new OptimizerFactoryImpl($config);
        $element = $optimizerFactory->getOptimizer('someElement');
        $this->assertTrue(is_object($element), 'Did not return an object');
        $this->assertInstanceOf('NullOptimizerImpl', $element, 'Did not return element of right instance.');

    }

    public function testWillReturnThrowExceptionIfElementNotInstanceOfOptimizer()
    {
        /** @var $configXML SimpleXMLElement */
        $configXML = simplexml_load_string('
        <config>
        <optimizers>
            <class name="someElement" link="_stub/StubScriptImpl.php">StubScriptImpl</class>
        </optimizers>
        </config>');
        $config = new ConfigImpl($configXML, dirname(__FILE__) . '/');
        $optimizerFactory = new OptimizerFactoryImpl($config);
        $exceptionWasThrown = false;
        try {
            $element = $optimizerFactory->getOptimizer('someElement');
        } catch (Exception $exception) {
            /** @var $exception ClassNotInstanceOfException */
            $this->assertInstanceOf('ClassNotInstanceOfException', $exception);
            $exceptionWasThrown = true;
            $this->assertEquals('StubScriptImpl', $exception->getClass(), 'Was not expected class');
            $this->assertEquals('Optimizer', $exception->getExpectedInstance(), 'Was not expected instance');

        }

        $this->assertTrue($exceptionWasThrown, 'No exception was thrown');


    }

    public function testWillThrowExceptionIfInvalidLink()
    {
        $configXML = simplexml_load_string('
        <config>
        <optimizers>
            <class name="someElement" link="notAValidLink">OptimizerNullImpl</class>
        </optimizers>
        </config>');
        $config = new ConfigImpl($configXML, dirname(__FILE__) . '/');
        $optimizerFactory = new OptimizerFactoryImpl($config);
        $this->setExpectedException('FileNotFoundException');
        $optimizerFactory->getOptimizer('someElement');

    }

    public function testWillThrowExceptionIfClassNotDefined()
    {
        $configXML = simplexml_load_string('
        <config>
        <optimizers>
            <class name="someElement" link="_stub/NullOptimizerImpl.php">NotAValidClassName</class>
        </optimizers>
        </config>');
        $config = new ConfigImpl($configXML, dirname(__FILE__) . '/');
        $optimizerFactory = new OptimizerFactoryImpl($config);
        $this->setExpectedException('ClassNotDefinedException');
        $optimizerFactory->getOptimizer('someElement');

    }
}
