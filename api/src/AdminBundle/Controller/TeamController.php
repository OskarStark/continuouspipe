<?php

namespace AdminBundle\Controller;

use ContinuousPipe\River\Flow\Projections\FlatFlowRepository;
use ContinuousPipe\River\View\Tide;
use ContinuousPipe\River\View\TideRepository;
use ContinuousPipe\Security\Credentials\BucketRepository;
use ContinuousPipe\Security\Team\Team;
use ContinuousPipe\Security\Team\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route(service="admin.controller.team")
 */
class TeamController
{
    /**
     * @var TeamRepository
     */
    private $teamRepository;
    /**
     * @var FlatFlowRepository
     */
    private $flowRepository;
    /**
     * @var TideRepository
     */
    private $tideRepository;
    /**
     * @var BucketRepository
     */
    private $bucketRepository;

    /**
     * @param TeamRepository $teamRepository
     * @param FlatFlowRepository $flowRepository
     * @param TideRepository $tideRepository
     * @param BucketRepository $bucketRepository
     */
    public function __construct(TeamRepository $teamRepository, FlatFlowRepository $flowRepository, TideRepository $tideRepository, BucketRepository $bucketRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->flowRepository = $flowRepository;
        $this->tideRepository = $tideRepository;
        $this->bucketRepository = $bucketRepository;
    }

    /**
     * @Route("/", name="admin_teams")
     * @Template
     */
    public function listAction()
    {
        $items = array_map(function (Team $team) {
            $flows = $this->flowRepository->findByTeam($team);

            /** @var Tide|null $lastTide */
            $lastTide = null;

            foreach ($flows as $flow) {
                $lastTides = $this->tideRepository->findLastByFlowUuid($flow->getUuid(), 1);

                foreach ($lastTides as $tide) {
                    if ($lastTide === null) {
                        $lastTide = $tide;
                    } elseif ($lastTide->getCreationDate() < $tide->getCreationDate()) {
                        $lastTide = $tide;
                    }
                }
            }

            return [
                'team' => $team,
                'flows' => $flows,
                'last_tide' => $lastTide,
            ];
        }, $this->teamRepository->findAll());

        return [
            'items' => $items,
        ];
    }

    /**
     * @Route("/teams/{team}", name="admin_team")
     * @ParamConverter("team", converter="team", options={"slug"="team"})
     * @Template
     */
    public function showAction(Team $team)
    {
        return [
            'team' => $team,
            'flows' => $this->flowRepository->findByTeam($team),
            'clusters' => $this->bucketRepository->find($team->getBucketUuid())->getClusters(),
        ];
    }
}
