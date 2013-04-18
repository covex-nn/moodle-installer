<?php

namespace JooS\Composer\Event;

use Composer\Script\Event;
use JooS\Composer\MoodleInstaller;

class Developer
{

  /**
   * Composer event handler
   * 
   * Create symlinks to moodle modules into moodle-dir
   * 
   * @param \Composer\Script\Event $event Event
   * 
   * @return null
   */
  public static function createSymlinks(Event $event)
  {
    $composer = $event->getComposer();
    
    $extra = $composer->getPackage()->getExtra();
    $extraFolders = null;
    if (isset($extra[MoodleInstaller::MOODLE_MODULES])) {
      $extraFolders = $extra[MoodleInstaller::MOODLE_MODULES];
    }
    if (!is_array($extraFolders)) {
      $extraFolders = array();
    }
    
    $config = $composer->getConfig();
    $moodleDir = $config->get(MoodleInstaller::MOODLE_DIR);
    if (!$moodleDir) {
      $moodleDir = "www";
    }
    
    foreach ($extraFolders as $folder => $developFolder) {
      MoodleInstaller::createSymlink($developFolder, $moodleDir . "/" . $folder);
    }
  }
  
}
