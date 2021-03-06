<?php

namespace AdminBundle\Controller;

use ContinuousPipe\River\Command\StartTideCommand;
use ContinuousPipe\River\EventBus\EventStore;
use ContinuousPipe\River\Flow;
use ContinuousPipe\River\Recover\CancelTides\Command\CancelTideCommand;
use ContinuousPipe\River\View\TideRepository;
use ContinuousPipe\Security\Team\Team;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route(service="admin.controller.tide")
 */
class TideController
{
    /**
     * @var TideRepository
     */
    private $tideRepository;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $uiUrl;

    /**
     * @param TideRepository $tideRepository
     * @param EventStore $eventStore
     * @param PaginatorInterface $paginator
     * @param string $uiUrl
     */
    public function __construct(
        TideRepository $tideRepository,
        EventStore $eventStore,
        PaginatorInterface $paginator,
        MessageBus $commandBus,
        UrlGeneratorInterface $urlGenerator,
        string $uiUrl
    ) {
        $this->tideRepository = $tideRepository;
        $this->eventStore = $eventStore;
        $this->paginator = $paginator;
        $this->uiUrl = $uiUrl;
        $this->commandBus = $commandBus;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/teams/{team}/flows/{flow}/tides", name="admin_tides")
     * @ParamConverter("team", converter="team", options={"slug"="team"})
     * @ParamConverter("flow", converter="flow", options={"identifier"="flow", "flat"=true})
     * @Template
     */
    public function listAction(Team $team, Flow\Projections\FlatFlow $flow, Request $request)
    {
        return [
            'team' => $team,
            'flow' => $flow,
            'pagination' => $this->paginator->paginate(
                $this->tideRepository->findByFlowUuid($flow->getUuid()),
                $request->query->getInt('page', 1),
                50
            ),
        ];
    }

    /**
     * @Route("/teams/{team}/flows/{flow}/tides/{uuid}", name="admin_tide")
     * @ParamConverter("team", converter="team", options={"slug"="team"})
     * @ParamConverter("flow", converter="flow", options={"identifier"="flow", "flat"=true})
     * @Template
     */
    public function showAction(Team $team, Flow\Projections\FlatFlow $flow, $uuid)
    {
        $tideUuid = Uuid::fromString($uuid);
        $tide = $this->tideRepository->find($tideUuid);
        $logsUrl = sprintf(
            '%s/team/%s/%s/%s/logs',
            $this->uiUrl,
            $tide->getTeam()->getSlug(),
            (string) $tide->getFlowUuid(),
            (string) $tide->getUuid()
        );

        return [
            'team' => $team,
            'flow' => $flow,
            'tide' => $tide,
            'tideLogsUrl' => $logsUrl,
            'events' => $this->eventStore->findByTideUuidWithMetadata($tideUuid),
        ];
    }

    /**
     * @Route("/teams/{team}/flows/{flow}/tides/{uuid}/force-start", methods={"POST"}, name="admin_tide_force_start")
     * @ParamConverter("team", converter="team", options={"slug"="team"})
     * @ParamConverter("flow", converter="flow", options={"identifier"="flow", "flat"=true})
     */
    public function forceStartAction(Team $team, Flow\Projections\FlatFlow $flow, Request $request, $uuid)
    {
        $this->commandBus->handle(new StartTideCommand(Uuid::fromString($uuid)));

        $request->getSession()->getFlashBag()->add('success', 'Tide has been forced to start');

        return new RedirectResponse(
            $this->urlGenerator->generate('admin_tide', [
                'team' => $team->getSlug(),
                'flow' => (string) $flow->getUuid(),
                'uuid' => $uuid,
            ])
        );
    }

    /**
     * @Route("/teams/{team}/flows/{flow}/tides/{uuid}/cancel", methods={"POST"}, name="admin_tide_cancel")
     * @ParamConverter("team", converter="team", options={"slug"="team"})
     * @ParamConverter("flow", converter="flow", options={"identifier"="flow", "flat"=true})
     */
    public function cancelAction(Team $team, Flow\Projections\FlatFlow $flow, Request $request, $uuid)
    {
        $this->commandBus->handle(new CancelTideCommand(Uuid::fromString($uuid)));

        $request->getSession()->getFlashBag()->add('success', 'Tide has been cancelled');

        return new RedirectResponse(
            $this->urlGenerator->generate('admin_tide', [
                'team' => $team->getSlug(),
                'flow' => (string) $flow->getUuid(),
                'uuid' => $uuid,
            ])
        );
    }
}
