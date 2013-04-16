<?php

namespace JooS\Composer;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

use JooS\Stream\Wrapper_FS;
use JooS\Stream\Wrapper_Exception;
use JooS\Files;

class MoodleInstaller extends LibraryInstaller
{
  
  const MOODLE_STREAM = "moodle-stream";
  
  const MOODLE_MODULES = "moodle-modules";
  
  const MOODLE_TYPE = "moodle-package";

  /**
   * Initializes moodle installer.
   *
   * @param IOInterface $io       io instance
   * @param Composer    $composer
   * @param string      $type     package type that this installer handles
   */
  public function __construct(IOInterface $io, Composer $composer, $type = self::MOODLE_TYPE)
  {
    parent::__construct($io, $composer, self::MOODLE_TYPE);
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
    $this->_installMoodleCode($package);
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
    $this->_removeMoodleCode($initial);
    parent::updateCode($initial, $target);
    $this->_installMoodleCode($target);
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
    $this->_removeMoodleCode($package);
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
    foreach ($this->_getExtraFolders($package) as $folder => $vendorPath) {
      if (file_exists($vendorPath)) {
        $this->_copyDirectory($vendorPath, $folder);
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
    $filesystem = $this->filesystem;
    /* @var $filesystem Filesystem */
    foreach ($this->_getExtraFolders($package) as $folder => $vendorPath) {
      $filesystem->remove($folder);
    }
  }
  
  /**
   * Copy one directory to another
   * 
   * @param string $from Source
   * @param string $to   Destination
   * 
   * @return null
   */
  private function _copyDirectory($from, $to)
  {
    if (is_dir($from)) {
      mkdir($to);
      foreach (new \DirectoryIterator($from) as $fileInfo) {
        /* @var $fileInfo \SplFileInfo */
        if ($fileInfo->isDot()) {
          continue;
        }
        $fileName = $fileInfo->getBasename();

        $this->_copyDirectory($from . "/" . $fileName, $to . "/" . $fileName);
      }
    } else {
      copy($from, $to);
    }
  }
    
  /**
   * Return array of folder paths
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
    
    $extraFolders = array();
    
    $downloadPath = $this->getInstallPath($package);
    foreach ($folders as $key => $value) {
      
      $key = str_replace(DIRECTORY_SEPARATOR, "/", $key);
      $value = str_replace(DIRECTORY_SEPARATOR, "/", $value);
      
      $extraFolders[$key] = $downloadPath . "/" . $value;
    }
    
    return $extraFolders;
  }
  
}
