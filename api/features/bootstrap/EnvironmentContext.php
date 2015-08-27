<?php

use Behat\Behat\Context\Context;
use ContinuousPipe\Model\Environment;
use ContinuousPipe\Pipe\DeploymentContext;
use ContinuousPipe\Pipe\DeploymentRequest;
use ContinuousPipe\Pipe\Event\DeploymentFailed;
use ContinuousPipe\Pipe\Event\DeploymentSuccessful;
use ContinuousPipe\Pipe\Tests\Adapter\Fake\FakeProvider;
use ContinuousPipe\Pipe\View\DeploymentRepository;
use ContinuousPipe\User\User;
use LogStream\Tests\MutableWrappedLog;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use ContinuousPipe\Pipe\View\Deployment;
use ContinuousPipe\Pipe\EventBus\EventStore;
use Rhumsaa\Uuid\Uuid;

class EnvironmentContext implements Context
{
    /**
     * @var ProviderContext
     */
    private $providerContext;

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var Uuid
     */
    private $lastDeploymentUuid;

    /**
     * @var DeploymentContext
     */
    private $deploymentContext;

    /**
     * @var MessageBus
     */
    private $eventBus;
    /**
     * @var DeploymentRepository
     */
    private $deploymentRepository;

    /**
     * @param Kernel $kernel
     * @param EventStore $eventStore
     * @param DeploymentRepository $deploymentRepository
     * @param MessageBus $eventBus
     */
    public function __construct(Kernel $kernel, EventStore $eventStore, DeploymentRepository $deploymentRepository, MessageBus $eventBus)
    {
        $this->kernel = $kernel;
        $this->eventStore = $eventStore;
        $this->deploymentRepository = $deploymentRepository;
        $this->eventBus = $eventBus;
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $this->providerContext = $scope->getEnvironment()->getContext('ProviderContext');
    }

    /**
     * @When I send a valid deployment request
     */
    public function iSendAValidDeploymentRequest()
    {
        $this->providerContext->iHaveAFakeProviderNamed('foo');
        $this->sendDeploymentRequest('fake/foo', 'foo');
    }

    /**
     * @Then the environment should be created or updated
     */
    public function theEnvironmentShouldBeCreatedOrUpdated()
    {
        $events = $this->eventStore->findByDeploymentUuid(
            $this->lastDeploymentUuid
        );

        if (0 === count($events)) {
            throw new \RuntimeException('Expected to have at least one event for this deployment, found 0');
        }
    }

    /**
     * @Then the deployment should be successful
     */
    public function theDeploymentShouldBeSuccessful()
    {
        $events = $this->eventStore->findByDeploymentUuid(
            $this->lastDeploymentUuid
        );

        $deploymentSuccessfulEvents = array_filter($events, function($event) {
            return $event instanceof DeploymentSuccessful;
        });

        if (count($deploymentSuccessfulEvents) == 0) {
            throw new \RuntimeException('No event successful events found');
        }
    }

    /**
     * @param string $providerName
     * @param string $environmentName
     * @param string $template
     */
    public function sendDeploymentRequest($providerName, $environmentName, $template = 'simple-app')
    {
        $simpleAppComposeContents = file_get_contents(__DIR__.'/../fixtures/'.$template.'.yml');
        $contents = json_encode([
            'environmentName' => $environmentName,
            'providerName' => $providerName,
            'dockerComposeContents' => $simpleAppComposeContents,
        ]);

        $this->response = $this->kernel->handle(Request::create('/deployments', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $contents));

        if (200 !== $this->response->getStatusCode()) {
            echo $this->response->getContent();

            throw new \RuntimeException(sprintf('Expected response code 200, got %d', $this->response->getStatusCode()));
        }

        $deployment = json_decode($this->response->getContent(), true);
        if (Deployment::STATUS_SUCCESS != $deployment['status']) {
            throw new \RuntimeException(sprintf(
                'Expected deployment status to be "%s" but got "%s"',
                Deployment::STATUS_SUCCESS,
                $deployment['status']
            ));
        }

        $this->lastDeploymentUuid = Uuid::fromString($deployment['uuid']);
    }

    /**
     * @Given I have a running deployment
     */
    public function iHaveARunningDeployment()
    {
        $deployment = $this->deploymentRepository->save(
            Deployment::fromRequest(
                new DeploymentRequest(),
                new User('sroze@inviqa.com')
            )
        );

        $this->lastDeploymentUuid = $deployment->getUuid();
    }

    /**
     * @When the deployment is successful
     */
    public function theDeploymentIsSuccessful()
    {
        $this->eventBus->handle(new DeploymentSuccessful($this->lastDeploymentUuid));
    }

    /**
     * @When the deployment is failed
     */
    public function theDeploymentIsFailed()
    {
        $this->eventBus->handle(new DeploymentFailed($this->lastDeploymentUuid));
    }

    /**
     * @Then a notification should be sent back
     */
    public function aNotificationShouldBeSentBack()
    {
    }
}
