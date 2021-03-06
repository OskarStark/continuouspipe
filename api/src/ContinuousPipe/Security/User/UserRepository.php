<?php

namespace ContinuousPipe\Security\User;

use ContinuousPipe\Security\User\UserNotFound;

interface UserRepository
{
    /**
     * @param string $username
     *
     * @throws UserNotFound
     *
     * @return User
     */
    public function findOneByUsername($username);

    public function save(User $user);
}
