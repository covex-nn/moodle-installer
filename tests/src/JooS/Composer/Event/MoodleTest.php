<?php

namespace JooS\Composer\Event;

use Composer\Composer;
use Composer\Config;
use Composer\Script\Event;

use JooS\Composer\MoodleInstaller;
use JooS\Files;

class MoodleTest extends \PHPUnit_Framework_TestCase
{
  
  /**
   * Test create and remove simlink
   * 
   * @param string $target  Symlink target
   * @param string $link    Symlink path
   * @param string $content File content (if target is a file)
   * 
   * @dataProvider fsTargets
   */
  public function testSymlink($target, $link, $content = null)
  {
    $this->assertFileExists($target);
    $this->assertFileNotExists($link);
    
    $result = Moodle::symlink($target, $link);
    $this->assertTrue($result);
    
    $this->assertFileExists($target);
    $this->assertFileExists($link);
    $this->assertTrue(is_link($link));
    
    $readLink = readlink($link);
    $this->assertEquals(realpath($target), realpath($readLink));
    
    Moodle::removeSymlink($link);
    
    $this->assertFileExists($target);
    $this->assertFileNotExists($link);
    if (!is_null($content)) {
      $this->assertEquals($content, file_get_contents($target));
    }
  }
  
  /**
   * If $link exists method must return false
   * 
   * @return null
   */
  public function testCannotCreateSymlink()
  {
    $result = Moodle::symlink(__FILE__, __FILE__);
    $this->assertFalse($result);
  }
  
  /**
   * Return targets for testing symlinks
   * 
   * @return array
   */
  public function fsTargets()
  {
    $files = $this->_getFsFiles();
    
    $dir = $files->mkdir();
    $targetDir = $files->mkdir();
    $targetFile = $dir . "/file1.txt";
    $contentFile = "qwerty";
    file_put_contents($targetFile, $contentFile);
    
    $linkDir = $dir . "/dir/another/one/dir/link";
    $linkFile = $dir . "/dir2/file";
    
    return array(
      array($targetDir, $linkDir, null), 
      array($targetFile, $linkFile, $contentFile)
    );
  }
  
  private static $_fsFiles = null;
  
  /**
   * @return Files
   */
  private function _getFsFiles()
  {
    if (is_null(self::$_fsFiles)) {
      self::$_fsFiles = new Files();
    }
    return self::$_fsFiles;
  }
  
  /**
   * Test events create/remove symlinks
   * 
   * @return null
   */
  public function testCreateRemoveSymlinks()
  {
    $root = $this->_files->mkdir();
    $folder = "test/folder";
    $target = __DIR__;
    
    $event = $this->_getEventMock($root, $folder, $target);
    
    Moodle::createSymlinks($event);
    
    $link = $root . "/" . $folder;
    
    $this->assertFileExists($link);
    $this->assertTrue(is_link($link));
    $this->assertEquals(
      realpath($target), realpath(readlink($link))
    );
    
    Moodle::removeSymlinks($event);
    
    $this->assertFileNotExists($link);
  }
  
  public function testSetConfigContent()
  {
    Moodle::setConfigContent(null);
    $this->assertEquals(null, Moodle::getConfigContent());
    Moodle::setConfigContent("qwerty");
    $this->assertEquals("qwerty", Moodle::getConfigContent());
    Moodle::setConfigContent(null);
  }
  
  public function testSaveRestoreConfig()
  {
    $root = $this->_files->mkdir();
    $folder = "test/folder";
    $target = __DIR__;
    
    $event = $this->_getEventMock($root, $folder, $target);
    
    $configPhp = $root . "/config.php";
    
    Moodle::saveConfig($event);
    $this->assertEquals(null, Moodle::getConfigContent());
    Moodle::restoreConfig($event);
    
    $this->assertFileNotExists($configPhp);
    
    file_put_contents($configPhp, "qwerty");
    Moodle::saveConfig($event);
    $this->assertEquals("qwerty", Moodle::getConfigContent());
    
    unlink($configPhp);
    Moodle::restoreConfig($event);
    
    clearstatcache();
    $this->assertFileExists($configPhp);
    $this->assertEquals("qwerty", file_get_contents($configPhp));
  }
  
  /**
   * Return Event Mock object
   * 
   * @return Event
   */
  private function _getEventMock($root, $folder, $target)
  {
    $extra = array(
      MoodleInstaller::MOODLE_MODULES => array(
        $folder => $target
      )
    );
    
    $package = $this->getMock("Composer\\Package\\RootPackageInterface");
    $package
        ->expects($this->any())
        ->method("getExtra")
        ->will($this->returnValue($extra));

    $package
        ->expects($this->any())
        ->method("getType")
        ->will($this->returnValue(MoodleInstaller::TYPE_MOODLE_PACKAGE));
    
    $config = new Config();
    
    $composer = new Composer();
    $composer->setConfig($config);
    $composer->setPackage($package);

    $config->merge(
      array(
        "config" => array(
          MoodleInstaller::MOODLE_DIR => $root
        )
      )
    );
    
    $io = $this->getMock("Composer\\IO\\IOInterface");
    $event = new Event("post-update-cmd", $composer, $io);
    
    return $event;
  }
  
  /**
   * @var Files
   */
  private $_files = null;
  
  /**
   * Sets up the fixture
   * 
   * @return null
   */
  protected function setUp()
  {
    $this->_files = new Files();
  }
  
  /**
   * Tears down the fixture
   * 
   * @return null
   */
  protected function tearDown()
  {
    unset($this->_files);
  }
  
}
