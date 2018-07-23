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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NotificationController extends Controller
{
    public function redirectAction(
        int $id,
        TokenStorageInterface $token_storage,
        ActionManagerInterface $action_manager,
        ApplicationNotificationManager $notifier,
        LinkGeneratorInterface $generator,
        Request $request
    ): Response {
        // Get the action.
        $repo = $this->em->getRepository(Action::class);
        if (null === $action = $repo->findOneBy(['id' => $id])) {
            throw new NotFoundHttpException();
        }

        $user = $token_storage->getToken()->getUser();
        $subject = $action_manager->findOrCreateComponent($user);
        $notifier->markAsReadAction($subject, $id);

        return new RedirectResponse($generator->generate($action));
    }

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
