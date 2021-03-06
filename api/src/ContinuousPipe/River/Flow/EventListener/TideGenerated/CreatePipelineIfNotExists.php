<?php

namespace ContinuousPipe\River\Flow\EventListener\TideGenerated;

use ContinuousPipe\River\Event\TideCreated;
use ContinuousPipe\River\Repository\FlowRepository;
use SimpleBus\Message\Bus\MessageBus;

class CreatePipelineIfNotExists
{
    /**
     * @var FlowRepository
     */
    private $flowRepository;

    /**
     * @var MessageBus
     */
    private $eventBus;

    /**
     * @param FlowRepository $flowRepository
     */
    public function __construct(FlowRepository $flowRepository, MessageBus $eventBus)
    {
        $this->flowRepository = $flowRepository;
        $this->eventBus = $eventBus;
    }

    public function notify(TideCreated $event)
    {
        $flow = $this->flowRepository->find($event->getFlowUuid());
        $flow->tideWasCreated($event);

        foreach ($flow->raisedEvents() as $event) {
            $this->eventBus->handle($event);
        }
    }
}
