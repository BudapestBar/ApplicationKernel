<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BudapestBar\Component\ApplicationKernel;


use BudapestBar\Component\ApplicationKernel\Fixtures\KernelForTest;
use BudapestBar\Component\HttpKernel\HttpKernelInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ApplicationKernelTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Symfony\Component\EventDispatcher\EventDispatcher')) {
        //    $this->markTestSkipped('The "EventDispatcher" component is not available');
        }

        if (!class_exists('Symfony\Component\Routing\Router')) {
        //    $this->markTestSkipped('The "Routing" component is not available');
        }

        if (!class_exists('Symfony\Component\DependencyInjection\Container')) {
        //    $this->markTestSkipped('The "DependencyInjection" component is not available');
        }

    }

    public function testConstructor()
    {
        $env = 'test_env';
        $debug = true;

        $kernel = new KernelForTest($env, $debug);

        $this->assertEquals($env, $kernel->getEnvironment());
        $this->assertEquals($debug, $kernel->isDebug());
        $this->assertFalse($kernel->isBooted());
        $this->assertLessThanOrEqual(microtime(true), $kernel->getStartTime());
        $this->assertNull($kernel->getContainer());
    }

    public function testHandleCallsHandleOnHttpKernel()
    {
        $type = HttpKernelInterface::MASTER_REQUEST;
        $catch = true;
        $request = new Request();

        $httpKernelMock = $this->getMockBuilder('BudapestBar\Component\HttpKernel\HttpKernel')
            ->disableOriginalConstructor()
            ->getMock();
            
        $httpKernelMock
            ->expects($this->once())
            ->method('handle')
            ->with($request, $type, $catch);

        $kernel = $this->getMockBuilder('BudapestBar\Component\ApplicationKernel\Fixtures\KernelForTest')
            ->disableOriginalConstructor()
            ->setMethods(array('getHttpKernel'))
            ->getMock();

        $kernel->expects($this->once())
            ->method('getHttpKernel')
            ->will($this->returnValue($httpKernelMock));
            

        $kernel->setFixtures('prod', false);

        $kernel->handle($request, $type, $catch);
    }

    public function testHandleBootsTheKernel()
    {
        $type = HttpKernelInterface::MASTER_REQUEST;
        $catch = true;
        $request = new Request();

        $httpKernelMock = $this->getMockBuilder('BudapestBar\Component\HttpKernel\HttpKernel')
            ->disableOriginalConstructor()
            ->getMock();

        $kernel = $this->getMockBuilder('BudapestBar\Component\ApplicationKernel\Fixtures\KernelForTest')
            ->disableOriginalConstructor()
            ->setMethods(array('getHttpKernel', 'boot'))
            ->getMock();

        $kernel->expects($this->once())
            ->method('getHttpKernel')
            ->will($this->returnValue($httpKernelMock));

        $kernel->expects($this->once())
            ->method('boot');

        // required as this value is initialized
        // in the kernel constructor, which we don't call
        $kernel->setIsBooted(false);

        $kernel->setFixtures('prod', false);

        $kernel->handle($request, $type, $catch);
    }

}