<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Request;

use WeavingTheWeb\Bundle\DashboardBundle\Controller\AbstractController;

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
     * @param Request $request
     * @return array
     *
     * @Extra\Route(
     *      "/",
     *      name="weaving_the_web_dashboard_settings_add_remote"
     * )
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template("WeavingTheWebDashboardBundle:Settings:Remote/_form.html.twig")
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

        return [
            'active_menu_item' => 'remote_setting',
            'add_remote_form' => $form->createView()];
    }

}
