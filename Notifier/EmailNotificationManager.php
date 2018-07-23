<?php

declare(strict_types=1);

/*
 * This file is part of the BkstgNotificationBundle package.
 * (c) Luke Bainbridge <http://www.lukebainbridge.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bkstg\NotificationBundle\Notifier;

use Bkstg\CoreBundle\User\UserInterface;
use Bkstg\NotificationBundle\BkstgNotificationBundle;
use Bkstg\NotificationBundle\Event\NotificationEntryEvent;
use Doctrine\ORM\EntityManagerInterface;
use Spy\Timeline\Model\ActionInterface;
use Spy\Timeline\Notification\NotifierInterface;
use Spy\Timeline\Spread\Entry\EntryCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class EmailNotificationManager implements NotifierInterface
{
    private $mailer;
    private $dispatcher;
    private $twig;
    private $translator;
    private $em;

    /**
     * Create a new email notification manager.
     *
     * @param \Swift_Mailer            $mailer     The mailer service.
     * @param EventDispatcherInterface $dispatcher The event dispatcher service.
     * @param Environment              $twig       The twig service.
     * @param TranslatorInterface      $translator The translator service.
     * @param EntityManagerInterface   $em         The action manager service.
     */
    public function __construct(
        \Swift_Mailer $mailer,
        EventDispatcherInterface $dispatcher,
        Environment $twig,
        TranslatorInterface $translator,
        EntityManagerInterface $em
    ) {
        $this->mailer = $mailer;
        $this->dispatcher = $dispatcher;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     *
     * @param ActionInterface $action           The action to notify.
     * @param EntryCollection $entry_collection The collection of entries to notify.
     *
     * @return void
     */
    public function notify(ActionInterface $action, EntryCollection $entry_collection): void
    {
        // Get the action subject for decisions later.
        $action_subject = $action->getComponent('subject');

        // Iterate over entry collections for each context.
        foreach ($entry_collection as $context => $entries) {
            // Iterate over entries in each collection.
            foreach ($entries as $entry) {
                // Get the entry subject.
                $entry_subject = $entry->getSubject();

                // Use a reflection class to determine if this is a user.
                try {
                    $reflection = new \ReflectionClass($entry_subject->getModel());
                    if (!$reflection->implementsInterface(UserInterface::class)) {
                        continue;
                    }
                } catch (\Exception $e) {
                    // This isn't even an object, no need to continue.
                    continue;
                }

                // Create an entry event.
                $entry_event = new NotificationEntryEvent($entry, $action);

                // Default to false if the action and entry subject match.
                if ($action_subject === $entry_subject) {
                    $entry_event->setNotify(false);
                }

                // Allow listeners to alter decision.
                $this->dispatcher->dispatch(NotificationEntryEvent::NAME, $entry_event);
                if ($entry_event->getNotify()) {
                    // If the user data is not populated bail.
                    if (null === $user = $entry_subject->getData()) {
                        if (null === $repo = $this->em->getRepository($entry_subject->getModel())) {
                            continue;
                        }
                        $user = $repo->findOneBy(['id' => $entry_subject->getIdentifier()]);
                    }

                    $message = new \Swift_Message($this->translator->trans(
                        'notification.new_notification',
                        [],
                        BkstgNotificationBundle::TRANSLATION_DOMAIN
                    ));

                    $message
                        ->setFrom('noreply@bkstg.net')
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->twig->render(
                                '@BkstgNotification/Email/_notification.html.twig',
                                ['action' => $action]
                            ),
                            'text/html'
                        )
                    ;

                    $this->mailer->send($message);
                }
            }
        }
    }
}
