<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '../config.php';
require_once '../includes/db.inc';

if (!isset($_REQUEST['token']) || $_REQUEST['token'] != SLACK_TOKEN) exit;
if (!isset($_REQUEST['command']) || $_REQUEST['command'] != '/rpg') exit;
if (!isset($_REQUEST['text'])) exit;

$command = trim($_REQUEST['text']);

require_once('../vendor/autoload.php');
require_once('../src/RPGSession.php');

$session = new RPGSession ();
$session->handle($command, $_REQUEST);