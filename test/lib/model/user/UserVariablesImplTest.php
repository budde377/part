<?php
/**
 * User: budde
 * Date: 10/22/13
 * Time: 3:53 PM
 */
namespace ChristianBudde\Part\model\user;

use ChristianBudde\Part\StubBackendSingletonContainerImpl;
use ChristianBudde\Part\util\CustomDatabaseTestCase;
use ChristianBudde\Part\util\db\StubDBImpl;

class UserVariablesImplTest extends CustomDatabaseTestCase
{

    private $db;
    /** @var  UserVariablesImpl */
    private $existingVariables;
    /** @var  UserVariablesImpl */
    private $nonExistingVariables;
    /** @var  UserVariablesImpl */
    private $nonExistingVariablesNonExistingUser;
    /** @var  User */
    private $existingUser;
    /** @var  User */
    private $existingUser2;
    /** @var  \ChristianBudde\Part\model\user\User */
    private $nonExistingUser;

    function __construct($dataset = null)
    {
        parent::__construct($GLOBALS['MYSQL_XML_DIR'] .  '/UserVariablesImplTest.xml');
    }

    public function setUp()
    {
        parent::setUp();
        $this->db = new StubDBImpl();
        $this->db->setConnection(self::$pdo);
        $container = new StubBackendSingletonContainerImpl();
        $container->setDBInstance($this->db);
        $this->existingUser = new UserImpl($container, 'testuser');
        $this->existingVariables = new UserVariablesImpl($this->db, $this->existingUser);
        $this->existingUser2 = new UserImpl($container, 'testuser2');
        $this->nonExistingUser = new UserImpl($container, 'nosuchuser');
        $this->nonExistingVariables = new UserVariablesImpl($this->db, $this->existingUser2);
        $this->nonExistingVariablesNonExistingUser = new UserVariablesImpl($this->db, $this->nonExistingUser);

    }

    public function testListKeysWillReturnArray()
    {
        $ar = $this->existingVariables->listKeys();
        $this->assertTrue(is_array($ar));
        $ar = $this->nonExistingVariables->listKeys();
        $this->assertTrue(is_array($ar));

    }

    public function testListKeysWillHaveRightContent()
    {
        $ar = $this->existingVariables->listKeys();
        $this->assertEquals(2, count($ar));
        $this->assertEquals('test1', $ar[0]);
        $this->assertEquals('test2', $ar[1]);

        $ar = $this->nonExistingVariables->listKeys();
        $this->assertEquals(0, count($ar));
    }

    public function testGetListOfNonExistingUserReturnsArray()
    {
        $var = new UserVariablesImpl($this->db, $this->nonExistingUser);
        $this->assertTrue(is_array($var->listKeys()));
        $this->assertEquals(0, count($var->listKeys()));
    }

    public function testGetValueWillGetValueIfExist()
    {
        $this->assertEquals("val1", $this->existingVariables->getValue("test1"));
        $this->assertEquals("val2", $this->existingVariables->getValue("test2"));
    }

    public function testGetValueWillReturnNullIfNotExist()
    {
        $this->assertNull($this->existingVariables->getValue("NonExistingKey"));
    }

    public function testHasKeyWillReturnTrueIfHasKey()
    {
        $this->assertTrue($this->existingVariables->hasKey("test1"));
    }

    public function testHasKeyWillReturnFalseIfDoesNotHasKey()
    {
        $this->assertFalse($this->existingVariables->hasKey("NonExistingKey"));
    }

    public function testRemoveKeyWillRemoveKey()
    {
        $this->existingVariables->removeKey("test1");
        $this->assertFalse($this->existingVariables->hasKey("test1"));
        $this->assertEquals(1, count($this->existingVariables->listKeys()));
    }

    public function testRemoveOfNonExistingDoesNotChangeAnything()
    {
        $this->existingVariables->removeKey("nonExisting");
        $this->assertEquals(2, count($this->existingVariables->listKeys()));
    }

    public function testRemoveIsPersistent()
    {
        $this->existingVariables->removeKey("test1");
        $var = new UserVariablesImpl($this->db, $this->existingUser);
        $this->assertEquals(1, count($var->listKeys()));
        $this->assertFalse($var->hasKey("test1"));
    }

    public function testSetValueWillDoJustThat()
    {
        $this->existingVariables->setValue("test3", "val3");
        $this->assertEquals(3, count($this->existingVariables->listKeys()));
        $this->assertTrue($this->existingVariables->hasKey("test3"));
        $this->assertEquals("val3", $this->existingVariables->getValue("test3"));
    }

    public function testSetValueWilLBePersistent()
    {
        $this->existingVariables->setValue("test3", "val3");
        $var = new UserVariablesImpl($this->db, $this->existingUser);
        $this->assertEquals(3, count($var->listKeys()));
        $this->assertTrue($var->hasKey("test3"));
        $this->assertEquals("val3", $var->getValue("test3"));
    }

    public function testSetValueWillOverwrite()
    {
        $this->existingVariables->setValue("test2", "val2000");
        $this->assertEquals(2, count($this->existingVariables->listKeys()));
        $this->assertTrue($this->existingVariables->hasKey("test2"));
        $this->assertEquals("val2000", $this->existingVariables->getValue("test2"));
    }

    public function testSetValueOfNonExistingUserWillNotWork()
    {
        $this->nonExistingVariablesNonExistingUser->setValue("test", "lol");
        $this->assertFalse($this->nonExistingVariablesNonExistingUser->hasKey("test"));
    }

    public function testForeachWillTraverse()
    {
        $seen1 = $seen2 = false;
        $i = 0;
        foreach ($this->existingVariables as $key => $var) {
            $seen1 = $seen1 || ($var == "val1" && $key == "test1");
            $seen2 = $seen2 || ($var == "val2" && $key == "test2");
            $i++;
        }
        $this->assertEquals(2, $i);
        $this->assertTrue($seen1);
        $this->assertTrue($seen2);
    }


    public function testSetValueWilLBePersistentOverPageIdChange()
    {
        $this->existingVariables->setValue("test3", "val3");
        $this->existingUser->setUsername("some_other_id");
        $this->assertEquals('some_other_id', $this->existingUser->getUsername());
        $this->assertEquals(['test1', 'test2', 'test3'], $this->existingVariables->listKeys());
        $this->assertEquals("val3", $this->existingVariables->getValue("test3"));
    }


    public function testCanSetValueAfterPageIdChange()
    {
        $this->existingUser->setUsername("some_other_id");
        $this->existingVariables->setValue("test3", "val3");
        $this->assertEquals('some_other_id', $this->existingUser->getUsername());
        $this->assertEquals(['test1', 'test2', 'test3'], $this->existingVariables->listKeys());
        $this->assertEquals("val3", $this->existingVariables->getValue("test3"));
    }

}