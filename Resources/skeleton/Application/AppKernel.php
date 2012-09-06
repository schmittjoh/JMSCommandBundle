[?php

namespace <?php echo $namespace ?>\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config)
    {
        parent::__construct('test', true);

        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        if ( ! is_file($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
<?php if ($withDatabase): ?>
            new \Symfony\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\DoctrineFixturesBundle\DoctrineFixturesBundle(),
<?php endif ?>
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \<?php echo $namespace ?>\<?php echo $name ?>(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/<?php echo $name ?>';
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($config)
    {
        $this->__construct($config);
    }
}