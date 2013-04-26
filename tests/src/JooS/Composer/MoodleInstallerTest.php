<?php

namespace JooS\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Package\Package;
use Composer\Downloader\DownloadManager;
use Composer\Downloader\ZipDownloader;
use Composer\IO\IOInterface;
use Composer\Repository\InstalledRepositoryInterface;

use JooS\Files;

use TheSeer\DirectoryScanner\DirectoryScanner;

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
    
    $package = $this->_createPackageMock(
      "covex-nn/test1", MoodleInstaller::TYPE_MOODLE_SOURCE
    );

    $this->assertEquals(
      $this->moodleDir, 
      $library->getInstallPath($package)
    );
  }
  
  /**
   * Install and remove
   */
  public function testInstallPathPackage()
  {
    $moodle = new MoodleInstaller($this->io, $this->composer);
    
    $package = $this->_createPackageMock(
      "covex-nn/test1", MoodleInstaller::TYPE_MOODLE_PACKAGE
    );

    $expectedPath = $this->vendorDir . "/" . $package->getPrettyName();
    $actualPath = $moodle->getInstallPath($package);
    if (DIRECTORY_SEPARATOR == "\\") {
      $expectedPath = str_replace("\\", "/", $expectedPath);
      $actualPath = str_replace("\\", "/", $actualPath);
    }
    
    $this->assertEquals($expectedPath, $actualPath);
  }
  
  /**
   * Test 
   */
  public function testInstallUpdateRemoveMoodlePackage()
  {
    $moodle = new MoodleInstaller($this->io, $this->composer);
    $repository = $this->getMock(
      'Composer\Repository\InstalledRepositoryInterface'
    );
    
    $packageOne = $this->_createPackageMockFromDir(
      __DIR__ . "/_source/package1"
    );
    // Install package1
    $moodle->install($repository, $packageOne);
    
    /* @var $packageOne Package */
    $linkOne = $this->moodleDir . "/test/dir-in-www";
    $this->assertFileExists($linkOne);
    
    $targetOne = realpath(
      $this->vendorDir . "/covex-nn/moodle-package-test/test-dir-in-package"
    );
    $actualTargetOne = realpath(readlink($linkOne));
    
    $this->assertFileExists($targetOne);
    $this->assertEquals($targetOne, $actualTargetOne);
    
    $packageTwo = $this->_createPackageMockFromDir(
      __DIR__ . "/_source/package2"
    );
    
    $repository
        ->expects($this->any())
        ->method("hasPackage")
        ->with($packageOne)
        ->will($this->returnValue(true));

    $repository
        ->expects($this->any())
        ->method("hasPackage")
        ->with($packageTwo)
        ->will($this->returnValue(false));
    
    // update package1 with package2
    $moodle->update($repository, $packageOne, $packageTwo);

    $this->assertFileNotExists($linkOne);
    $this->assertFileNotExists($targetOne);
    
    $repository
        ->expects($this->any())
        ->method("hasPackage")
        ->with($packageOne)
        ->will($this->returnValue(false));

    $repository
        ->expects($this->any())
        ->method("hasPackage")
        ->with($packageTwo)
        ->will($this->returnValue(true));

    $linkTwo = $this->moodleDir . "/second-dir-in-www";
    $this->assertFileExists($linkTwo);
    
    $targetTwo = realpath(
      $this->vendorDir . "/covex-nn/moodle-package-test/second-dir-in-package"
    );
    $actualTargetTwo = realpath(readlink($linkTwo));

    $this->assertFileExists($targetTwo);
    $this->assertEquals($targetTwo, $actualTargetTwo);
    
    // Uninstall package2
    $moodle->uninstall($repository, $packageTwo);
    
    $this->assertFileNotExists($linkTwo);
    $this->assertFileNotExists($targetTwo);
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
   * @var DownloadManager
   */
  protected $dm;
  
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
    
    $this->vendorDir = $this->files->mkdir();
    $this->binDir = $this->files->mkdir();
    $this->moodleDir = $this->files->mkdir() . "/www";
    
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
    
    $zipDownloader = new ZipDownloader($this->io, $this->config);
    $this->dm = new DownloadManager();
    $this->dm->setDownloader("zip", $zipDownloader);
    
    $this->composer->setDownloadManager($this->dm);
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
   * Create package mock
   * 
   * @param string $source Source folder
   * 
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function _createPackageMockFromDir($source)
  {
    $json = json_decode(
      file_get_contents($source . "/composer.json"), 
      true
    );
    
    $package = $this->_createPackageMock($json["name"], $json["type"]);
    if (isset($json["extra"])) {
      $package
          ->expects($this->any())
          ->method('getExtra')
          ->will($this->returnValue($json["extra"]));
    }
    
    $distUrl = $this->_createPackageArchive($source);
    
    $package
        ->expects($this->any())
        ->method('getDistUrl')
        ->will($this->returnValue($distUrl));
    
    return $package;
  }
  
  /**
   * Create package mock object
   * 
   * @param string $name Package pretty name
   * @param string $type Package type
   * 
   * @return \PHPUnit_Framework_MockObject_MockObject
   */
  private function _createPackageMock($name, $type)
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

  /**
   * Create zip archive
   * 
   * @param type $source Source folder
   * @param type $target Destination
   * 
   * @return null
   */
  private function _createPackageArchive($source)
  {
    $tmpFilename = $this->files->tempnam();
    
    $zip = new \ZipArchive();
    $zip->open($tmpFilename, \ZipArchive::CREATE);
    
    $source = str_replace(DIRECTORY_SEPARATOR, "/", $source);
    
    $scanner = new DirectoryScanner();
    foreach ($scanner->getFiles($source) as $file) {
      /* @var $file \SplFileInfo */
      
      $filename = $file->getPathname();
      $localname = substr($filename, strlen($source) + 1);
      
      $zip->addFile($filename, "package/" . $localname);
    }
    $zip->close();
    
    return $tmpFilename;
  }
  
}
