<?php
namespace ChristianBudde\Part\util\file;



/**
 * Created by JetBrains PhpStorm.
 * User: budde
 * Date: 6/13/12
 * Time: 6:00 PM
 * To change this template use File | Settings | File Templates.
 */
class JSRegisterImpl implements JSRegister
{


    private $registeredFiles = array();


    /**
     * Will register a file
     * @param JSFile $file
     * @return void
     */
    public function registerJSFile(JSFile $file)
    {
        $match = false;
        foreach ($this->registeredFiles as $f) {
            $match = $this->compareFiles($f, $file);
        }
        if (!$match) {
            $this->registeredFiles[] = $file;
        }
    }


    /**
     * Get an array with the registered files
     * @return array
     */
    public function getRegisteredFiles()
    {
        return $this->registeredFiles;
    }


    private function compareFiles(File $file1, File $file2)
    {
        return $file1->getAbsoluteFilePath() == $file2->getAbsoluteFilePath();
    }
}
