<?php

namespace ContinuousPipe\Authenticator\Infrastructure\Doctrine\Type;

use ContinuousPipe\Security\Credentials\Cluster\ClusterPolicy;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use JMS\Serializer\SerializerBuilder;

class JsonPoliciesType extends JsonType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        return $this->serializer()->serialize($value, 'json');
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return array();
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        return $this->serializer()->deserialize($value, 'array<'.ClusterPolicy::class.'>', 'json');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'json_policies';
    }

    private function serializer()
    {
        return SerializerBuilder::create()
            ->addMetadataDir(__DIR__.'/../../../../../../src/ContinuousPipe/Security/Resources/serializer', 'ContinuousPipe\\Security')
            ->build()
        ;
    }
}
