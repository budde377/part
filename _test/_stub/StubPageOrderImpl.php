<?php
require_once dirname(__FILE__) . '/../../_interface/PageOrder.php';

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 6/20/12
 * Time: 1:24 PM
 */
class StubPageOrderImpl implements PageOrder
{

    private $order;
    private $inactiveList = array();

    /**
     * This will return pageOrder. If null is given, it will return top-level
     * order, else if valid page id is given, it will return the order of the
     * sub-list. The return array will, if non-empty, contain instances of Page
     * If invalid id is provided, it will return empty array
     * @param null|Page $parentPage
     * @return array
     */
    public function getPageOrder(Page $parentPage = null)
    {
        $id = null;
        if ($parentPage instanceof Page) {
            $id = $parentPage->getID();
        }
        return isset($this->order[$id]) && !empty($this->order[$id]) &&
            is_array($this->order[$id]) ? $this->order[$id] : array();
    }

    /**
     * This will set the pageOrder of given page ID.
     * There must not be created loops and parent/id must be valid page ID (and existing),
     * else the function will fail and return FALSE. If proper id('s) and no loops created,
     * function will return TRUE
     * @param Page $page
     * @param int $place
     * @param null | Page $parentPage
     * @return bool
     */
    public function setPageOrder(Page $page, $place, Page $parentPage = null)
    {
        return false;
    }

    /**
     * Will return TRUE if the page is active (ie. in order), else FALSE
     * A page is only active if it is attached to root node (null)
     * @param Page $page
     * @return bool
     */
    public function isActive(Page $page)
    {
        return false;
    }

    /**
     * Will list all pages in an array as instances of Page
     * @param int $listMode Must be of ListPageEnum
     * @return array
     */
    public function listPages($listMode = PageOrder::LIST_ALL)
    {
        $returnArray = array();
        if ($listMode == PageOrder::LIST_ACTIVE || $listMode == PageOrder::LIST_ALL) {
            $this->createPageList($returnArray);
        }
        if ($listMode == PageOrder::LIST_INACTIVE || $listMode == PageOrder::LIST_ALL) {
            foreach ($this->inactiveList as $inactivePage) {
                $returnArray[] = $inactivePage;
            }
        }
        return $returnArray;
    }

    /**
     * @param string $id must satisfy syntax of Page id
     * @return bool | Page Returns FALSE on invalid id or other error, else instance of Page
     */
    public function createPage($id)
    {
        return false;
    }

    /**
     * This will delete a page from page order and in general
     * @param Page $page
     * @return bool
     */
    public function deletePage(Page $page)
    {
        return false;
    }

    /**
     * Will deactivate a page and all it's sub pages.
     * The page order remains the same
     * @param Page $page
     * @return void
     */
    public function deactivatePage(Page $page)
    {

    }

    public function setOrder($order)
    {
        $this->order = $order;
    }


    private function createPageList(&$array, Page $parentPage = null)
    {
        $list = $this->getPageOrder($parentPage);
        foreach ($list as $page) {
            $array[] = $page;
            $this->createPageList($array, $page);
        }
    }

    /**
     * @param array $array
     */
    public function setInactiveList($array)
    {
        $this->inactiveList = $array;
    }

    /**
     * @param string $id
     * @return Page | null Page if Page with title is found, else null
     */
    public function getPage($id)
    {
        foreach($this->listPages(PageOrder::LIST_ALL) as $page){
            /** @var $page Page */
            if($page->getID() == $id){
                return $page;
            }
        }
        return null;
    }
}
