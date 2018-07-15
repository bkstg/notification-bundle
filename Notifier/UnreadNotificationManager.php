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
use Bkstg\NotificationBundle\Event\NotificationEntryEvent;
use Spy\Timeline\Driver\TimelineManagerInterface;
use Spy\Timeline\Model\ActionInterface;
use Spy\Timeline\Notification\Unread\UnreadNotificationManager as BaseUnreadNotificationManager;
use Spy\Timeline\Spread\Entry\EntryCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class UnreadNotificationManager extends BaseUnreadNotificationManager
{
    protected $dispatcher;
    protected $timeline_manager;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        TimelineManagerInterface $timeline_manager
    ) {
        $this->dispatcher = $dispatcher;
        parent::__construct($timeline_manager);
    }

    /**
     * {@inheritdoc}
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
                    // Create a new timeline action for this notification.
                    $this->timelineManager->createAndPersist(
                        $action,
                        $entry->getSubject(),
                        $context,
                        'notification'
                    );
                }
            }
        }

        // Flush the timeline maanager to persist notifications.
        $this->timelineManager->flush();
    }
}
