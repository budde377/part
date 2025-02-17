<?php
namespace ChristianBudde\Part\exception;

use Exception;

/**
 * User: budde
 * Date: 5/30/12
 * Time: 10:53 PM
 */
class ClassNotInstanceOfException extends Exception
{

    private $class;
    private $expectedInstance;

    /**
     * @param string $class
     * @param string $expectedInstance
     */
    public function __construct($class, $expectedInstance)
    {
        $this->class = $class;
        $this->expectedInstance = $expectedInstance;

        parent::__construct("ClassNotInstanceOfException: Class \"$class\" was not instance of \"$expectedInstance\"");
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getExpectedInstance()
    {
        return $this->expectedInstance;
    }
}
