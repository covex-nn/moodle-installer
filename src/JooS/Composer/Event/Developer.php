<?php

namespace JooS\Composer\Event;

use Composer\Script\Event;
use JooS\Composer\MoodleInstaller;

class Developer
{
  
  /**
   * @var string
   */
  private static $_configContent = null;
  
  /**
   * Remove symlinks to moodle modules from moodle-dir
   * 
   * @param Event $event Event
   * 
   * @return null
   */
  public static function removeSymlinks(Event $event)
  {
    $extraFolders = self::_getExtraFolders($event);
    foreach (array_keys($extraFolders) as $folder) {
      MoodleInstaller::removeSymlink($folder);
    }
  }
  
  /**
   * Create symlinks to moodle modules into moodle-dir
   * 
   * @param Event $event Event
   * 
   * @return null
   */
  public static function createSymlinks(Event $event)
  {
    $extraFolders = self::_getExtraFolders($event);
    foreach ($extraFolders as $folder => $developFolder) {
      MoodleInstaller::symlink($developFolder, $folder);
    }
  }

  /**
   * Return moodle folders for symlinks
   * 
   * @param Event $event Event
   * 
   * @return array
   */
  private static function _getExtraFolders(Event $event)
  {
    $composer = $event->getComposer();
    
    $extra = $composer->getPackage()->getExtra();
    $folders = null;
    if (isset($extra[MoodleInstaller::MOODLE_MODULES])) {
      $folders = $extra[MoodleInstaller::MOODLE_MODULES];
    }
    if (!is_array($folders)) {
      $folders = array();
    }
    
    $moodleDir = self::_getMoodleDir($event);
    
    $extraFolders = array();
    foreach ($folders as $folder => $developFolder) {
      $extraFolders[$moodleDir . "/" . $folder] = $developFolder;
    }
    
    return $extraFolders;
  }

  /**
   * Save contents of www/config.php
   * 
   * @param Event $event Event
   * 
   * @return null
   */
  public static function saveConfig(Event $event)
  {
    $configPhp = self::_getMoodleDir($event, "config.php");
    
    if (file_exists($configPhp)) {
      $configContent = file_get_contents($configPhp);
    } else {
      $configContent = null;
    }
    
    self::setConfigContent($configContent);
  }
  
  /**
   * Restore www/config.php
   * 
   * @param \Composer\Script\Event $event
   */
  public static function restoreConfig(Event $event)
  {
    $configContent = self::getConfigContent();
    if (!is_null($configContent)) {
      $configPhp = self::_getMoodleDir($event, "config.php");
      if (!file_exists($configPhp)) {
        file_put_contents($configPhp, $configContent);
      }
    }
  }
  
  /**
   * Store config.php contents
   * 
   * @param string $configContent Content of config.php
   * 
   * @return null
   */
  public static function setConfigContent($configContent = null)
  {
    self::$_configContent = $configContent;
  }
  
  /**
   * Return stored config.php contents
   * 
   * @return string
   */
  public static function getConfigContent()
  {
    return self::$_configContent;
  }
  
  /**
   * Return moodle dir
   * 
   * @param Event  $event    Event
   * @param string $filename Relative path to file inside www
   * 
   * @return string
   */
  private function _getMoodleDir(Event $event, $filename = null)
  {
    $config = $event->getComposer()->getConfig();
    $moodleDir = $config->get(MoodleInstaller::MOODLE_DIR);
    if (!$moodleDir) {
      $moodleDir = "www";
    }
    if (!is_null($filename)) {
      $moodleDir .= "/" . $filename;
    }
    
    return $moodleDir;
  }
}
