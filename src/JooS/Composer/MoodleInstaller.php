<?php

namespace JooS\Composer;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class MoodleInstaller extends LibraryInstaller
{
  
  const MOODLE_MODULES = "moodle-modules";
  const MOODLE_DIR = "moodle-dir";
  
  const TYPE_MOODLE_PACKAGE = "moodle-package";
  const TYPE_MOODLE_SOURCE = "moodle-source";
  

  public function supports($packageType)
  {
    switch ($packageType) {
      case self::TYPE_MOODLE_PACKAGE:
      case self::TYPE_MOODLE_SOURCE:
        $supports = true;
        break;
      default:
        $supports = false;
    }
    return $supports;
  }
  
  /**
   * Returns the installation path of a package
   *
   * @param  PackageInterface $package Package
   * 
   * @return string
   */
  public function getInstallPath(PackageInterface $package)
  {
    switch ($package->getType()) {
      case self::TYPE_MOODLE_SOURCE:
        $installPath = $this->_getMoodleDir();
        break;
      default:
        $installPath = parent::getInstallPath($package);
    }
    return $installPath;
  }
  
  /**
   * Updates specific package.
   *
   * @param InstalledRepositoryInterface $repo    repository in which to check
   * @param PackageInterface             $initial already installed package version
   * @param PackageInterface             $target  updated version
   *
   * @return null
   * @throws InvalidArgumentException if $from package is not installed
   */
  public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
  {
    if ($initial->getType() == self::TYPE_MOODLE_SOURCE) {
      $packages = $repo->search(
        array("type" => self::TYPE_MOODLE_PACKAGE)
      );
      if (sizeof($packages)) {
        throw new \InvalidArgumentException(
          "Package '" . self::TYPE_MOODLE_SOURCE . "' can not be upgraded. " . 
          "Uninstall all packages '" . self::TYPE_MOODLE_PACKAGE . "' first"
        );
      }
    }
    parent::update($repo, $initial, $target);
  }
  
  /**
   * Install package code
   * 
   * @param PackageInterface $package Package
   * 
   * @return null
   */
  protected function installCode(PackageInterface $package)
  {
    parent::installCode($package);
    
    if ($package->getType() == self::TYPE_MOODLE_PACKAGE) {
      $this->_installMoodleCode($package);
    }
  }

  /**
   * Update package code
   * 
   * @param PackageInterface $initial Already installed package
   * @param PackageInterface $target  Package beeing installed
   * 
   * @return null
   */
  protected function updateCode(PackageInterface $initial, PackageInterface $target)
  {
    if ($initial->getType() == self::TYPE_MOODLE_PACKAGE) {
      $this->_removeMoodleCode($initial);
    }
    
    parent::updateCode($initial, $target);
    
    if ($target->getType() == self::TYPE_MOODLE_PACKAGE) {
      $this->_installMoodleCode($target);
    }
  }

  /**
   * Remove package code
   * 
   * @param PackageInterface $package Package
   * 
   * @return null
   */
  protected function removeCode(PackageInterface $package)
  {
    if ($package->getType() == self::TYPE_MOODLE_PACKAGE) {
      $this->_removeMoodleCode($package);
    }
    parent::removeCode($package);
  }
  
  /**
   * Install code
   * 
   * @param PackageInterface $package Package
   * 
   * @return null
   */
  private function _installMoodleCode(PackageInterface $package)
  {
    $extraFolders = $this->_getExtraFolders($package);
    foreach ($extraFolders as $folder => $vendorPath) {
      if (file_exists($vendorPath)) {
        self::createSymlink($vendorPath, $folder);
      }
    }
  }
  
  /**
   * Remove code
   * 
   * @param PackageInterface $package Package
   * 
   * @return boolean
   */
  private function _removeMoodleCode(PackageInterface $package)
  {
    $extraFolders = $this->_getExtraFolders($package);
    foreach (array_keys($extraFolders) as $folder) {
      if (file_exists($folder) && is_link($folder)) {
        $target = readlink($folder);
        do {
          $newTarget = dirname($target) . "/" . uniqid(basename($target));
        } while (file_exists($newTarget));
        
        rename($target, $newTarget);
        if (!@rmdir($folder)) {
          unlink($folder);
        }
        rename($newTarget, $target);
      }
    }
  }
  
  /**
   * Create symlink
   * 
   * @param type $from Source
   * @param type $to   Destination link
   * 
   * @return boolean
   */
  public static function createSymlink($from, $to) {
    if (file_exists($to)) {
      return false;
    }
    
    $parts = explode("/", $to);
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
    
    return symlink(realpath($from), $to);
  }
  
  /**
   * Return array of folder paths
   * 
   * Key - moodle-dir/folder, Value - vendor-dir/folder
   * 
   * @param PackageInterface $package Package
   * 
   * @return array
   */
  private function _getExtraFolders(PackageInterface $package)
  {
    $extra = $package->getExtra();
    if (!isset($extra[self::MOODLE_MODULES])) {
      $folders = array();
    } elseif (!is_array($extra[self::MOODLE_MODULES])) {
      $folders = array();
    } else {
      $folders = $extra[self::MOODLE_MODULES];
    }

    $moodleDir = $this->_getMoodleDir();
    $extraFolders = array();
    
    $downloadPath = $this->getInstallPath($package);
    foreach ($folders as $key => $value) {
      if (DIRECTORY_SEPARATOR != "/") {
        $key = str_replace(DIRECTORY_SEPARATOR, "/", $key);
        $value = str_replace(DIRECTORY_SEPARATOR, "/", $value);
      }
      $extraFolders[$moodleDir . "/" . $key] = $downloadPath . "/" . $value;
    }
    
    return $extraFolders;
  }

  /**
   * Return moodle installation dir
   * 
   * @return string
   */
  private function _getMoodleDir()
  {
    $composer = $this->composer;
    /* @var $composer Composer */
    $moodleDir = $composer->getConfig()->get(self::MOODLE_DIR);
    if (!$moodleDir) {
      $moodleDir = "www";
    }
    return $moodleDir;
  }
  
}
