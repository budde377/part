<?php

/**
 * Created by PhpStorm.
 * User: budde
 * Date: 5/13/14
 * Time: 10:32 AM
 */
class LogFileImplTest extends PHPUnit_Framework_TestCase
{
    /** @var  LogFileImpl */
    private $logFile;
    /** @var  DumpFile */
    private $dumpFile;
    /** @var Folder */
    private $folder;

    public function setUp(){
        $this->folder = new FolderImpl(dirname(__FILE__)."/stubs/logFolder");
        $this->logFile= new LogFileImpl(dirname(__FILE__)."/stubs/logFile");
        $this->logFile->delete();

    }

    /* THIS TEST ASSUMES THAT THE LogFileImpl EXTENDS FileImpl. */

    public function testLogWillLogToFile()
    {
        $msg = "SOME LOG MSG";
        $this->assertEquals("", $this->logFile->getContents());
        $this->logFile->log($msg, LogFile::LOG_LEVEL_ERROR);
        $this->assertContains($msg, $this->logFile->getContents());
    }

    public function testListLogListsLog()
    {
        $msg1 = uniqid("msg");
        $msg2 = uniqid("msg");

        $this->logFile->log($msg1, LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log($msg2, LogFile::LOG_LEVEL_WARNING);

        $ar = $this->logFile->listLog();

        $this->assertTrue(is_array($ar));
        $this->assertEquals(2, count($ar));
        $this->assertArrayHasKey(0, $ar);
        $this->assertArrayHasKey(1, $ar);

        $ar1 = $ar[0];
        $ar2 = $ar[1];

        $this->assertArrayHasKey("message", $ar1);
        $this->assertArrayHasKey("message", $ar2);

        $this->assertArrayHasKey("time", $ar2);
        $this->assertArrayHasKey("time", $ar2);

        $this->assertArrayHasKey("level", $ar2);
        $this->assertArrayHasKey("level", $ar2);

        $this->assertEquals($msg1, $ar1["message"]);
        $this->assertEquals($msg2, $ar2["message"]);

        $this->assertEquals(LogFile::LOG_LEVEL_WARNING, $ar2["level"]);
        $this->assertEquals(LogFile::LOG_LEVEL_ERROR, $ar1["level"]);

    }

    public function testNewLinesAreOk()
    {
        $msg = "some \n message";
        $this->logFile->log($msg, LogFile::LOG_LEVEL_WARNING);
        $ar = $this->logFile->listLog();
        $this->assertEquals(1, count($ar));
        $this->assertEquals($msg, $ar[0]["message"]);
    }

    public function testLevelsAreRespected()
    {
        $msg1 = uniqid("msg");
        $msg2 = uniqid("msg");

        $this->logFile->log($msg1, LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log($msg2, LogFile::LOG_LEVEL_WARNING);

        $ar1 = $this->logFile->listLog(LogFile::LOG_LEVEL_ERROR);
        $ar2 = $this->logFile->listLog(LogFile::LOG_LEVEL_WARNING);
        $ar3 = $this->logFile->listLog(LogFile::LOG_LEVEL_DEBUG);
        $ar4 = $this->logFile->listLog(LogFile::LOG_LEVEL_ERROR | LogFile::LOG_LEVEL_WARNING | LogFile::LOG_LEVEL_DEBUG);

        $this->assertEquals(1, count($ar1));
        $this->assertEquals(1, count($ar2));
        $this->assertEquals(0, count($ar3));
        $this->assertEquals(2, count($ar4));

        $this->assertEquals($msg1, $ar1[0]["message"]);
        $this->assertEquals($msg2, $ar2[0]["message"]);
        $this->assertEquals($msg1, $ar4[0]["message"]);
        $this->assertEquals($msg2, $ar4[1]["message"]);

    }

    public function testLogWillReturnNull()
    {
        $this->assertNull($this->logFile->log("SOME MSG", LogFile::LOG_LEVEL_WARNING));
    }

    public function testLogWillReturnDumpFile()
    {
        $this->dumpFile = $this->logFile->log("MSG", LogFile::LOG_LEVEL_WARNING, true);
        $this->assertInstanceOf("DumpFile", $this->dumpFile);
        $this->assertTrue($this->dumpFile->exists());
        $this->assertGreaterThan(0, strlen($this->dumpFile->getContents()));
    }

    public function testListLogWillContainDumpFile()
    {
        $d = $this->logFile->log("Some msg", LogFile::LOG_LEVEL_WARNING, true);
        $ar = $this->logFile->listLog();
        $ar1 = $ar[0];
        $this->assertArrayHasKey("dumpfile", $ar1);
        $this->assertInstanceOf("DumpFile", $d2 = $ar1["dumpfile"]);
        $this->assertTrue($d === $d2);
        $this->dumpFile = $d;
    }


    public function testListLogDumpFileAndReuseInstance()
    {
        $d = $this->logFile->log("Some msg", LogFile::LOG_LEVEL_WARNING, true);
        $ar = $this->logFile->listLog();
        $ar1 = $ar[0];
        $this->assertTrue($d === $ar1["dumpfile"]);
        $ar = $this->logFile->listLog();
        $ar2 = $ar[0];
        $this->assertTrue($ar2["dumpfile"] === $ar1["dumpfile"]);
        $this->dumpFile = $d;
    }

    public function testListLogWillRespectTime()
    {
        $msg1 = uniqid();
        $msg2 = uniqid();

        $this->logFile->log($msg1, LogFile::LOG_LEVEL_ERROR);
        sleep(1);
        $t = time();
        $this->logFile->log($msg2, LogFile::LOG_LEVEL_ERROR);
        $ar = $this->logFile->listLog(LogFile::LOG_LEVEL_ALL, $t);

        $this->assertEquals(1, count($ar));
        $this->assertEquals($msg2, $ar[0]["message"]);

    }

    public function testClearLogWillClearTheLog()
    {
        $this->logFile->log("SOME SMG", LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log("SOME SMG", LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log("SOME SMG", LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log("SOME SMG", LogFile::LOG_LEVEL_ERROR);

        $this->assertGreaterThan(0, strlen($this->logFile->getContents()));
        $this->logFile->clearLog();
        $this->assertEquals(0, strlen($this->logFile->getContents()));
        $this->assertEquals(0, count($this->logFile->listLog()));
    }

    public function testCanWriteAfterClear()
    {
        $this->logFile->log("SOME MSG", LogFile::LOG_LEVEL_ERROR);
        $this->logFile->clearLog();
        $this->logFile->log("SOME MSG", LogFile::LOG_LEVEL_ERROR);
        $this->logFile->log("SOME MSG", LogFile::LOG_LEVEL_ERROR);
        $this->assertEquals(2, count($this->logFile->listLog()));
    }

    public function testClearWillDeleteDumpFiles()
    {
        $this->dumpFile = $f = $this->logFile->log("SOME MSG", LogFile::LOG_LEVEL_ERROR, true);
        $this->assertTrue($f->exists());
        $this->logFile->clearLog();
        $this->assertFalse($f->exists());
    }


    public function testAbsolutePathIsTheSameWithNewInstanceOfLogFile()
    {
        $d = $this->logFile->log("LOL", LogFile::LOG_LEVEL_DEBUG, true);
        $logfile = new LogFileImpl($this->logFile->getAbsoluteFilePath());
        $dl = $logfile->listLog();
        /** @var DumpFile $d2 */
        $d2 = $dl[0]["dumpfile"];
        $this->assertEquals($d->getAbsoluteFilePath(), $d2->getAbsoluteFilePath());
    }

    public function testClearLogWithNewInstanceOfLogFile()
    {
        $d = $this->logFile->log("LOL", LogFile::LOG_LEVEL_DEBUG, true);
        $logfile = new LogFileImpl($this->logFile->getAbsoluteFilePath());
        $logfile->clearLog();
        $this->assertFalse($d->exists());

    }

   public function testLogWillCreateFolderIfNessesary(){
        $l = new LogFileImpl($this->folder->getAbsolutePath().'/log/log/logFile');
        $this->assertFalse($this->folder->exists());
        $l->log("LOL", LogFile::LOG_LEVEL_ERROR);
        $this->assertTrue($this->folder->exists());

    }


    public function tearDown()
    {
        $this->logFile->delete();
        if ($this->dumpFile != null) {
            $this->dumpFile->delete();
        }
        @$this->folder->delete(Folder::DELETE_FOLDER_RECURSIVE);
    }


}