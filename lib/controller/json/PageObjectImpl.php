<?php
namespace ChristianBudde\cbweb\controller\json;

use ChristianBudde\cbweb\model\page\Page;

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 24/01/13
 * Time: 09:25
 * To change this template use File | Settings | File Templates.
 */
class PageObjectImpl extends ObjectImpl
{
    /**
     * @param Page $domainLibrary
     */
    function __construct(Page $domainLibrary)
    {
        parent::__construct('page');
        $this->setVariable('id',$domainLibrary->getID());
        $this->setVariable('title',$domainLibrary->getTitle());
        $this->setVariable('template',$domainLibrary->getTemplate());
        $this->setVariable('alias',$domainLibrary->getAlias());
        $this->setVariable('hidden',$domainLibrary->isHidden());
        $this->setVariable('editable', $domainLibrary->isEditable());
    }


}
