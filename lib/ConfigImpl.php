<?php
namespace ChristianBudde\Part;

use ChristianBudde\Part\exception\InvalidXMLException;
use DOMDocument;
use SimpleXMLElement;

/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 5/15/12
 * Time: 9:01 AM
 */
class ConfigImpl implements Config
{
    private $variables;
    private $owner;
    private $domain;
    private $configFile;
    private $rootPath;

    private $templates = null;
    private $templatePath = [];
    private $templateNamespace = [];
    private $pageElements = null;
    private $preTasks = null;
    private $postTasks = null;
    private $mysql = null;
    private $debugMode;
    private $defaultPages;
    private $enableUpdater;
    private $tmpFolderPath;
    private $log;
    private $ajaxTypeHandlers;
    private $defaultTemplate;

    /**
     * @param SimpleXMLElement $configFile
     * @param string $rootPath
     * @throws InvalidXMLException
     */
    public function __construct(SimpleXMLElement $configFile, $rootPath)
    {
        $namespaces = $configFile->getDocNamespaces();

        if (!count($namespaces)) {
            $configFile->addAttribute('xmlns', 'http://christianbud.de/site-config');
        }

        $configFile->asXML();
        $dom = new DOMDocument(1, 'UTF-8');
        $dom->loadXML($configFile->asXML());
        $schema = dirname(__FILE__) . "/../xsd/site-config.xsd";
        libxml_use_internal_errors(true);

        if (!$dom->schemaValidate($schema)) {
            throw new InvalidXMLException('site-config', 'ConfigXML');
        }
        libxml_use_internal_errors(false);

        $this->configFile = $configFile;
        $this->rootPath = $rootPath;
    }


    /**
     * Will return the link to the template file as a string.
     * This should be relative to a root path provided.
     * If the link is not in list, this will return null.
     * @param $name string
     * @return string | null
     */
    public function getTemplate($name)
    {
        $this->setUpTemplate();
        return isset($this->templates[$name]) ? $this->templates[$name] : null;
    }

    /**
     * Will return the default template if defined. Else null.
     * @return string
     */
    public function getDefaultTemplateName()
    {
        $this->setUpTemplate();
        return $this->defaultTemplate;

    }

    /**
     * Will return the default template if defined. Else null.
     * @return string
     */
    public function getDefaultTemplate()
    {
        return $this->getTemplate($this->getDefaultTemplateName());
    }

    /**
     * Will return PostTasks as an array, with the ClassName as key and the link as value.
     * The link should be relative to a root path provided.
     * @return array
     */
    public function getPostTasks()
    {
        if ($this->postTasks != null) {
            return $this->postTasks;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return $this->postTasks = $this->getScripts($this->configFile->postTasks);
    }


    private function getScripts($scriptsXml)
    {

        if (!$scriptsXml) {
            return [];
        }

        $scripts = [];
        foreach ($scriptsXml->class as $scriptClass) {
            $scripts[(string)$scriptClass] = isset($scriptClass['link']) ? $this->rootPath . $scriptClass['link'] : null;
        }

        return $scripts;
    }

    /**
     * Will return pre tasks as an array, with the ClassName as key and the link as value.
     * The link should be relative to a root path provided.
     * @return array
     */
    public function getPreTasks()
    {
        if ($this->preTasks != null) {
            return $this->preTasks;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return $this->preTasks = $this->getScripts($this->configFile->preTasks);
    }



    /**
     * @param string $name name of the pageElement as specified in config
     * @return array | null Array with entrance className, name, path with ClassName,
     * name provided, and absolute path respectively.
     */
    public function getPageElement($name)
    {
        if ($this->pageElements == null) {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->pageElements = $this->buildClasses($this->configFile->pageElements->class);
        }


        return isset($this->pageElements[$name]) ? $this->pageElements[$name] : null;

    }

    private function buildClasses($classesXML)
    {
        if (empty($classesXML)) {
            return [];
        }
        $returnArray = [];
        foreach ($classesXML as $element) {
            $elementArray = [
                'name' => (string)$element['name'],
                'className' => (string)$element];

            if (isset($element['link'])) {
                $elementArray['link'] = $this->getRootPath() . (string)$element['link'];

            }

            $returnArray[(string)$element['name']] = $elementArray;
        }


        return $returnArray;
    }

    public function getMySQLConnection()
    {

        if ($this->mysql != null) {
            return $this->mysql === false ? null : $this->mysql;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        if (empty($this->configFile->MySQLConnection)) {
            $this->mysql = false;
            return null;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $this->mysql = [
            'user' => (string)$this->configFile->MySQLConnection->username,
            'password' => (string)$this->configFile->MySQLConnection->password,
            'database' => (string)$this->configFile->MySQLConnection->database,
            'host' => (string)$this->configFile->MySQLConnection->host,
            'folders' => []];
        /** @noinspection PhpUndefinedFieldInspection */
        if (!empty($this->configFile->MySQLConnection->folders)) {
            /** @noinspection PhpUndefinedFieldInspection */
            foreach ($this->configFile->MySQLConnection->folders->folder as $folder) {
                $this->mysql['folders'][(string)$folder['name']] = (string)$folder['path'];
            }
        }

        return $this->mysql;

    }

    /**
     * Will return a array containing all possible templates by name.
     * @return array
     */
    public function listTemplateNames()
    {
        $this->setUpTemplate();
        $ret = array();
        foreach ($this->templates as $key => $val) {
            $ret[] = $key;
        }
        return $ret;
    }


    /**
     * Will return an array with default pages. Pages hardcoded into the website.
     * The array will have the page title as key and another array, containing alias', as value.
     * @return array
     */
    public function getDefaultPages()
    {
        if ($this->defaultPages != null) {
            return $this->defaultPages;
        }
        $this->defaultPages = [];
        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->configFile->defaultPages->getName()) {
            /** @noinspection PhpUndefinedFieldInspection */
            foreach ($this->configFile->defaultPages->page as $page) {
                $title = (string)$page;
                $this->defaultPages[$title]["template"] = (string)$page["template"];
                $this->defaultPages[$title]["alias"] = (string)$page["alias"];
                $this->defaultPages[$title]["id"] = (string)$page["id"];
            }

        }

        return $this->defaultPages;
    }

    private function setUpTemplate()
    {
        if ($this->templates !== null) {
            return;
        }

        $this->templates = [];
        /** @noinspection PhpUndefinedFieldInspection */
        $templates = $this->configFile->templates;
        if (!empty($templates)) {
            $this->setUpTemplateHelper($templates);
            return;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        if (empty($this->configFile->templateCollection)) {
            return;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($this->configFile->templateCollection->templates as $templates) {
            $this->setUpTemplateHelper($templates);
        }

    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        if ($this->debugMode != null) {
            return $this->debugMode;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        if (!$this->configFile->debugMode->getName()) {
            return $this->debugMode = false;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return $this->debugMode = (string)$this->configFile->debugMode == "true";
    }


    /**
     * @return string Root path
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * @return bool
     */
    public function isUpdaterEnabled()
    {
        if ($this->enableUpdater !== null) {
            return $this->enableUpdater;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        if (!$this->configFile->enableUpdater->getName()) {
            return $this->enableUpdater = true;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        return $this->enableUpdater = (string)$this->configFile->enableUpdater == "true";

    }

    /**
     * @return string String containing the domain (name.ext)
     */
    public function getDomain()
    {
        if ($this->domain !== null) {
            return $this->domain;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->domain = (string)$this->configFile->siteInfo->domain['name'] . "." . (string)$this->configFile->siteInfo->domain['extension'];
    }

    /**
     * @return array containing owner information
     */
    public function getOwner()
    {
        if ($this->owner !== null) {
            return $this->owner;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        /** @noinspection PhpUndefinedFieldInspection */
        return $this->owner = array(
            'name' => (string)$this->configFile->siteInfo->owner['name'],
            'mail' => (string)$this->configFile->siteInfo->owner['mail'],
            'username' => (string)$this->configFile->siteInfo->owner['username']
        );
    }

    /**
     * Will path relative to project root to templates.
     * @param string $name The name of the template
     * @return string | null Null if template not defined
     */
    public function getTemplateFolderPath($name)
    {
        $this->setUpTemplate();
        return !isset($this->templatePath[$name]) ? null : "{$this->rootPath}/{$this->templatePath[$name]}";
    }


    /**
     * @return string Path to the tmp folder
     */
    public function getTmpFolderPath()
    {
        if ($this->tmpFolderPath !== null) {
            return $this->tmpFolderPath;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $result = $this->tmpFolderPath = (string)$this->configFile->tmpFolder['path'];
        return $result === null ? $this->tmpFolderPath = "" : $result;
    }

    /**
     * @return string Path to the error log.
     */
    public function getLogPath()
    {
        if ($this->log !== null) {
            return $this->log;
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $result = $this->log = (string)$this->configFile->log['path'];
        return $result === null ? $this->log = "" : $result;

    }

    /**
     * Will return AJAXTypeHandlers as an array, with the num key and an array containing "class_name" and "path" as value.
     * The link should be relative to a root path provided.
     * @return array
     */
    public function getAJAXTypeHandlers()
    {
        if ($this->ajaxTypeHandlers != null) {
            return $this->ajaxTypeHandlers;
        }

        $this->ajaxTypeHandlers = array();

        /** @noinspection PhpUndefinedFieldInspection */
        if (!$this->configFile->AJAXTypeHandlers->getName()) {
            return $this->ajaxTypeHandlers;
        }


        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($this->configFile->AJAXTypeHandlers->class as $handler) {
            $class_array = array("class_name" => (string)$handler);
            if (isset($handler['link'])) {
                $class_array['link'] = $this->rootPath . "/" . $handler['link'];
            }
            $this->ajaxTypeHandlers[] = $class_array;
        }
        return $this->ajaxTypeHandlers;
    }

    /**
     * @return array
     */
    public function getVariables()
    {

        if ($this->variables !== null) {
            return $this->variables;
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $variables = $this->configFile->variables->var;
        $this->variables = [];

        if ($variables == null) {
            return $this->variables;
        }

        foreach ($variables as $var) {
            $this->variables[(string)$var['key']] = (string)$var['value'];
        }

        return $this->variables;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->getVariables()[$offset]);
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->getVariables()[$offset];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {

    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {

    }


    /**
     * Lists the folders where to look for other templates.
     * @return string[]
     */
    public function listTemplateFolders()
    {

        $this->setUpTemplate();
        $result = [];
        foreach ($this->templateNamespace as $ns => $paths) {
            foreach ($paths as $path) {
                $path = $this->getRootPath() . "/" . $path;
                if ($ns == "") {
                    $result[] = $path;
                } else {
                    $result[] = ['path' => $path, 'namespace' => $ns];
                }
            }
        }

        return $result;
    }

    private function setUpTemplateHelper($templates)
    {

        $namespace = (string)$templates['namespace'];

        if (!isset($this->templateNamespace[$namespace])) {
            $this->templateNamespace[$namespace] = [];
        }
        $this->templateNamespace[$namespace][] = (string)$templates['path'];

        foreach ($templates->template as $template) {
            $this->templates[(string)$template] = (string)$template['filename'];
            $this->templatePath[(string)$template] = (string)$templates['path'];
            if(((string) $template['default']) == 'true'){
                $this->defaultTemplate = (string) $template;
            }

        }
    }
}
