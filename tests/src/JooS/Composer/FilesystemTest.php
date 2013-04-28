<?php

namespace JooS\Composer;

use JooS\Files;

class FilesystemTest extends \PHPUnit_Framework_TestCase
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
    
    $result = MoodleInstaller::symlink($target, $link);
    $this->assertTrue($result);
    
    $this->assertFileExists($target);
    $this->assertFileExists($link);
    $this->assertTrue(is_link($link));
    
    $readLink = readlink($link);
    $this->assertEquals(realpath($target), realpath($readLink));
    
    MoodleInstaller::removeSymlink($link);
    
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
    $result = MoodleInstaller::symlink(__FILE__, __FILE__);
    $this->assertFalse($result);
  }
  
  /**
   * Return targets for testing symlinks
   * 
   * @return array
   */
  public function fsTargets()
  {
    $files = $this->_getFilesTool();
    
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
  
  private static $_files = null;
  
  /**
   * @return Files
   */
  private function _getFilesTool()
  {
    if (is_null(self::$_files)) {
      self::$_files = new Files();
    }
    return self::$_files;
  }
}
