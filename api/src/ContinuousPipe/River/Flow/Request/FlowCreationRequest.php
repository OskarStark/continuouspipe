<?php

namespace ContinuousPipe\River\Flow\Request;

use JMS\Serializer\Annotation as JMS;

class FlowCreationRequest extends FlowUpdateRequest
{
    /**
     * @JMS\Type("string")
     *
     * @var string
     */
    private $repository;

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }
}
