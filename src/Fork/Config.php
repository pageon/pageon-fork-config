<?php
namespace Pageon\PageonForkConfig\Fork;

use Monolog\Logger;
use Pageon\PageonForkConfig\Error\Handler as ErrorHandler;
use Pageon\SlackWebhookMonolog\Monolog\Config as MonologConfig;
use Pageon\SlackWebhookMonolog\Slack\Config as SlackConfig;
use Pageon\SlackWebhookMonolog\Monolog\SlackWebhookHandler;
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
            $slackWebhook = $this->getParameter('pageon.slack_webhook');
            if (empty($slackWebhook)) {
                return;
            }

            $this->getService('pageon.monolog')->pushHandler(
                new SlackWebhookHandler(
                    new SlackConfig(
                        new Webhook(
                            $slackWebhook
                        ),
                        new User(
                            new Username(
                                $this->getParameter('site.domain')
                            )
                        )
                    ),
                    new MonologConfig(Logger::WARNING)
                )
            );
        }
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
