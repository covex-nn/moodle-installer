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
   * Test event for developers
   * 
   * @return null
   */
  public function testCreateSymlinks()
  {
    $files = new Files();
    $root = $files->mkdir();
    
    $link = $root . "/test/folder";
    $target = __DIR__;
    
    $extra = array(
      MoodleInstaller::MOODLE_MODULES => array(
        "test/folder" => $target
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
    
    Developer::createSymlinks($event);
    
    $this->assertFileExists($link);
    $this->assertTrue(is_link($link));
    $this->assertEquals(
      realpath($target), realpath(readlink($link))
    );
  }
  
}
