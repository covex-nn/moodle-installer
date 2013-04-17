<?php

namespace JooS\Composer\Event;

use JooS\Stream\Wrapper_FS;

class HtaccessTest extends \PHPUnit_Framework_TestCase
{
  
  /**
   * @var Htaccess
   */
  private $_htaccess;
  
  /**
   * Test contents
   * 
   * @return null
   */
  public function testEmptyContent()
  {
    $path = $this->_htaccessPath();
    
    $this->assertEquals($path, $this->_htaccess->getPath());
    $this->assertTrue(!$this->_htaccess->exists());
    $this->assertTrue($this->_htaccess->writable());
    $this->assertEquals("", $this->_htaccess->getContents());
    $this->assertEquals(array(), $this->_htaccess->getContentsArray());
    
    $denyFromAll = "Deny from all";
    $expected = array($denyFromAll);

    file_put_contents($path, $denyFromAll);
    $this->assertEquals($expected, $this->_htaccess->getContentsArray());

    file_put_contents($path, $denyFromAll . "\n");
    $this->assertEquals($expected, $this->_htaccess->getContentsArray());
  }
  
  public function testAppend()
  {
    $denyFromAll = "Deny from all";
    $this->_htaccess->append($denyFromAll);
    $this->assertEquals($denyFromAll . PHP_EOL, $this->_htaccess->getContents());

    $this->_htaccess->append($denyFromAll);
    $this->assertEquals($denyFromAll . PHP_EOL, $this->_htaccess->getContents());
  }
  
  /**
   * Sets up the fixture, for example, open a network connection.
   * 
   * This method is called before a test is executed.
   *
   * @return null
   */
  protected function setUp()
  {
    Wrapper_FS::register("htaccess");
    mkdir("htaccess://folder");
    
    $this->_htaccess = new Htaccess(
      $this->_htaccessPath()
    );
  }
  
  /**
   * Tears down the fixture, for example, close a network connection.
   * 
   * This method is called after a test is executed.
   * 
   * @return null
   */
  protected function tearDown()
  {
    Wrapper_FS::unregister("htaccess");
  }
  
  protected function _htaccessPath()
  {
    return "htaccess://folder/.htaccess";
  }
  
}
