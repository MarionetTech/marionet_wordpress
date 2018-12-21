<?php

/* Marionet Event client */

class MarionetClient {
  private $debug = false;
  private $version = '0.1.0';
  private $tracker_name = 'php-tracker';
  private $endpoint = 'http://tracker.marionet.tech/event';
  private $app_key;
  private $uid = null;
  private $visitor = array();
  private $access_key = null;

  public function __construct($app_key) {
    $this->app_key = (string) $app_key;
  }

  public function set_uid($uid) {
    $this->uid = (string) $uid;
  }

  public function debug($value) {
    $this->debug = (bool) $value;
  }

  public function set_endpoint($endpoint) {
    $this->endpoint = (string) $endpoint;
  }

  public function set_visitor($visitor) {
    foreach ($visitor as $key=>$value) $this->visitor[$key] = $value;
    return $this->visitor;
  }

  public function set_access_key($value) {
    $this->access_key = (string) $value;
  }

  public function track($event_name, $properties = []) {
    $this->send($event_name, $properties, $data, $context, $truePerformedAt);
  }

  private function send($event_name, $properties, $data, $context, $truePerformedAt) {
    if (!isset($this->app_key)) return false;

    // Allow send orderUpdate event without uid
    if (!isset($this->uid) || ($event_name == 'orderUpdate' && !empty($properties['number']))) return false;

    $data = $data ? $data : array();
    $data['appKey'] = $this->app_key;
    $data['name'] = $event_name;
    $data['performedAt'] = time();
    $data['uuid'] = $uid;
    $data['clientVersion'] = $this->version;
    $data['clienType'] = 'php-tracker';
    $data['visitor'] = $this->visitor;
    $data['properties'] = $properties;

    if ($this->access_key) $data['accessKey'] = $this->access_key;

    $this->put_log($data);
    $post = http_build_query($data);
    $opts = stream_context_create(array(
      'http' => array(
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post
      )
    ));
    return file_get_contents("{$this->endpoint}/event", false, $opts);
  }

  private function put_log($post) {
    if (!$this->debug) return true;
    ob_start();
    print_r($post);
    $str_post = ob_get_clean();
    $date = date('Y.m.d H:i:s');
    $row = "{$date}\n{$str_post}\n";
    $filename = dirname(__FILE__) . "/events.log";
    return file_put_contents($filename, $row, FILE_APPEND);
  }
}
