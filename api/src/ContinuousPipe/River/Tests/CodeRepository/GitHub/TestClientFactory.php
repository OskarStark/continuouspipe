<?php

namespace ContinuousPipe\River\Tests\CodeRepository\GitHub;

use ContinuousPipe\River\GitHub\ClientFactory;
use ContinuousPipe\Security\Credentials\Bucket;
use ContinuousPipe\Security\User\User;
use Github\Client;
use Github\HttpClient\HttpClientInterface;
use Ramsey\Uuid\Uuid;

class TestClientFactory implements ClientFactory
{
    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @param HttpClientInterface $httpClient
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function createClientForUser(User $user)
    {
        return new Client($this->httpClient);
    }

    /**
     * {@inheritdoc}
     */
    public function createClientFromBucket(Bucket $credentialsBucket)
    {
        return $this->createClientFromBucketUuid($credentialsBucket->getUuid());
    }

    /**
     * {@inheritdoc}
     */
    public function createClientFromBucketUuid(Uuid $bucketUuid)
    {
        return new Client($this->httpClient);
    }

    /**
     * {@inheritdoc}
     */
    public function createClientForCurrentUser()
    {
        return new Client($this->httpClient);
    }
}
