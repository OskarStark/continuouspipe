<?php

namespace ContinuousPipe\Builder\Aggregate\Command;

use JMS\Serializer\Annotation as JMS;

class StartBuild
{
    /**
     * @JMS\Type("string")
     *
     * @var string
     */
    private $buildIdentifier;

    /**
     * @param string $buildIdentifier
     */
    public function __construct(string $buildIdentifier)
    {
        $this->buildIdentifier = $buildIdentifier;
    }

    /**
     * @return string
     */
    public function getBuildIdentifier(): string
    {
        return $this->buildIdentifier;
    }
}
