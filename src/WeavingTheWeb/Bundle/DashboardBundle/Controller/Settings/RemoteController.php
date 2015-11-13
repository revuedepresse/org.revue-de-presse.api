<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\DashboardBundle\Controller\AbstractController,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Remote;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @Extra\Route(
 *      "/settings/remote",
 *      service="weaving_the_web_dashboard.controller.settings.remote"
 * )
 */
class RemoteController extends AbstractController
{
    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\RemoteRepository
     */
    public $remoteRepository;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Form\Type\SelectRemoteType
     */
    public $selectRemoteType;

    /**
     * @param Request $request
     * @return array
     *
     * @Extra\Route(
     *      "/",
     *      name="weaving_the_web_dashboard_settings_show_remote_settings"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:Remote/Add/_block.html.twig")
     */
    public function showRemoteSettingsAction(Request $request)
    {
        $remoteResponse = $this->addRemoteAction($request);
        if ($remoteResponse instanceof RedirectResponse) {
            return $remoteResponse;
        }

        $remoteSelectionResponse = $this->selectRemoteAction($request);
        if ($remoteSelectionResponse instanceof RedirectResponse) {
            return $remoteSelectionResponse;
        }

        return array_merge(
            $remoteResponse,
            $remoteSelectionResponse,
            ['active_menu_item' => 'remote_settings']
        );
    }

    /**
     * @param Request $request
     * @return array
     *
     * @Extra\Route(
     *      "/add",
     *      name="weaving_the_web_dashboard_settings_add_remote"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:Remote/Add/_block.html.twig")
     */
    public function addRemoteAction(Request $request)
    {
        $currentRoute = $this->getCurrentRoute();
        $form = $this->formFactory->create('add_remote', null, ['action' => $currentRoute]);

        if ($request->isMethod('POST')) {
            if ($this->isFormSubmitted($form, $request)) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    /** @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\RemoteRepository $remoteRepository  */
                    $remoteRepository = $this->entityManager->getRepository('WeavingTheWebDashboardBundle:Remote');
                    /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Remote $remote  */
                    $remote = $remoteRepository->make($data['remote'], $data['access_token']);
                    $remote->setUser($this->getUser());

                    $this->entityManager->persist($remote);
                    $this->entityManager->flush();

                    $flashMessages = [$this->translator->trans('remote.add_remote.success', [], 'remote')];
                    $this->addFlashMessages($flashMessages, 'add_remote_info');

                    return new RedirectResponse($currentRoute);
                } else {
                    $this->handleFormErrors($form, 'add_remote', 'remote');
                }
            }
        }

        return ['add_remote_form' => $form->createView()];
    }

    /**
     * @param Request $request
     * @return array
     *
     * @Extra\Route(
            "/select",
     *      name="weaving_the_web_dashboard_settings_select_remote"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:Remote/Select/_form.html.twig")
     */
    public function selectRemoteAction(Request $request)
    {
        $currentRoute = $this->getCurrentRoute();

        /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Remote $remote */
        $remote = $this->remoteRepository->findOneby(['selected' => true, 'user' => $this->getUser()]);

        $data = null;
        if (!is_null($remote)) {
            $data = ['remotes' => $remote];
        }
        $this->selectRemoteType->setUser($this->getUser());
        $form = $this->formFactory->create(
            $this->selectRemoteType,
            $data,
            [
                'action' => $currentRoute,
            ]
        );

        if ($this->isFormSubmitted($form, $request)) {
            if ($form->isValid()) {
                /** @var \WeavingTheWeb\Bundle\DashboardBundle\Entity\Remote $submittedRemote */
                $submittedRemote = $form->get('remotes')->getData();

                // Unselect the previously selected remote
                if (!is_null($data)) {
                    $remote->unselect();
                    $this->updateRemoteSelection($remote);
                }

                if (is_null($submittedRemote)) {
                    $messageKey = 'empty_selection';
                } else {
                    $submittedRemote->select();
                    $this->updateRemoteSelection($submittedRemote);

                    $messageKey = 'success';
                }

                $successMessage = $this->translator->trans('remote.select_remote.' . $messageKey, [], 'remote');
                $this->addFlashMessages([$successMessage], 'select_remote_info');

                return new RedirectResponse($currentRoute);
            } else {
                $this->handleFormErrors($form, 'select_remote');
            }
        }

        return ['select_remote_form' => $form->createView()];
    }

    /**
     * @param Remote $remote
     */
    protected function updateRemoteSelection(Remote $remote)
    {
        $this->entityManager->persist($remote);
        $this->entityManager->flush();
    }
}
