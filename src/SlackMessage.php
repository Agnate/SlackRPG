<?php

class SlackMessage {

  public $url;
  public $username;
  public $icon;

  function __construct ($url, $username, $icon) {
    $this->url = $url;
    $this->username = $username;
    $this->icon = $icon;
  }

  /**
   * $fields:
   *    'payload' -> Contains the text to show the user.
   *    'channel' -> The channel to send it to. Defaults to slack-hook channel. Use @username to send privately.
   */
  public function send ($text, $channel = NULL) {
    $payload = compact('text');
    $payload['username'] = $this->username;
    $payload['icon_emoji'] = $this->icon;
    $payload['as_user'] = true;
    if (!empty($channel)) $payload['channel'] = $channel;

    $fields = array(
      'payload' => json_encode($payload),
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $server_output = curl_exec ($ch);
    curl_close ($ch);
  }
}