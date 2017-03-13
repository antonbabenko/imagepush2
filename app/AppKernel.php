<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

// Remove these defines when amazonwebservices/aws-sdk-for-php and gaufrette is updated. This raise noticeexception in PHP 5.6.
define('CURLOPT_CLOSEPOLICY', 1);
define('CURLCLOSEPOLICY_LEAST_RECENTLY_USED', 1);

class AppKernel extends Kernel
{

    public function registerBundles()
    {

        $bundles = array(
            // Symfony core
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),

            // External bundles
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
//            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new Ornicar\ApcBundle\OrnicarApcBundle(),
            new Aws\Symfony\AwsBundle(),

            // Project bundles
            new Imagepush\ImagepushBundle\ImagepushBundle(),
            new Imagepush\SitemapBundle\SitemapBundle(),
            );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();

            include_once __DIR__ . "/../src/CustomDebug.php";
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }

}
