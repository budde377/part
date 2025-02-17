<?php
namespace ChristianBudde\Part\controller\json;


use PHPUnit_Framework_TestCase;

/**
 * Created by PhpStorm.
 * User: budde
 * Date: 8/28/14
 * Time: 2:00 PM
 */
class ParserImplTest extends PHPUnit_Framework_TestCase
{

    /** @var  ParserImpl */
    private $parser;

    private $function1Name = "function1";
    /** @var  TypeImpl */
    private $function1Target;
    /** @var  JSONFunctionImpl */
    private $function1;

    private $function2Name = "function2";
    /** @var  JSONFunctionImpl */
    private $function2;


    private $objectName = "SomeObject";
    /** @var Object */
    private $object1;
    private $object2;


    private $typeString = "someType";
    /** @var  TypeImpl */
    private $type;
    /** @var  ResponseImpl */
    private $response;
    private $responseType = Response::RESPONSE_TYPE_ERROR;
    private $responseErrorCode = Response::ERROR_CODE_MALFORMED_REQUEST;

    /** @var  CompositeFunctionImpl */
    private $compositeFunction;

    public function setUp()
    {
        $this->object1 = new ObjectImpl($this->objectName);
        $this->object2 = new ObjectImpl($this->objectName);

        $this->parser = new ParserImpl();
        $this->function1Target = new TypeImpl("SomeTarget");
        $this->function1 = new JSONFunctionImpl($this->function1Name, $this->function1Target, [null,null,null,$this->object1]);

        $this->function2 = new JSONFunctionImpl($this->function2Name, $this->function1, [null,null,'v3']);
        $this->function2->setId(123);



        $this->object1->setVariable("key0", "val0");
        $this->object1->setVariable("key1", $this->object2);
        $this->type = new TypeImpl($this->typeString);
        $this->response = new ResponseImpl($this->responseType, $this->responseErrorCode);


        $this->compositeFunction = new CompositeFunctionImpl($this->function1Target);

    }


    public function testParserParsesObject()
    {
        /** @var Object $obj */
        $obj = $this->parser->parse($this->object1->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Object', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->object1->getAsJSONString());
        $this->assertEquals($this->object2, $obj->getVariable('key1'));
    }

    public function testParserParsesFunction()
    {
        /** @var JSONFunction $obj */
        $obj = $this->parser->parse($s = $this->function1->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\JSONFunction', $obj);
        $this->assertEquals($this->object1, $obj->getArg(3));
        $this->assertEquals($obj->getAsJSONString(), $this->function1->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Target', $obj->getTarget());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Type', $obj->getTarget());
    }

    public function testParserParsesFunction2()
    {
        /** @var JSONFunction $obj */
        $obj = $this->parser->parse($this->function2->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\JSONFunction', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->function2->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Target', $obj->getTarget());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\JSONFunction', $obj->getTarget());

    }

    public function testParserParsesResponse()
    {
        /** @var Response $obj */
        $this->response->setPayload($this->object1);
        $obj = $this->parser->parse($this->response->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Response', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->response->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Object', $obj->getPayload());

    }

    public function testParserParsesResponseWithOOutPayload()
    {
        /** @var Response $obj */
        $obj = $this->parser->parse($this->response->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Response', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->response->getAsJSONString());

    }

    public function testsParserParsesType()
    {
        $obj = $this->parser->parse($this->type->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\Type', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->type->getAsJSONString());
    }

    public function testParserParseCompositeFunction()
    {
        $this->compositeFunction = new CompositeFunctionImpl($this->compositeFunction->getTarget(), array_merge($this->compositeFunction->listFunctions(), [$this->function1]));
        $this->compositeFunction->setId(123);
        $obj = $this->parser->parse($this->compositeFunction->getAsJSONString());
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\CompositeFunction', $obj);
        $this->assertEquals($obj->getAsJSONString(), $this->compositeFunction->getAsJSONString());
    }

    public function testParserParseCompositeFunctionNotInRoot()
    {
        $args = $this->function1->getArgs();
        $args[0] = $this->compositeFunction;
        $this->function1 = new JSONFunctionImpl($this->function1->getName(), $this->function1->getTarget(), $args);
        /** @var JSONFunction $obj */
        $obj = $this->parser->parse($f = $this->function1->getAsJSONString());
        $obj = $obj->getArg(0);
        $this->assertInstanceOf('ChristianBudde\Part\controller\json\CompositeFunction', $obj);
        $this->assertEquals($obj->getAsArray(), $this->compositeFunction->getAsArray());
    }

    public function testArrayWithTypeIsParsedCorrectly()
    {
        $array = array('type' => $this->function1);
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

    public function testArrayWithMissingEntriesIsParsedCorrectlyOnFunction()
    {
        $array = array('type' => 'function');
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

    public function testArrayWithMissingEntriesIsParsedCorrectlyOnType()
    {
        $array = array('type' => 'type');
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

    public function testArrayWithMissingEntriesIsParsedCorrectlyOnObject()
    {
        $array = array('type' => 'object');
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

    public function testArrayWithMissingEntriesIsParsedCorrectlyOnResponse()
    {
        $array = array('type' => 'response');
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

    public function testArrayWithMissingEntriesIsParsedCorrectlyOnCompositeFunction()
    {
        $array = array('type' => 'composite_function');
        $result = $this->parser->parse(json_encode($array));
        $this->assertEquals($array, $result);
    }

}