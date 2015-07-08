<?php

namespace Extra\Composer\Installers;

use Composer\IO\IOInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class Installer extends LibraryInstaller
{
    /**
     * Package types to installer class map
     *
     * @var array
     */
    private $supportedTypes = array(
        'site'       => 'SiteInstaller',
        'drupal'       => 'DrupalInstaller',
    );

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $type = $package->getType();
        $installerType = $this->findInstallerByType($type);
        if ($installerType === false) {
            throw new \InvalidArgumentException(
                'Sorry the package type of this package is not yet supported.'
            );
        }
        $class = 'Extra\\Composer\\Installers\\' . $this->supportedTypes[$installerType];
        $installer = new $class($package, $this->composer, $this->getIO());
        return $installer->getInstallPath($package, $installerType);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            throw new \InvalidArgumentException('Package is not installed: '.$package);
        }
        $repo->removePackage($package);
        $installPath = $this->getInstallPath($package);
        $this->io->write(sprintf('Deleting %s - %s', $installPath, $this->filesystem->removeDirectory($installPath) ? '<comment>deleted</comment>' : '<error>not deleted</error>'));
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package) {

        parent::install($repo, $package);

        if ($package->getType() === 'drupal-core') {
            $extra = $this->composer->getPackage()->getExtra();
            if (!empty($extra['symlink'])) {
                $linkPath = realpath($extra['symlink']['to']);
                $targetPath = realpath($extra['symlink']['from']);

                if (substr($linkPath, -1) !== '/') {
                    $linkPath .= '/';
                }
                $linkPath .= $extra['symlink']['from'];

                if (is_link($linkPath)) {
                    unlink($linkPath);
                }

                symlink($targetPath, $linkPath);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        $installerType = $this->findInstallerByType($packageType);
        if ($installerType === false) {
            return false;
        }
        $locationPattern = $this->getLocationPattern($installerType);
        return preg_match('#' . $installerType . '-' . $locationPattern . '#', $packageType, $matches) === 1;
    }

    /**
     * @param  string $type
     * @return string
     */
    protected function findInstallerByType($type)
    {
        $installerType = false;
        krsort($this->supportedTypes);
        foreach ($this->supportedTypes as $key => $val) {
            if ($key === substr($type, 0, strlen($key))) {
                $installerType = substr($type, 0, strlen($key));
                break;
            }
        }
        return $installerType;
    }
    /**
     * Get the second part of the regular expression to check for support of a
     * package type
     *
     * @param  string $frameworkType
     * @return string
     */
    protected function getLocationPattern($frameworkType)
    {
        $pattern = false;
        if (!empty($this->supportedTypes[$frameworkType])) {
            $frameworkClass = 'Extra\\Composer\\Installers\\' . $this->supportedTypes[$frameworkType];
            /** @var BaseInstaller $framework */
            $framework = new $frameworkClass(null, $this->composer, $this->getIO());
            $locations = array_keys($framework->getLocations());
            $pattern = $locations ? '(' . implode('|', $locations) . ')' : false;
        }
        return $pattern ? : '(\w+)';
    }
    /**
     * Get I/O object
     *
     * @return IOInterface
     */
    private function getIO()
    {
        return $this->io;
    }
}