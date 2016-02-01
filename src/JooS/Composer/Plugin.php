<?php

namespace JooS\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements  PluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io)
  {
    $installer = new MoodleInstaller($io, $composer);
    $composer->getInstallationManager()->addInstaller($installer);
  }
}
