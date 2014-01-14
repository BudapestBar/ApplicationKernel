<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BudapestBar\Component\ApplicationKernel\Fixtures;

use BudapestBar\Component\ApplicationKernel\ApplicationKernel;

use Symfony\Component\Config\Loader\LoaderInterface;

class KernelForTest extends ApplicationKernel
{

    public function registerPackages()
    {

        $packages = array(

            new Package1()

        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        //    $packages[] = new Acme\Demopackage\AcmeDemopackage();
        }

        return $packages;

    }

    public function init()
    {
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    public function isBooted()
    {
        return $this->booted;
    }

    public function setIsBooted($value)
    {
        $this->booted = (Boolean) $value;
    }
}
