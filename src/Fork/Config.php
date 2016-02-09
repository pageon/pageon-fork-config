<?php

namespace Pageon\PageonForkConfig\Fork;

use Monolog\Handler\Curl;
use Monolog\Logger;
use Pageon\PageonForkConfig\Error\Handler as ErrorHandler;
use Pageon\SlackWebhookMonolog\Monolog\Config as MonologConfig;
use Pageon\SlackWebhookMonolog\Slack\Channel;
use Pageon\SlackWebhookMonolog\Slack\Config as SlackConfig;
use Pageon\SlackWebhookMonolog\Monolog\SlackWebhookHandler;
use Pageon\SlackWebhookMonolog\Slack\EmojiIcon;
use Pageon\SlackWebhookMonolog\Slack\UrlIcon;
use Pageon\SlackWebhookMonolog\Slack\User;
use Pageon\SlackWebhookMonolog\Slack\Username;
use Pageon\SlackWebhookMonolog\Slack\Webhook;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Config
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * PageonForkConfig constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;

        if ($this->isInDebugMode()) {
            return;
        }

        $this->setService('pageon.monolog', new Logger('pageon'));

        // catch all the errors and exceptions and pass them to monolog.
        new ErrorHandler($this->getService('pageon.monolog'));

        // add a monolog handler for slack using webhooks.
        $this->initSlackWebhookMonolog();
    }

    /**
     * Initialize the slack webhook monolog handler.
     */
    public function initSlackWebhookMonolog()
    {
        // only activate the error handler when we aren't in debug-mode and an api key is provided
        if (!$this->isInDebugMode() && $this->hasParameter('pageon.slack_webhook')) {
            $webhook = $this->getWebhook();
            // if there is no webhook all of this is pointless.
            if ($webhook === null) {
                return;
            }

            $this->getService('pageon.monolog')->pushHandler(
                new SlackWebhookHandler(
                    new SlackConfig(
                        $webhook,
                        $this->getUser()
                    ),
                    new MonologConfig(Logger::WARNING, $this->getParameter('pageon.slack_bubble', true)),
                    new Curl\Util()
                )
            );
        }
    }

    /**
     * Get the user.
     *
     * @return User
     */
    private function getUser()
    {
        return new User(
            new Username(
                $this->getParameter('pageon.slack_username', $this->getParameter('site.domain'))
            ),
            $this->getIcon()
        );
    }

    /**
     * Get the custom icon of the correct type if there is one set.
     */
    private function getIcon()
    {
        if ($this->hasParameter('pageon.slack_icon.emoji')) {
            return new EmojiIcon($this->getParameter('pageon.slack_icon.emoji'));
        }

        if ($this->hasParameter('pageon.slack_icon.url')) {
            return new UrlIcon($this->getParameter('pageon.slack_icon.url'));
        }

        return;
    }

    /**
     * Get the webhook for slack.
     *
     * @return null|Webhook
     */
    private function getWebhook()
    {
        $slackWebhook = $this->getParameter('pageon.slack_webhook');
        if (empty($slackWebhook)) {
            return;
        }

        return new Webhook(
            $slackWebhook,
            new Channel($this->getParameter('pageon.slack_channel'))
        );
    }

    /**
     * Check if we are running in debug mode.
     *
     * @return bool
     */
    private function isInDebugMode()
    {
        return $this->getParameter('kernel.debug', $this->getParameter('fork.debug', false));
    }

    /**
     * Get the value of a parameter or the fallback if it doesn't exist.
     *
     * @param string $name
     * @param mixed|null $fallback
     *
     * @return mixed|null
     */
    private function getParameter($name, $fallback = null)
    {
        return $this->hasParameter($name) ? $this->container->getParameter($name) : $fallback;
    }

    /**
     * Check if we have access to a specific parameter.
     *
     * @param string $name
     *
     * @return bool
     */
    private function hasParameter($name)
    {
        return $this->container->hasParameter($name);
    }

    /**
     * Get a service.
     *
     * @param string $name
     *
     * @return object
     */
    private function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * Save a service for future reference.
     *
     * @param string $name
     * @param object $service
     */
    private function setService($name, $service)
    {
        $this->container->set($name, $service);
    }
}
