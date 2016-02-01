<?php

namespace JooS\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

class MoodleInstaller extends LibraryInstaller
{

  const MOODLE_MODULES = "moodle-modules";
  const MOODLE_DIR = "moodle-dir";

  const TYPE_MOODLE_PACKAGE = "moodle-package";
  const TYPE_MOODLE_SOURCE = "moodle-source";

  /**
   * {@inheritdoc}
   */
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
   * {@inheritdoc}
   */
  public function getInstallPath(PackageInterface $package)
  {
    switch ($package->getType()) {
      case self::TYPE_MOODLE_SOURCE:
        $basePath = $this->getMoodleDir();
        break;
      default:
        $basePath = parent::getInstallPath($package);
    }

    return $basePath;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPackageBasePath(PackageInterface $package)
  {
    switch ($package->getType()) {
      case self::TYPE_MOODLE_SOURCE:
        $basePath = $this->getMoodleDir();
        break;
      default:
        $basePath = parent::getPackageBasePath($package);
    }

    return $basePath;
  }

  /**
   * Return moodle installation dir
   *
   * @return string
   */
  private function getMoodleDir()
  {
    $composer = $this->composer;
    /* @var $composer \Composer\Composer */
    $moodleDir = $composer->getConfig()->get(self::MOODLE_DIR);
    if (!$moodleDir) {
      $moodleDir = "www";
    }
    return $moodleDir;
  }
}
