<?php
/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 5/28/12
 * Time: 3:03 PM
 * To change this template use File | Settings | File Templates.
 */
class ExceptionStubScriptImpl implements ChristianBudde\cbweb\Script
{


    /**
     * This function runs the script
     * @param $name string
     * @param $args array | null
     * @throws ScriptHasRunException
     */
    public function run($name, $args)
    {
        throw new ScriptHasRunException($name, $args);
    }
}
