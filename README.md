# Pageon specific Fork stuff

Fork CMS is great, we use it all the time for client-projects. But we also
like our internal tools.

So this codebase will tie in our internal tools into Fork CMS for the sites we
create for our clients.

## 1: SlackWebhookMonolog

Instead of the default error handeling in Fork errors will be send to our slack channel so our dev's will be notified.

We will also display a nice error message for the customers.

### 1.1 Minimal configuration

For this to work we need to have at least the following parameters

* ```pageon.slack_webhook```: This contains the url to the webhook

if ```fork.debug``` or ```kernel.debug``` is true no notifications will be send.

### 1.2 Optional configuration

It is possible to tweak the configuration with extra parameters.

* ```pageon.slack_bubble``` [true]: If you add this to the parameters with the value false the errors won`t bubble to the next monolog handler that is listening.
* ```pageon.slack_username``` [```site.domain```]: You can choose a custom username instead of the default.
* ```pageon.slack_channel```: Override the channel where the webhook should post the notifications.
* ```pageon.slack_icon.emoji```: Use an emoji as the icon for the notifications instead of the default. This can not be combined with ```pageon.slack_icon.url```.
* ```pageon.slack_icon.url```: Use an image url as the icon for the notifications instead of the default. This can not be combined with ```pageon.slack_icon.emoji```.
