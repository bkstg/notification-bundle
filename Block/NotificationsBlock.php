<?php

declare(strict_types=1);

/*
 * This file is part of the BkstgNotificationBundle package.
 * (c) Luke Bainbridge <http://www.lukebainbridge.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bkstg\NotificationBundle\Block;

use Bkstg\NotificationBundle\Notifier\ApplicationNotificationManager;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Templating\TwigEngine;
use Spy\Timeline\Driver\ActionManagerInterface;
use Spy\Timeline\Driver\TimelineManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class NotificationsBlock extends AbstractBlockService
{
    private $action_manager;
    private $timeline_manager;
    private $token_storage;
    private $notifier;

    /**
     * Create a new notifications block.
     *
     * @param string                         $name             The name of the block.
     * @param TwigEngine                     $templating       The twig service.
     * @param ActionManagerInterface         $action_manager   The action manager service.
     * @param TimelineManagerInterface       $timeline_manager The timeline manager service.
     * @param TokenStorageInterface          $token_storage    The token storage service.
     * @param ApplicationNotificationManager $notifier         The notifier service.
     */
    public function __construct(
        $name,
        TwigEngine $templating,
        ActionManagerInterface $action_manager,
        TimelineManagerInterface $timeline_manager,
        TokenStorageInterface $token_storage,
        ApplicationNotificationManager $notifier
    ) {
        $this->action_manager = $action_manager;
        $this->timeline_manager = $timeline_manager;
        $this->token_storage = $token_storage;
        $this->notifier = $notifier;
        parent::__construct($name, $templating);
    }

    /**
     * Execute the block.
     *
     * @param  BlockContextInterface $context  The block context.
     * @param  Response              $response The response so far.
     * @return string
     */
    public function execute(BlockContextInterface $context, Response $response = null)
    {
        $user = $this->token_storage->getToken()->getUser();
        $user_component = $this->action_manager->findOrCreateComponent($user);
        $timeline = $this->notifier->getUnreadNotifications(
            $user_component,
            'GLOBAL',
            ['paginate' => false, 'max_per_page' => 5]
        );

        return $this->renderResponse($context->getTemplate(), [
            'block' => $context->getBlock(),
            'settings' => $context->getSettings(),
            'timeline' => $timeline,
            'count' => $this->notifier->countKeys($user_component),
        ], $response);
    }

    /**
     * Configure the settings for this block.
     *
     * @param OptionsResolver $resolver The options resolver services.
     *
     * @return void
     */
    public function configureSettings(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'template' => '@BkstgNotification/Block/_notifications.html.twig',
        ]);
    }
}
