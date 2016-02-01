<?php

namespace JooS\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;

use JooS\Files;

class MoodleInstallerTest extends \PHPUnit_Framework_TestCase
{

  /**
   * Test support types
   *
   * @return null
   */
  public function testSupports()
  {
    $installer = new MoodleInstaller($this->io, $this->composer);

    $this->assertTrue($installer->supports(MoodleInstaller::TYPE_MOODLE_SOURCE));
    $this->assertTrue($installer->supports(MoodleInstaller::TYPE_MOODLE_PACKAGE));
    $this->assertFalse($installer->supports("library"));
  }

  /**
   * Test install path for moodle-source package
   *
   * @return null
   */
  public function testInstallPathSource()
  {
    $library = new MoodleInstaller($this->io, $this->composer);

    $package = $this->createPackageMock(
      "covex-nn/test1", MoodleInstaller::TYPE_MOODLE_SOURCE
    );

    $this->assertEquals(
      $this->moodleDir,
      $library->getInstallPath($package)
    );
  }

  /**
   * Install and remove
   *
   * @return null
   */
  public function testInstallPathPackage()
  {
    $moodle = new MoodleInstaller($this->io, $this->composer);

    $package = $this->createPackageMock(
      "covex-nn/test1", MoodleInstaller::TYPE_MOODLE_PACKAGE
    );
    /* @var $package \Composer\Package\Package */

    $expectedPath = $this->vendorDir . "/" . $package->getPrettyName();
    $actualPath = $moodle->getInstallPath($package);
    if (DIRECTORY_SEPARATOR == "\\") {
      $expectedPath = str_replace("\\", "/", $expectedPath);
      $actualPath = str_replace("\\", "/", $actualPath);
    }

    $this->assertEquals($expectedPath, $actualPath);
  }

  /**
   * @var Composer
   */
  protected $composer;

  /**
   * @var Config
   */
  protected $config;

  /**
   * @var string
   */
  protected $vendorDir;

  /**
   * @var string
   */
  protected $binDir;

  /**
   * @var string
   */
  protected $moodleDir;

  /**
   * @var IOInterface
   */
  protected $io;

  /**
   * @var Files
   */
  protected $files;

  /**
   * Sets up the fixture
   *
   * @return null
   */
  protected function setUp()
  {
    $this->files = new Files();

    $this->composer = new Composer();
    $this->config = new Config();
    $this->composer->setConfig($this->config);

    $this->vendorDir = realpath($this->files->mkdir());
    $this->binDir = realpath($this->files->mkdir());
    $this->moodleDir = realpath($this->files->mkdir()) . "/www";

    $this->config->merge(
      array(
        'config' => array(
          'vendor-dir' => $this->vendorDir,
          'bin-dir' => $this->binDir,
          'moodle-dir' => $this->moodleDir,
          'home' => $this->files->mkdir()
        )
      )
    );

    $this->io = $this->getMock(
      'Composer\IO\IOInterface'
    );
  }

  /**
   * This method is called after the last test of this test class is run.
   *
   * @return null
   */
  protected function tearDown()
  {
    unset($this->files);
  }

  /**
   * Create package mock object
   *
   * @param string $name Package pretty name
   * @param string $type Package type
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function createPackageMock($name, $type)
  {
    $mockBuilder = $this->getMockBuilder('Composer\Package\Package')
      ->setConstructorArgs(array($name, '1.0.0.0', '1.0.0'));

    $package = $mockBuilder->getMock();

    $package
      ->expects($this->any())
      ->method('getPrettyName')
      ->will($this->returnValue($name));

    $package
      ->expects($this->any())
      ->method('getType')
      ->will($this->returnValue($type));

    $package
      ->expects($this->any())
      ->method('getDistType')
      ->will($this->returnValue("zip"));

    $package
      ->expects($this->any())
      ->method('getInstallationSource')
      ->will($this->returnValue("dist"));

    return $package;
  }
}
