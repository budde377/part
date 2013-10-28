<?php
require_once dirname(__FILE__).'/../../_class/PageElementImpl.php';
/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 13/12/12
 * Time: 01:38
 */
class CheckInitializedPageElementImpl extends PageElementImpl
{


    /**
     * Will set up the page element.
     * If you want to ensure that you register some files, this would be the place to do this.
     * This should always be called before generateContent, at the latest right before.
     * @return void
     */
    public function setUpElement()
    {
        parent::setUpElement();
        if(!isset($_SESSION['initialized'])){
            $_SESSION['initialized'] = 0;
        }
        $_SESSION['initialized']++;
    }
}
