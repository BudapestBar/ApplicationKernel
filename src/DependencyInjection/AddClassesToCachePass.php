<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BudapestBar\Component\ApplicationKernel\DependencyInjection;

use BudapestBar\Component\ApplicationKernel\DependencyInjection\Extension;
use BudapestBar\Component\ApplicationKernel\Kernel;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Sets the classes to compile in the cache for the container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AddClassesToCachePass implements CompilerPassInterface
{
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = array();
        foreach ($container->getExtensions() as $extension) {
            if ($extension instanceof Extension) {
                $classes = array_merge($classes, $extension->getClassesToCompile());
            }
        }

        $this->kernel->setClassCache(array_unique($container->getParameterBag()->resolveValue($classes)));
    }
}
