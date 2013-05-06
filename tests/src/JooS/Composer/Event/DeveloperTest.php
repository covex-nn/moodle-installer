<?php

namespace JooS\Composer\Event;

use Composer\Composer;
use Composer\Config;
use Composer\Script\Event;

use JooS\Composer\MoodleInstaller;
use JooS\Files;

class DeveloperTest extends \PHPUnit_Framework_TestCase
{
  
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
    
    Developer::createSymlinks($event);
    
    $link = $root . "/" . $folder;
    
    $this->assertFileExists($link);
    $this->assertTrue(is_link($link));
    $this->assertEquals(
      realpath($target), realpath(readlink($link))
    );
    
    Developer::removeSymlinks($event);
    
    $this->assertFileNotExists($link);
  }
  
  public function testSetConfigContent()
  {
    Developer::setConfigContent(null);
    $this->assertEquals(null, Developer::getConfigContent());
    Developer::setConfigContent("qwerty");
    $this->assertEquals("qwerty", Developer::getConfigContent());
    Developer::setConfigContent(null);
  }
  
  public function testSaveRestoreConfig()
  {
    $root = $this->_files->mkdir();
    $folder = "test/folder";
    $target = __DIR__;
    
    $event = $this->_getEventMock($root, $folder, $target);
    
    $configPhp = $root . "/config.php";
    
    Developer::saveConfig($event);
    $this->assertEquals(null, Developer::getConfigContent());
    Developer::restoreConfig($event);
    
    $this->assertFileNotExists($configPhp);
    
    file_put_contents($configPhp, "qwerty");
    Developer::saveConfig($event);
    $this->assertEquals("qwerty", Developer::getConfigContent());
    
    unlink($configPhp);
    Developer::restoreConfig($event);
    
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
