<?php

namespace ContinuousPipe\Pipe\Kubernetes\Transformer;

use ContinuousPipe\Pipe\Kubernetes\Naming\NamingStrategy;
use ContinuousPipe\Model\Component;
use Kubernetes\Client\Model\Container;
use Kubernetes\Client\Model\ContainerPort;
use Kubernetes\Client\Model\EnvironmentVariable;
use Kubernetes\Client\Model\ExecAction;
use Kubernetes\Client\Model\HttpGetAction;
use Kubernetes\Client\Model\Pod;
use Kubernetes\Client\Model\PodSpecification;
use Kubernetes\Client\Model\Probe;
use Kubernetes\Client\Model\ResourceLimits;
use Kubernetes\Client\Model\ResourceRequirements;
use Kubernetes\Client\Model\ResourceRequirementsRequests;
use Kubernetes\Client\Model\SecurityContext;
use Kubernetes\Client\Model\TcpSocketAction;
use Kubernetes\Client\Model\VolumeMount;

class PodTransformer
{
    /**
     * @var NamingStrategy
     */
    private $namingStrategy;
    /**
     * @var VolumeTransformer
     */
    private $volumeTransformer;

    public function __construct(NamingStrategy $namingStrategy, VolumeTransformer $volumeTransformer)
    {
        $this->namingStrategy = $namingStrategy;
        $this->volumeTransformer = $volumeTransformer;
    }

    /**
     * @param Component $component
     *
     * @return Pod
     */
    public function getPodFromComponent(Component $component)
    {
        $specification = new PodSpecification(
            [
                $this->createContainer($component),
            ],
            $this->createVolumes($component->getSpecification()),
            $this->getPodRestartPolicy($component),
            PodSpecification::DNS_POLICY_CLUSTER_FIRST
        );

        $metadata = $this->namingStrategy->getObjectMetadataFromComponent($component);

        return new Pod($metadata, $specification);
    }

    /**
     * @param Component\Specification $specification
     *
     * @return array
     */
    private function createVolumes(Component\Specification $specification)
    {
        $volumes = [];

        foreach ($specification->getVolumes() as $componentVolume) {
            $volumes[] = $this->volumeTransformer->getVolumeFromComponentVolume($componentVolume);
        }

        return $volumes;
    }

    /**
     * @param Component $component
     *
     * @return Container
     */
    private function createContainer(Component $component)
    {
        $specification = $component->getSpecification();

        if ($runtimePolicy = $specification->getRuntimePolicy()) {
            $securityContext = new SecurityContext($runtimePolicy->isPrivileged());
        } else {
            $securityContext = null;
        }

        return new Container(
            $component->getName(),
            $this->getImageName($specification->getSource()),
            $this->createEnvironmentVariables($specification->getEnvironmentVariables()),
            $this->createPorts($specification->getPorts()),
            $this->createVolumeMounts($specification->getVolumeMounts()),
            Container::PULL_POLICY_ALWAYS,
            $specification->getCommand(),
            $securityContext,
            $this->createResources($specification->getResources()),
            $this->createProbe($component->getDeploymentStrategy()->getLivenessProbe()),
            $this->createProbe($component->getDeploymentStrategy()->getReadinessProbe())
        );
    }

    /**
     * @param Component\VolumeMount[] $volumeMounts
     *
     * @return VolumeMount[]
     */
    private function createVolumeMounts(array $volumeMounts)
    {
        return array_map(function (Component\VolumeMount $volumeMount) {
            return new VolumeMount($volumeMount->getName(), $volumeMount->getMountPath(), $volumeMount->isReadOnly());
        }, $volumeMounts);
    }

    /**
     * @param Component\Port[] $ports
     *
     * @return ContainerPort[]
     */
    private function createPorts(array $ports)
    {
        return array_map(function (Component\Port $port) {
            return new ContainerPort($port->getIdentifier(), $port->getPort(), strtoupper($port->getProtocol()));
        }, $ports);
    }

    /**
     * @param Component\EnvironmentVariable[] $environmentVariables
     *
     * @return EnvironmentVariable[]
     */
    private function createEnvironmentVariables(array $environmentVariables)
    {
        return array_map(function (Component\EnvironmentVariable $environmentVariable) {
            return new EnvironmentVariable($environmentVariable->getName(), $environmentVariable->getValue());
        }, $environmentVariables);
    }

    /**
     * @param Component\Source $source
     *
     * @return string
     */
    private function getImageName(Component\Source $source)
    {
        $image = $source->getImage();

        if ($repository = $source->getRepository()) {
            $image = $repository.'/'.$image;
        }
        if ($tag = $source->getTag()) {
            $image = $image.':'.$tag;
        }

        return $image;
    }

    /**
     * Get pod's restart policy.
     *
     * @param Component $component
     *
     * @return string
     */
    private function getPodRestartPolicy(Component $component)
    {
        $scalability = $component->getSpecification()->getScalability();

        return $scalability->isEnabled() ? PodSpecification::RESTART_POLICY_ALWAYS : PodSpecification::RESTART_POLICY_NEVER;
    }

    /**
     * @param Component\Probe|null $componentProbe
     *
     * @return Probe|null
     */
    private function createProbe(Component\Probe $componentProbe = null)
    {
        if (null === $componentProbe) {
            return;
        }

        return new Probe(
            $componentProbe instanceof Component\Probe\Exec ? $this->createExecAction($componentProbe) : null,
            $componentProbe instanceof Component\Probe\Http ? $this->createHttpAction($componentProbe) : null,
            $componentProbe instanceof Component\Probe\Tcp ? $this->createTcpAction($componentProbe) : null,
            $componentProbe->getInitialDelaySeconds(),
            $componentProbe->getTimeoutSeconds(),
            $componentProbe->getPeriodSeconds(),
            $componentProbe->getSuccessThreshold(),
            $componentProbe->getFailureThreshold()
        );
    }

    /**
     * @param Component\Resources|null $resources
     *
     * @return ResourceRequirements|null
     */
    private function createResources(Component\Resources $resources = null)
    {
        if (null === $resources) {
            return;
        }

        return new ResourceRequirements(
            (null !== ($requests = $resources->getRequests())) ? new ResourceRequirementsRequests(null, $requests->getMemory(), $requests->getCpu()) : null,
            (null !== ($limits = $resources->getLimits())) ? new ResourceLimits($limits->getMemory(), $limits->getCpu()) : null
        );
    }

    /**
     * @param Component\Probe\Http $componentProbe
     *
     * @return HttpGetAction
     */
    private function createHttpAction(Component\Probe\Http $componentProbe)
    {
        return new HttpGetAction(
            $componentProbe->getPath(),
            $componentProbe->getPort(),
            $componentProbe->getHost(),
            $componentProbe->getScheme()
        );
    }

    /**
     * @param Component\Probe\Tcp $componentProbe
     *
     * @return TcpSocketAction
     */
    private function createTcpAction(Component\Probe\Tcp $componentProbe)
    {
        return new TcpSocketAction($componentProbe->getPort());
    }

    /**
     * @param Component\Probe\Exec $componentProbe
     *
     * @return ExecAction
     */
    private function createExecAction(Component\Probe\Exec $componentProbe)
    {
        return new ExecAction($componentProbe->getCommand());
    }
}
