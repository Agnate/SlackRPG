<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('../../config.php');
require_once('../../vendor/autoload.php');

use \Kint;

// d($_POST);

// Create an array of data to pass to respond. Only these keys will be grabbed from $_POST.
$data = array(
  'token' => '',
  'forced_debug_mode' => '',
  'command' => '',
  'user_name' => '',
  'user_id' => '',
  'text' => '',
);

// Get the information from the POST.
foreach ($_POST as $key => $value) {
  // Skip any POST data whose key is not in $data.
  if (!isset($data[$key])) continue;
  $data[$key] = $value;
}

// POST to respond.php
$ch = curl_init(RPG_SERVER_PUBLIC_URL.'/debug/respond.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Get the response.
$response = curl_exec($ch);

// Close the curl connection.
curl_close($ch);

// d($response);
// d(curl_getinfo($ch, CURLINFO_HTTP_CODE));
// d(curl_error($ch));

print $response;