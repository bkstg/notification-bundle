<?php

declare(strict_types=1);

/*
 * This file is part of the BkstgNotificationBundle package.
 * (c) Luke Bainbridge <http://www.lukebainbridge.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bkstg\NotificationBundle\Event;

use Spy\Timeline\Model\ActionInterface;
use Spy\Timeline\Spread\Entry\EntryInterface;
use Symfony\Component\EventDispatcher\Event;

class NotificationEntryEvent extends Event
{
    const NAME = 'bkstg.timeline.notification_entry';

    protected $entry;
    protected $action;
    protected $notify = true;

    /**
     * Create a new notification entry event.
     *
     * @param EntryInterface  $entry  The entry.
     * @param ActionInterface $action The action.
     */
    public function __construct(EntryInterface $entry, ActionInterface $action)
    {
        $this->entry = $entry;
        $this->action = $action;
    }

    /**
     * Returns the entry for this event.
     *
     * @return EntryInterface
     */
    public function getEntry(): EntryInterface
    {
        return $this->entry;
    }

    /**
     * Return the action for this event.
     *
     * @return ActionInterface
     */
    public function getAction(): ActionInterface
    {
        return $this->action;
    }

    /**
     * Return whether or not to notify of this action.
     *
     * @return bool
     */
    public function getNotify(): bool
    {
        return $this->notify;
    }

    /**
     * Set whether or not to notify for this action.
     *
     * @param bool $notify Whether or not to notify.
     *
     * @return void
     */
    public function setNotify(bool $notify): void
    {
        $this->notify = $notify;
    }
}
