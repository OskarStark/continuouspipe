<?php

namespace ContinuousPipe\Watcher;

use ContinuousPipe\Security\Credentials\Cluster;
use LogStream\Log;

interface Watcher
{
    /**
     * @param Cluster\Kubernetes $kubernetes
     * @param string             $namespace
     * @param string             $pod
     *
     * @throws WatcherException
     *
     * @return WatcherLog
     */
    public function logs(Cluster\Kubernetes $kubernetes, string $namespace, string $pod);
}
