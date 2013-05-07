<?php

namespace JooS\Composer\Event;

use Composer\Script\Event;
use Composer\Package\PackageInterface;
use JooS\Composer\MoodleInstaller;

class Moodle
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
      self::removeSymlink($folder);
      $event->getIO()->write(
        "- [moodle-installer] symlink '$folder' removed"
      );
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
      self::symlink($developFolder, $folder);
      $event->getIO()->write(
        "- [moodle-installer] symlink '$folder' => '$developFolder' created"
      );
    }
  }

  /**
   * Create symlink
   * 
   * @param type $target Source
   * @param type $link   Destination link
   * 
   * @return boolean
   */
  public static function symlink($target, $link)
  {
    if (file_exists($link)) {
      return false;
    }

    $parts = explode("/", $link);
    array_pop($parts);
    
    $create = array();
    while (sizeof($parts)) {
      $path = implode("/", $parts);

      if (file_exists($path)) {
        break;
      } else {
        $create[] = array_pop($parts);
        if (!sizeof($parts)) {
          $path = ".";
        }
      }
    }
    
    foreach (array_reverse($create) as $folder) {
      $path .= "/" . $folder;
      mkdir($path, 0777);
    }
    
    return symlink(realpath($target), $link);
  }
  
  /**
   * Delete symlink
   * 
   * @param string $link Path to link
   * 
   * @return null
   */
  public static function removeSymlink($link)
  {
    clearstatcache();
    if (file_exists($link) && is_link($link)) {
      $target = @readlink($link);
      if (!file_exists($target)) {
        $target = false;
      }
      
      if ($target !== false) {
        do {
          $newTarget = dirname($target) . "/" . uniqid(basename($target));
        } while (file_exists($newTarget));

        rename($target, $newTarget);
      }
      if (!@rmdir($link)) {
        unlink($link);
      }
      if ($target !== false) {
        rename($newTarget, $target);
      }
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
    
    $moodleDir = self::_getMoodleDir($event);
    $vendorDir = $composer->getConfig()->get("vendor-dir");
    $extraFolders = array();
    
    $devPackage = $composer->getPackage();
    $devType = $devPackage->getType();
    if ($devType == MoodleInstaller::TYPE_MOODLE_PACKAGE) {
      $devFolders = self::_getPackageExtraFolders($devPackage);
      foreach ($devFolders as $folder => $developFolder) {
        $key = $moodleDir . "/" . $folder;
        $extraFolders[$key] = $developFolder;
      }
    }
    
    $repoManager = $composer->getRepositoryManager();
    if (!is_null($repoManager)) {
      $repo = $repoManager->getLocalRepository();
      /* @var $repo \Composer\Repository\InstalledFilesystemRepository */
      foreach ($repo->getPackages() as $package) {
        /* @var $package PackageInterface */
        $type = $package->getType();
        if ($type == MoodleInstaller::TYPE_MOODLE_PACKAGE) {
          if ($devPackage->getName() == $package->getName()) {
            continue;
          }

          $packageFolders = self::_getPackageExtraFolders($package);
          foreach ($packageFolders as $folder => $developFolder) {
            $key = $moodleDir . "/" . $folder;
            $val = $vendorDir . "/" . $package->getName() . "/" . $developFolder;
            $extraFolders[$key] = $val;
          }
        }
      }

    }
    return $extraFolders;
  }
  
  /**
   * Return package's moodle folders
   * 
   * @param PackageInterface $package Package
   * 
   * @return array
   */
  private static function _getPackageExtraFolders(PackageInterface $package)
  {
    $folders = null;
    if ($package->getType() == MoodleInstaller::TYPE_MOODLE_PACKAGE) {
      $extra = $package->getExtra();
      if (isset($extra[MoodleInstaller::MOODLE_MODULES])) {
        $folders = $extra[MoodleInstaller::MOODLE_MODULES];
      }
    }
    if (!is_array($folders)) {
      $folders = array();
    }
    
    return $folders;
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
    
    if (!is_null($configContent)) {
      $event->getIO()->write(
        "- [moodle-installer] config.php saved"
      );
    }
  }
  
  /**
   * Restore www/config.php
   * 
   * @param \Composer\Script\Event $event Event
   * 
   * @return null
   */
  public static function restoreConfig(Event $event)
  {
    $configContent = self::getConfigContent();
    if (!is_null($configContent)) {
      $configPhp = self::_getMoodleDir($event, "config.php");
      if (!file_exists($configPhp)) {
        file_put_contents($configPhp, $configContent);
        
        $event->getIO()->write(
          "- [moodle-installer] config.php restored"
        );
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
  private static function _getMoodleDir(Event $event, $filename = null)
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
