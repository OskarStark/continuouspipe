<?php

namespace ContinuousPipe\River\View;

use ContinuousPipe\River\CodeRepository;
use Rhumsaa\Uuid\Uuid;

class Flow
{
    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var CodeRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param \ContinuousPipe\River\Flow $flow
     *
     * @return Flow
     */
    public static function fromFlow(\ContinuousPipe\River\Flow $flow)
    {
        $flowContext = $flow->getContext();

        $view = new self();
        $view->uuid = $flowContext->getFlowUuid();
        $view->repository = $flowContext->getCodeRepository();
        $view->configuration = $flowContext->getConfiguration();

        return $view;
    }

    /**
     * @return Uuid
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
