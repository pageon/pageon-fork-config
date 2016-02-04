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
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

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
        $this->container->set('pageon.monolog', new Logger('pageon'));

        // catch all the errors and exceptions and pass them to monolog.
        if (!$this->isInDebugMode()) {
            new ErrorHandler($this->container->get('pageon.monolog'));
        }

        // add a monolog handler for slack using webhooks.
        $this->initSlackWebhookMonolog();
    }

    /**
     * Initialize Slack
     */
    public function initSlackWebhookMonolog()
    {
        // only activate the error handler when we aren't in debug-mode and an api key is provided
        if (!$this->isInDebugMode() && $this->container->hasParameter('pageon.slack_webhook')) {
            $slackWebhook = $this->container->getParameter('pageon.slack_webhook');
            if (empty($slackWebhook)) {
                return;
            }

            $this->container->get('pageon.monolog')->pushHandler(
                new SlackWebhookHandler(
                    new SlackConfig(
                        new Webhook(
                            $slackWebhook
                        ),
                        new User(
                            new Username(
                                $this->container->getParameter('site.domain')
                            )
                        )
                    ),
                    new MonologConfig(Logger::WARNING)
                )
            );
        }
    }

    private function isInDebugMode()
    {
        if ($this->container->hasParameter('kernel.debug')) {
            return $this->container->getParameter('kernel.debug');
        }

        return $this->container->hasParameter('fork.debug');
    }
}
