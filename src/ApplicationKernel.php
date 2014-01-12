<?php


namespace BudapestBar\Component\ApplicationKernel;

use BudapestBar\Component\ApplicationKernel\DependencyInjection\MergeExtensionConfigurationPass;
use BudapestBar\Component\ApplicationKernel\DependencyInjection\AddClassesToCachePass;

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
class ApplicationKernel implements HttpKernelInterface, TerminableInterface
{

	protected $packages;
    protected $container;
    protected $rootDir;
    protected $environment;
    protected $debug;
    protected $booted = false;
    protected $name;
    protected $startTime;
//    protected $loadClassCache;

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

        $this->rootDir = $this->getRootDir();
        $this->name = $this->getName();

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

        $this->initializePackagess();
        $this->initializeContainer();

        foreach ($this->getPackages() as $package) {
            
            $package->setContainer($this->container);
            $package->boot();
        
        }

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

        foreach ($this->getPackages() as $package) {
            
            $package->shutdown();
            $package->setContainer(null);
        
        }

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

    public function getEnvironment() {

    	return $this->environment;

    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->rootDir;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCacheDir()
    {
        return $this->rootDir.'/cache/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getName()
    {
        if (null === $this->name) {
            $this->name = preg_replace('/[^a-zA-Z0-9_]+/', '', basename($this->rootDir));
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }



    protected function getKernelParameters() {

    	return array_merge(
            array(
                'kernel.root_dir'        => $this->rootDir,
                'kernel.environment'     => $this->environment,
                'kernel.debug'           => $this->debug,
                'kernel.name'            => $this->name,
                'kernel.cache_dir'       => $this->getCacheDir(),
                'kernel.container_class' => $this->getContainerClass(),
            ),
            $this->getEnvParameters()
        );

    }

    /**
     * Gets the environment parameters.
     *
     * Only the parameters starting with "SYMFONY__" are considered.
     *
     * @return array An array of parameters
     */
    protected function getEnvParameters()
    {
        $parameters = array();
        foreach ($_SERVER as $key => $value) {
            if (0 === strpos($key, 'BUDAPESTBAR__')) {
                $parameters[strtolower(str_replace('__', '.', substr($key, 13)))] = $value;
            }
        }

        return $parameters;
    }

    protected function initializePackages()
    {


        $this->packages = array();

        foreach ($this->registerPackages() as $package) {

            $name = $package->getName();
            if (isset($this->packages[$name])) {
                throw new \LogicException(sprintf('Trying to register two packages with the same name "%s"', $name));
            }
            $this->packages[$name] = $extension;

        }

    }

    protected function initializeContainer()
    {

        /*

        $class = $this->getContainerClass();
        
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug);

        $fresh = true;
        
        if (!$cache->isFresh()) {

            $container = $this->buildContainer();

            $container->compile();
            
            $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

            $fresh = false;
        }

        require_once $cache;

        $this->container = new $class();

        */
       
       	$container = $this->buildContainer();

        $container->compile();
       
       	$this->container = $container;

        $this->container->set('kernel', $this);

       	// if (!$fresh && $this->container->has('cache_warmer')) {
        //    $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
       	// }

    }

    /**
     * Gets the container class.
     *
     * @return string The container class
     */
    protected function getContainerClass()
    {
        return $this->name.ucfirst($this->environment).($this->debug ? 'Debug' : '').'ProjectContainer';
    }

    protected function getContainerBaseClass() {

        return 'Container';

    }

    protected function buildContainer() {

    	/*

    	foreach (array('cache' => $this->getCacheDir()) as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException(sprintf("Unable to create the %s directory (%s)\n", $name, $dir));
                }
            } elseif (!is_writable($dir)) {
                throw new \RuntimeException(sprintf("Unable to write in the %s directory (%s)\n", $name, $dir));
            }
        }

        */

        $container = $this->getContainerBuilder();

        $container->addObjectResource($this);

        $this->prepareContainer($container);

        if (null !== $cont = $this->registerContainerConfiguration($this->getContainerLoader($container))) {
            $container->merge($cont);
        }

        // $container->addCompilerPass(new AddClassesToCachePass($this));

        return $container;

    }

    /**
     * Prepares the ContainerBuilder before it is compiled.
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        
        $extensions = array();

        foreach ($this->packages as $package) {

            if ($extension = $package->getContainerExtension()) {
                $container->registerExtension($extension);
                $extensions[] = $extension->getAlias();
            }

            if ($this->debug) {
                $container->addObjectResource($package);
            }
        }
        foreach ($this->packages as $package) {
            $package->build($container);
        }

        // ensure these packages are implicitly loaded
        $container->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass($packages));
    }

    /**
     * Gets a new ContainerBuilder instance used to build the package container.
     *
     * @return ContainerBuilder
     */
    protected function getContainerBuilder()
    {
        
        $container = new ContainerBuilder(new ParameterBag($this->getKernelParameters()));

        // if (class_exists('ProxyManager\Configuration')) {
        //     $container->setProxyInstantiator(new RuntimeInstantiator());
        // }

        return $container;
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

    /**
     * Dumps the package container to PHP code in the cache.
     *
     * @param ConfigCache      $cache     The config cache
     * @param ContainerBuilder $container The package container
     * @param string           $class     The name of the class to generate
     * @param string           $baseClass The name of the container's base class
     */
    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass)
    {
        // cache the container
        $dumper = new PhpDumper($container);

        // if (class_exists('ProxyManager\Configuration')) {
        //     $dumper->setProxyDumper(new ProxyDumper());
        // }

        $content = $dumper->dump(array('class' => $class, 'base_class' => $baseClass));

        if (!$this->debug) {
            $content = static::stripComments($content);
        }

        $cache->write($content, $container->getResources());
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the comments removed
     */
    public static function stripComments($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }

        $rawChunk = '';
        $output = '';
        $tokens = token_get_all($source);
        for (reset($tokens); false !== $token = current($tokens); next($tokens)) {
            if (is_string($token)) {
                $rawChunk .= $token;
            } elseif (T_START_HEREDOC === $token[0]) {
                $output .= preg_replace(array('/\s+$/Sm', '/\n+/S'), "\n", $rawChunk).$token[1];
                do {
                    $token = next($tokens);
                    $output .= $token[1];
                } while ($token[0] !== T_END_HEREDOC);
                $rawChunk = '';
            } elseif (!in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $rawChunk .= $token[1];
            }
        }

        // replace multiple new lines with a single newline
        $output .= preg_replace(array('/\s+$/Sm', '/\n+/S'), "\n", $rawChunk);

        return $output;
    }

    public function serialize()
    {
        return serialize(array($this->environment, $this->debug));
    }

    public function unserialize($data)
    {
        list($environment, $debug) = unserialize($data);

        $this->__construct($environment, $debug);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {

        // $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    
    }

    public function registerPackages()
    {
        $packages = array(

            new BudapestBar\Component\AccessControlClientpackage\AccessControlClientpackage()

        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        //    $packages[] = new Acme\Demopackage\AcmeDemopackage();
        }

        return $packages;
    }

}

