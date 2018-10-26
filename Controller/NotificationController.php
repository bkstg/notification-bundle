<?php

declare(strict_types=1);

/*
 * This file is part of the BkstgNotificationBundle package.
 * (c) Luke Bainbridge <http://www.lukebainbridge.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bkstg\NotificationBundle\Controller;

use Bkstg\CoreBundle\Controller\Controller;
use Bkstg\NotificationBundle\Notifier\ApplicationNotificationManager;
use Bkstg\TimelineBundle\BkstgTimelineBundle;
use Bkstg\TimelineBundle\Entity\Action;
use Bkstg\TimelineBundle\Generator\LinkGeneratorInterface;
use Spy\Timeline\Driver\ActionManagerInterface;
use Spy\Timeline\Filter\DataHydrator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NotificationController extends Controller
{
    /**
     * Redirect to the action link.
     *
     * @param int                            $id             The action id.
     * @param TokenStorageInterface          $token_storage  The token storage service.
     * @param ActionManagerInterface         $action_manager The action manager service.
     * @param ApplicationNotificationManager $notifier       The notification manager service.
     * @param LinkGeneratorInterface         $generator      The link generator service.
     * @param DataHydrator                   $hydrator       The data hydrator service.
     * @param Request                        $request        The incoming request.
     *
     * @return Response
     */
    public function redirectAction(
        int $id,
        TokenStorageInterface $token_storage,
        ActionManagerInterface $action_manager,
        ApplicationNotificationManager $notifier,
        LinkGeneratorInterface $generator,
        DataHydrator $hydrator,
        Request $request
    ): Response {
        // Get the action.
        $repo = $this->em->getRepository(Action::class);
        if (null === $action = $repo->findOneBy(['id' => $id])) {
            throw new NotFoundHttpException();
        }

        // Hydrate the components.
        $hydrator->filter([$action]);

        $user = $token_storage->getToken()->getUser();
        $subject = $action_manager->findOrCreateComponent($user);
        $notifier->markAsReadAction($subject, $id);

        return new RedirectResponse($generator->generateLink($action));
    }

    /**
     * Mark an action as read.
     *
     * @param int                            $id             The id of the action.
     * @param TokenStorageInterface          $token_storage  The token storage service.
     * @param ActionManagerInterface         $action_manager The action manager service.
     * @param ApplicationNotificationManager $notifier       The notifier service.
     * @param Request                        $request        The incoming request.
     *
     * @return Response
     */
    public function markReadAction(
        int $id,
        TokenStorageInterface $token_storage,
        ActionManagerInterface $action_manager,
        ApplicationNotificationManager $notifier,
        Request $request
    ): Response {
        $user = $token_storage->getToken()->getUser();
        $subject = $action_manager->findOrCreateComponent($user);
        $notifier->markAsReadAction($subject, $id);

        return new RedirectResponse($request->server->get('HTTP_REFERER'));
    }

    /**
     * Mark all actions as read for the current user.
     *
     * @param TokenStorageInterface          $token_storage  The token storage service.
     * @param ActionManagerInterface         $action_manager The action manager service.
     * @param ApplicationNotificationManager $notifier       The notifier service.
     * @param Request                        $request        The incoming request.
     *
     * @return Response
     */
    public function markAllReadAction(
        TokenStorageInterface $token_storage,
        ActionManagerInterface $action_manager,
        ApplicationNotificationManager $notifier,
        Request $request
    ): Response {
        $user = $token_storage->getToken()->getUser();
        $subject = $action_manager->findOrCreateComponent($user);
        $notifier->markAllAsRead($subject);

        $this->session->getFlashBag()->add(
            'success',
            $this->translator->trans('notifications.cleared', [], BkstgTimelineBundle::TRANSLATION_DOMAIN)
        );

        return new RedirectResponse($request->server->get('HTTP_REFERER'));
    }
}
