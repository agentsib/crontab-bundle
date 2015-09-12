<?php

namespace AgentSIB\CrontabBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AgentSIBCrontabBundle extends Bundle
{
    public function getContainerExtension ()
    {
        if (null === $this->extension) {
            $basename = preg_replace('/Bundle$/', '', $this->getName());

            $class = $this->getNamespace().'\\DependencyInjection\\'.$basename.'Extension';
            if (class_exists($class)) {
                $extension = new $class();

                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
    }

    public function build (ContainerBuilder $container)
    {
        parent::build($container);
    }


}
