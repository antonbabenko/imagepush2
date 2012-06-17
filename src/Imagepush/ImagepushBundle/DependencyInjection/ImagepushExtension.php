<?php

namespace Imagepush\ImagepushBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImagepushExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config as $parameter => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $oneValue) {
                    $container->setParameter('imagepush.' . $key, $oneValue);
                }
            } else {
                $container->setParameter('imagepush.' . $parameter, $value);
            }
        }
    }

}
