<?php

namespace BudapestBar\Component\ApplicationKernel;

use BudapestBar\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Symfony\Component\Config\Loader\LoaderInterface;

interface KernelInterface extends HttpKernelInterface, \Serializable
{

	/**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot();

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     *
     * @api
     */
    public function shutdown();

	/**
     * Gets the current container.
     *
     * @return ContainerInterface A ContainerInterface instance
     *
     * @api
     */
    public function getContainer();

}