<?php
require_once dirname(__FILE__).'/../../_interface/Site.php';

class StubSiteImpl implements Site{

    private $content = array();
    private $variables;
    private $lastMod;

    /**
     * @param mixed $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }


    /**
     * Returns and reuses instance of site scoped Content
     * @param string $id
     * @return Content
     */
    public function getContent($id = "")
    {
        return isset($this->content[$id])?$this->content[$id]:$this->content[$id] = new StubContentImpl();
    }

    /**
     * Returns and reuses instance of site scoped variables
     * @return Variables
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Returns last modified timestamp, NULL if site hasn't been modified
     * @return int | null
     */
    public function lastModified()
    {
        return $this->lastMod;
    }

    /**
     * "Modifies" the site by changing the last modified timestamp to now
     * @return int The new timestamp
     */
    public function modify()
    {
        return $this->lastMod = time();
    }
}

