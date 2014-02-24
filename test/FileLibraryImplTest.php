<?php
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 2/22/14
 * Time: 3:15 PM
 */

class FileLibraryImplTest extends PHPUnit_Framework_TestCase{

    /** @var  Folder */
    private $testDir;

    private $testString = "testString";

    private $config;

    /** @var  FileLibraryImpl */
    private $lib;

    /** @var  User */
    private $user;
    /** @var  User */
    private $user2;

    public function setUp(){
        $this->user = new StubUserImpl();
        $this->user2 = new StubUserImpl();

        $this->testDir = new FolderImpl(dirname(__FILE__)."/stubs/testFolderFileLibrary");
        $this->testDir->create();
        $this->config = new StubConfigImpl();

        $this->lib = new FileLibraryImpl($this->testDir);
    }

    public function setupExistingFiles(){
        $f = new FileImpl($this->testDir->getAbsolutePath()."/test1.txt");
        $f->write($this->testString);
        return $this->lib->addToLibrary($this->user, $f);
    }


    public function testAddFileAddsFile(){
        $this->assertEquals(0, count($this->lib->getFileList()));
        $this->setupExistingFiles();
        $this->assertEquals(1, count($this->lib->getFileList()));
        $this->assertEquals(1, count($this->lib->getFileList($this->user)));
        $this->assertEquals(0, count($this->lib->getFileList($this->user2)));
    }

    public function testAddFileAddsUniqueName(){
        $f = $this->setupExistingFiles();
        $f2 = $this->lib->addToLibrary($this->user, $f);
        $this->assertNotEquals($f->getFilename(), $f2->getFilename());
        $this->assertTrue($f->exists());
        $this->assertTrue($f2->exists());
    }


    public function testPreservesFilename(){
        $f = $this->createFile("test.txt", time());
        $f2 = $this->lib->addToLibrary($this->user, $f);
        $this->assertEquals($f->getExtension(), $f2->getExtension());
    }

    public function testContainsFileReturnsTrueIfContainsFile(){
        $f = $this->setupExistingFiles();
        $this->assertTrue($this->lib->containsFile($f));
    }

    public function testContainsFileReturnsFalseIfFileDoesntExist(){
        $f = $this->setupExistingFiles();
        $f->delete();
        $this->assertFalse($this->lib->containsFile($f));
    }

    public function testContainsFileReturnsFalseIfNotContainsFile(){
        $f = new FileImpl(__FILE__);
        $this->assertFalse($this->lib->containsFile($f));
    }

    public function testFileListWillReturnArrayContainingFile(){
        $this->setupExistingFiles();
        $ar = $this->lib->getFileList();
        $this->assertEquals(1, count($ar));

        $this->lib->addToLibrary($this->user, $this->createFile(time(), time()));

        $ar = $this->lib->getFileList();
        $this->assertEquals(2, count($ar));
    }

    public function testFileListWillReturnEmptyArrayOnUserWithNoFiles(){
        $ar = $this->lib->getFileList($this->user);
        $this->assertTrue(is_array($ar));
        $this->assertEquals(0, count($ar));
    }


    public function testAddToWhitelistWillReturnFalseIfFileNotInLibrary(){
        $f = $this->createFile(time(), time());
        $this->assertFalse($this->lib->addToWhitelist($f));
    }

    public function testAddToWhielistWillReturnTrueOnSuccess(){
        $f = $this->setupExistingFiles();
        $this->assertFalse($this->lib->whitelistContainsFile($f));
        $this->assertTrue($this->lib->addToWhitelist($f));
        $this->assertTrue($this->lib->whitelistContainsFile($f));
    }


    public function testGetWhitelistReturnsWhitelist(){
        $f = $this->setupExistingFiles();
        $this->assertTrue($this->lib->addToWhitelist($f));
        $whitelist = $this->lib->getWhitelist();
        $this->assertEquals(1, count($whitelist));
        /** @var File $o */
        $o = $whitelist[0];
        $this->assertEquals($f->getAbsoluteFilePath(), $o->getAbsoluteFilePath());
    }


    public function testWhitelistIsPersistent(){
        $f = $this->setupExistingFiles();
        $this->lib->addToWhitelist($f);
        $whitelist = $this->lib->getWhitelist();
        $this->assertEquals(1, count($whitelist));
        $lib = new FileLibraryImpl($this->testDir);
        $this->assertEquals(1, count($lib->getWhitelist()));

    }


    public function testRemoveFromWhitelistWillReturnFalseIfNotInList(){
        $f = $this->setupExistingFiles();
        $this->assertFalse($this->lib->removeFromWhitelist($f));
    }

    public function testRemoveFromWhitelistWillRemove(){
        $f = $this->setupExistingFiles();
        $this->lib->addToWhitelist($f);
        $this->assertTrue($this->lib->removeFromWhitelist($f));
        $this->assertFalse($this->lib->whitelistContainsFile($f));
    }

    public function testRemoveFromWhitelistIsPersistent(){
        $f = $this->setupExistingFiles();
        $this->lib->addToWhitelist($f);
        $this->lib->removeFromWhitelist($f);
        $lib = new FileLibraryImpl($this->testDir);
        $this->assertFalse($lib->whitelistContainsFile($f));
    }

    public function testCleanLibraryWillCleanLibrary(){
        $f = $this->setupExistingFiles();
        $this->lib->addToWhitelist($f);
        $f2 = $this->createFile(time(), time());
        $f3 = $this->lib->addToLibrary($this->user, $f2);
        $this->assertTrue($this->lib->containsFile($f3));
        $this->lib->cleanLibrary();
        $this->assertFalse($this->lib->containsFile($f3));
        $this->assertTrue($this->lib->containsFile($f));
    }

    public function testCleanLibraryWithUserWillOnlyDeleteFilesForUser(){
        $f = $this->setupExistingFiles();
        $f2 = $this->createFile(time(), time());
        $f3 = $this->lib->addToLibrary($this->user2, $f2);
        $this->lib->cleanLibrary($this->user);
        $this->assertFalse($this->lib->containsFile($f));
        $this->assertTrue($this->lib->containsFile($f3));
    }

    public function testLibraryWillCreateLibFolderIfNotExists(){
        $this->testDir->delete(Folder::DELETE_FOLDER_RECURSIVE);
        $lib = new FileLibraryImpl($this->testDir);
        $this->assertFalse($this->testDir->exists());
        $f = new FileImpl(dirname(__FILE__)."/stubs/fileStub");
        $lib->addToLibrary($this->user, $f);
        $this->assertTrue($this->testDir->exists());
    }


    public function testRemoveFileWillReturnFalseOnFileNotInLib(){
        $this->setupExistingFiles();
        $f = $this->createFile(time(), time());
        $this->assertFalse($this->lib->removeFromLibrary($f));
        $this->assertTrue($f->exists());
    }


    public function testRemoveFileWillRemove(){
        $f = $this->setupExistingFiles();
        $this->assertTrue($this->lib->containsFile($f));
        $this->assertTrue($this->lib->removeFromLibrary($f));
        $this->assertFalse($this->lib->containsFile($f));
    }

    public function testRemoveFileWillRemoveFromWhitelist(){
        $f = $this->setupExistingFiles();
        $this->lib->addToWhitelist($f);
        $this->assertTrue($this->lib->whitelistContainsFile($f));
        $this->assertTrue($this->lib->removeFromLibrary($f));
        $this->assertFalse($this->lib->whitelistContainsFile($f));
    }


    public function testAddVersionWillAddVersion(){
        $f = $this->setupExistingFiles();
        $f2 = $this->createFile();
        $version = "1.0";
        $f3 = $this->lib->addVersionOfFile($f, $f2, $version);
        $this->assertTrue($this->lib->containsFile($f3));
        $this->assertEquals($f->getBasename()."-".$version.".".$f->getExtension(), $f3->getFilename());
    }

    public function testAddVersionWillReturnNullIfOrigFileNotInLib(){
        $f = $this->createFile();
        $this->assertNull($this->lib->addVersionOfFile($f, $f));

    }

    public function testCanFindOriginalFromVersion(){
        $f = $this->setupExistingFiles();
        $f2 = $this->createFile();
        $this->lib->addVersionOfFile($f, $f2);
        $f3 = $this->lib->findOriginalFileToVersion($f2);
        $this->assertEquals($f3->getAbsoluteFilePath(), $f->getAbsoluteFilePath());
    }







    public function createFile($name = null, $content = null){

        $name = $name == null?time():$name;
        $content= $content== null?time():$content;

        $f = new FileImpl($this->testDir->getAbsolutePath()."/".$name);
        $f->write($content);
        return $f;
    }


    public function tearDown(){
        @unlink($this->testDir->getAbsolutePath()."/.whitelist");
        $this->testDir->delete(Folder::DELETE_FOLDER_RECURSIVE);
    }

}