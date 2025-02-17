<?php
namespace ChristianBudde\Part\controller\json;


/**
 * Created by PhpStorm.
 * User: budde
 * Date: 8/28/14
 * Time: 1:00 PM
 */

class TypeImpl extends ElementImpl implements Type{

    private $type;

    function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTypeString()
    {
        return $this->type;
    }


    /**
     * @return string
     */
    public function getAsJSONString()
    {
        return json_encode($this->getAsArray());
    }

    /**
     * @return array
     */
    public function getAsArray()
    {
        return array("type" => "type", "type_string" => $this->type);
    }
}