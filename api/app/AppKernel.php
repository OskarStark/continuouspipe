<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
            new ContinuousPipe\SecurityBundle\ContinuousPipeSecurityBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new Mhujer\JmsSerializer\Uuid\SymfonyBundle\MhujerJmsSerializerUuidBundle(),
            new Beberlei\Bundle\MetricsBundle\BeberleiMetricsBundle(),
            new Tolerance\Bridge\Symfony\Bundle\ToleranceBundle\ToleranceBundle(),
            new ContinuousPipe\AtlassianAddonBundle\AtlassianAddonBundle(),
            new ContinuousPipe\PlatformBundle\ContinuousPipePlatformBundle(),
            new ContinuousPipe\DevelopmentEnvironmentBundle\DevelopmentEnvironmentBundle(),
            new ContinuousPipe\MessageBundle\MessageBundle(),
            new LogStream\LogStreamBundle(),
            new AdminBundle\AdminBundle(),
            new AppBundle\AppBundle(),
            new BuilderBundle\BuilderBundle(),
            new PipeBundle\PipeBundle(),
            new AuthenticatorBundle\AuthenticatorBundle(),

            // Administration dependencies
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Snc\RedisBundle\SncRedisBundle(),

            // API dependencies
            new Http\HttplugBundle\HttplugBundle(),
            new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
        );

        if (in_array($this->getEnvironment(), ['test', 'smoke_test'])) {
            $bundles[] = new AppTestBundle\AppTestBundle();
            $bundles[] = new BuilderTestBundle\BuilderTestBundle();
            $bundles[] = new PipeTestBundle\PipeTestBundle();
            $bundles[] = new AuthenticatorTestBundle\AuthenticatorTestBundle();
        }

        $bundles[] = new SimpleBus\SymfonyBridge\SimpleBusCommandBusBundle();
        $bundles[] = new SimpleBus\SymfonyBridge\SimpleBusEventBusBundle();
        $bundles[] = new SimpleBus\AsynchronousBundle\SimpleBusAsynchronousBundle();
        $bundles[] = new SimpleBus\JMSSerializerBundleBridge\SimpleBusJMSSerializerBundleBridgeBundle();
        $bundles[] = new Csa\Bundle\GuzzleBundle\CsaGuzzleBundle();

        if (in_array($this->getEnvironment(), array('dev', 'test', 'smoke_test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }
    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
