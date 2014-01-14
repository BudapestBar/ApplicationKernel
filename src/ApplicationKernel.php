<?php


namespace BudapestBar\Component\ApplicationKernel;

use BudapestBar\Component\ApplicationKernel\DependencyInjection\MergeExtensionConfigurationPass;
use BudapestBar\Component\ApplicationKernel\DependencyInjection\AddClassesToCachePass;

use BudapestBar\Component\HttpKernel\HttpKernelInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;

/**
 * ApplicationKernel handles the dependencies and the core initialization
 */
class ApplicationKernel implements KernelInterface, TerminableInterface
{


    protected $environment;
    protected $debug;
    protected $startTime;
    protected $booted = false;
    protected $container;


    /**
     * Constructor.
     *
     * @param string  $environment The environment
     * @param Boolean $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {

        $this->environment = $environment;
        $this->debug = (Boolean) $debug;

        if ($this->debug) {

            $this->startTime = microtime(true);
        
        }

    }

    public function __clone()
    {
        if ($this->debug) {

            $this->startTime = microtime(true);
        
        }

        $this->booted = false;
        $this->container = null;
    }

    public function boot() {

        if (true === $this->booted) {
            return;
        }

        $this->initializeContainer();

        $this->booted = true;

    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function terminate(Request $request, Response $response)
    {
        if (false === $this->booted) {
            return;
        }

        if ($this->getHttpKernel() instanceof TerminableInterface) {
            $this->getHttpKernel()->terminate($request, $response);
        }
    }

    public function shutdown() {

        if (false === $this->booted) {

            return;
        
        }

        $this->booted = false;

        $this->container = null;
        
    }


    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {

            $this->boot();
        
        }

        return $this->getHttpKernel()->handle($request, $type, $catch);

    }

    public function getHttpKernel() {

        return $this->container->get('http_kernel');

    }

    public function getContainer() {

        return $this->container;
        
    }

    public function initializeContainer() {

        $container = new ContainerBuilder(new ParameterBag($this->getKernelParameters()));

        $container->addObjectResource($this);

        $this->registerContainerConfiguration($this->getContainerLoader($container)) {
            
            $container->merge($cont);


        $container->compile();
       
        $this->container = $container;

        $this->container->set('kernel', $this);


    }



    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    protected function getKernelParameters()
    {
        
        return array(
        //    'kernel.root_dir'        => $this->rootDir,
            'kernel.environment'     => $this->environment,
            'kernel.debug'           => $this->debug,
        //    'kernel.name'            => $this->name,
        //    'kernel.cache_dir'       => $this->getCacheDir(),
        //    'kernel.logs_dir'        => $this->getLogDir(),
        //    'kernel.bundles'         => $bundles,
        //    'kernel.charset'         => $this->getCharset(),
        //    'kernel.container_class' => $this->getContainerClass(),

        );
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerInterface $container The package container
     *
     * @return DelegatingLoader The loader
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this);
        $resolver = new LoaderResolver(array(
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new IniFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new ClosureLoader($container),
        ));

        return new DelegatingLoader($resolver);
    }

}

