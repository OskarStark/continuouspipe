<?php

namespace ContinuousPipe\River\Task\Run;

use ContinuousPipe\Pipe\Client\Client;
use ContinuousPipe\Pipe\View\Deployment;
use ContinuousPipe\River\Event\TideEvent;
use ContinuousPipe\River\EventCollection;
use ContinuousPipe\River\Task\EventDrivenTask;
use ContinuousPipe\River\Task\Run\Event\RunFailed;
use ContinuousPipe\River\Task\Run\Event\RunStarted;
use ContinuousPipe\River\Task\Run\Event\RunSuccessful;
use ContinuousPipe\River\Task\Run\RunRequest\DeploymentRequestFactory;
use ContinuousPipe\River\Task\TaskDetails;
use ContinuousPipe\River\Task\TaskQueued;
use ContinuousPipe\River\Tide;
use LogStream\LoggerFactory;
use LogStream\Node\Text;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Bus\MessageBus;

class RunTask extends EventDrivenTask
{
    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * @var RunContext
     */
    private $context;

    /**
     * @var RunTaskConfiguration
     */
    private $configuration;

    /**
     * @var Uuid|null
     */
    private $startedRunUuid;

    /**
     * @param EventCollection      $events
     * @param LoggerFactory        $loggerFactory
     * @param MessageBus           $commandBus
     * @param RunContext           $context
     * @param RunTaskConfiguration $configuration
     */
    public function __construct(EventCollection $events, LoggerFactory $loggerFactory, MessageBus $commandBus, RunContext $context, RunTaskConfiguration $configuration)
    {
        parent::__construct($context, $events);

        $this->loggerFactory = $loggerFactory;
        $this->commandBus = $commandBus;
        $this->context = $context;
        $this->configuration = $configuration;
    }

    public function run(Tide $tide, DeploymentRequestFactory $deploymentRequestFactory, Client $pipeClient)
    {
        $logger = $this->loggerFactory->from($this->context->getLog());

        $log = $logger->child(new Text(sprintf(
            'Running %s',
            $this->getLabel()
        )))->getLog();

        $this->context->setTaskLog($log);
        $this->events->raiseAndApply(TaskQueued::fromContext($this->context));

        $deploymentRequest = $deploymentRequestFactory->createDeploymentRequest(
            $tide,
            new TaskDetails($this->context->getTaskId(), $log->getId()),
            $this->configuration
        );

        $deployment = $pipeClient->start($deploymentRequest, $tide->getUser());

        $this->events->raiseAndApply(new RunStarted(
            $tide->getUuid(),
            $this->getIdentifier(),
            $deployment->getUuid()
        ));
    }

    public function receiveDeploymentNotification(Deployment $deployment)
    {
        if (null === $this->startedRunUuid || $deployment->getUuid() != $this->startedRunUuid) {
            return;
        }

        if ($deployment->isSuccessful()) {
            $this->events->raiseAndApply(new RunSuccessful(
                $this->context->getTideUuid(),
                $deployment
            ));
        } elseif ($deployment->isFailed()) {
            $this->events->raiseAndApply(new RunFailed(
                $this->context->getTideUuid(),
                $deployment
            ));
        } else {
            throw new \RuntimeException(sprintf(
                'Received a deployment notification for status "%s"',
                $deployment->getStatus()
            ));
        }
    }

    public function apply(TideEvent $event)
    {
        parent::apply($event);

        if ($event instanceof RunStarted) {
            $this->startedRunUuid = $event->getRunUuid();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function accept(TideEvent $event)
    {
        if ($event instanceof RunFailed || $event instanceof RunSuccessful) {
            if (null === $this->startedRunUuid) {
                return false;
            }

            return $this->startedRunUuid->equals($event->getRunUuid());
        }

        return parent::accept($event);
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return 0 < $this->numberOfEventsOfType(RunSuccessful::class);
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return 0 < $this->numberOfEventsOfType(RunFailed::class);
    }

    /**
     * @return null|Uuid
     */
    public function getStartedRunUuid()
    {
        return $this->startedRunUuid;
    }
}
