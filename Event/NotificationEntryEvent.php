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

    public function __construct(EntryInterface $entry, ActionInterface $action)
    {
        $this->entry = $entry;
        $this->action = $action;
    }

    public function getEntry()
    {
        return $this->entry;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getNotify()
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): void
    {
        $this->notify = $notify;
    }
}
