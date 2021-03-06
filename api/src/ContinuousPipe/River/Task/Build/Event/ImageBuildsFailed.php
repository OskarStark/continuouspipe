<?php

namespace ContinuousPipe\River\Task\Build\Event;

use ContinuousPipe\River\Event\TideEvent;
use ContinuousPipe\River\Task\TaskEvent;
use LogStream\Log;
use Ramsey\Uuid\Uuid;

class ImageBuildsFailed implements TideEvent, TaskEvent
{
    /**
     * @var Uuid
     */
    private $tideUuid;
    /**
     * @var Log
     */
    private $log;
    /**
     * @var string
     */
    private $taskIdentifier;
    /**
     * @var string|null
     */
    private $reason;

    /**
     * @param Uuid $tideUuid
     * @param string $taskIdentifier
     * @param Log $log
     * @param string|null $reason
     */
    public function __construct(Uuid $tideUuid, string $taskIdentifier, Log $log, string $reason = null)
    {
        $this->tideUuid = $tideUuid;
        $this->log = $log;
        $this->taskIdentifier = $taskIdentifier;
        $this->reason = $reason;
    }

    /**
     * @return Uuid
     */
    public function getTideUuid()
    {
        return $this->tideUuid;
    }

    /**
     * @return Log
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaskId()
    {
        return $this->taskIdentifier;
    }

    /**
     * @return null|string
     */
    public function getReason()
    {
        return $this->reason;
    }
}
