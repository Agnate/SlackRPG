<?php
require_once('config.php');
require_once('includes/db.inc');
require(RPG_SERVER_ROOT.'/vendor/autoload.php');
require_once(RPG_SERVER_ROOT.'/src/autoload.php');

use Frlnc\Slack\Http\SlackResponseFactory;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Core\Commander;

$interactor = new CurlInteractor;
$interactor->setResponseFactory(new SlackResponseFactory);

$commander = new Commander(SLACK_BOT_TOKEN, $interactor);

/*$response = $commander->execute('chat.postMessage', array(
  'channel' => SLACK_BOT_PUBLIC_CHANNEL,
  'username' => SLACK_BOT_USERNAME,
  'as_user' => true,
  'text' => 'Hello, world!',
));*/

$response = $commander->execute('im.list', array(
  
));

echo var_export($response->toArray(), true)."\n";