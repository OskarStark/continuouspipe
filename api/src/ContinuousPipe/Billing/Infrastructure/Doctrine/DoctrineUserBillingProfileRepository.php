<?php

namespace ContinuousPipe\Billing\Infrastructure\Doctrine;

use ContinuousPipe\Billing\BillingProfile\UserBillingProfile;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileException;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileNotFound;
use ContinuousPipe\Billing\BillingProfile\UserBillingProfileRepository;
use ContinuousPipe\Security\Team\Team;
use ContinuousPipe\Security\Team\TeamRepository;
use ContinuousPipe\Security\User\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Ramsey\Uuid\UuidInterface;

class DoctrineUserBillingProfileRepository implements UserBillingProfileRepository
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TeamRepository
     */
    private $teamRepository;

    public function __construct(EntityManager $entityManager, TeamRepository $teamRepository)
    {
        $this->entityManager = $entityManager;
        $this->teamRepository = $teamRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUser(User $user): array
    {
        return $this->getUserBillingProfileRepository()->createQueryBuilder('bp')
            ->join('bp.admins', 'admins')
            ->where('admins.username = :username')
            ->setParameter('username', $user->getUsername())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function save(UserBillingProfile $billingProfile)
    {
        /** @var UserBillingProfile $merged */
        $merged = $this->entityManager->merge($billingProfile);
        $merged->setAdmins($billingProfile->getAdmins());
        $merged->setTeams($billingProfile->getTeams());

        $this->entityManager->persist($merged);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function find(UuidInterface $uuid): UserBillingProfile
    {
        if (null === ($billingProfile = $this->getUserBillingProfileRepository()->find($uuid->toString()))) {
            throw new UserBillingProfileNotFound(sprintf(
                'No billing profile found with identifier %s',
                $uuid
            ));
        }

        return $billingProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function link(Team $team, UserBillingProfile $billingProfile)
    {
        $team = $this->entityManager->merge($team);

        $billingProfile = $this->find($billingProfile->getUuid());
        $billingProfile->getTeams()->add($team);

        $this->entityManager->persist($billingProfile);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(Team $team, UserBillingProfile $billingProfile)
    {
        $billingProfile = $this->find($billingProfile->getUuid());
        $matchingTeams = $billingProfile->getTeams()->filter(function (Team $matchingTeam) use ($team) {
            return $matchingTeam->getSlug() == $team->getSlug();
        });

        foreach ($matchingTeams as $team) {
            $billingProfile->getTeams()->removeElement($team);
        }

        $this->entityManager->persist($billingProfile);
        $this->entityManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function findByTeam(Team $team): UserBillingProfile
    {
        $billingProfile = $this->getUserBillingProfileRepository()->createQueryBuilder('bp')
            ->join('bp.teams', 'teams')
            ->where('teams.slug = :slug')
            ->setParameter('slug', $team->getSlug())
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $billingProfile) {
            throw new UserBillingProfileNotFound(sprintf(
                'No billing profile found for team %s',
                $team->getSlug()
            ));
        }

        return $billingProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UserBillingProfile $billingProfile)
    {
        try {
            $this->entityManager->remove($billingProfile);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new UserBillingProfileException('The billing profile is linked with some resources that needs to be deleted before', 400, $e);
        }
    }

    private function getUserBillingProfileRepository()
    {
        return $this->entityManager->getRepository(UserBillingProfile::class);
    }
}
