<?php

namespace JooS\Composer;

use Composer\Script\Event;

class HtaccessEvent
{
  
  /**
   * Create .htaccess and write 'Deny from all'
   * 
   * @param Event $event
   * 
   * @return null
   */
  public static function denyFromAll(Event $event)
  {
    $composer = $event->getComposer();
    
    $vendor = $composer->getConfig()->get("vendor-dir");
    $path =  $vendor . "/.htaccess";

    $htaccess = new self($path);
    $htaccess->append("Deny from all");
    
    $composerIo = $event->getIO();
    $composerIo->write("File " . $path . " created, HTTP access denied");
  }
  
  /**
   * @var string
   */
  private $_path;
  
  /**
   * Public functructor
   * 
   * @param string $path Path to .htaccess
   */
  public function __construct($path)
  {
    $this->_setPath($path);
  }

  /**
   * 
   * @param type $string
   * 
   * @return boolean
   * @todo сделать более интеллектуальненько =)
   */
  public function append($string)
  {
    $array = $this->getContentsArray();
    if (!in_array($string, $array)) {
      $array[] = $string;
    }
    return $this->_write($array);
  }
  
  /**
   * Write contents to file
   * 
   * @param string $contents Contents
   * 
   * @return boolean
   */
  protected function _write($contents)
  {
    if (is_array($contents)) {
      $contents = implode(PHP_EOL, $contents) . PHP_EOL;
    } else {
      $contents = trim($contents) . PHP_EOL;
    }
    if ($this->writable()) {
      $path = $this->getPath();
      $result = !!file_put_contents($path, $contents);
    } else {
      $result = false;
    }
    
    return $result;
  }
  
  /**
   * Return file contents
   * 
   * @return string
   */
  public function getContents()
  {
    $path = $this->getPath();
    if ($this->exists()) {
      $contents = file_get_contents($path);
    } else {
      $contents = "";
    }
    return $contents;
  }
  
  public function getContentsArray()
  {
    $contents = $this->getContents();
    
    $lines = explode(PHP_EOL, $contents);
    foreach ($lines as $key => $value) {
      $value = trim($value);
      if (strlen($value)) {
        $lines[$key] = $value;
      } else {
        unset($lines[$key]);
      }
    }
    return array_values($lines);
  }
  
  /**
   * Return if file exists
   * 
   * @return boolean
   */
  public function exists()
  {
    $path = $this->getPath();
    return file_exists($path);
  }
  
  /**
   * Return if file writable
   * 
   * @return boolean
   */
  public function writable()
  {
    $path = $this->getPath();
    if ($this->exists()) {
      $writable = is_file($path) && is_writable($path);
    } else {
      $dir = dirname($path);
      $writable = is_writable($dir);
    }
    return $writable;
  }
  
  /**
   * Return path to .htaccess
   * 
   * @return string
   */
  public function getPath()
  {
    return $this->_path;
  }
  
  /**
   * Set path to .htaccess
   * 
   * @param string $path Path to .htaccess
   * 
   * @return HtaccessEvent
   */
  protected function _setPath($path)
  {
    $this->_path = $path;
    
    return $this;
  }
  
}
