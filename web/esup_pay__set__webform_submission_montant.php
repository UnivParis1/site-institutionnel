<?php

$IP = $_SERVER['REMOTE_ADDR'];
if ($IP !== "172.20.0.88" && $IP !== '2001:660:3305:f0::88') {
    fail(403, "IP $IP not allowed");
}
if (!isset($_GET['pbx_total'])) fail(400, 'missing "pbx_total" query param');
if (!isset($_GET['sid'])) fail(400, 'missing "sid" query param');

require "./sites/default/settings.php";
set_pbx_total($databases['default']['default'], $_GET['sid'], $_GET['pbx_total']);

echo_response_and_close_request("OK");

tell_comptex_fc($_GET['sid']);


function set_pbx_total($conf, $sid, $pbx_total) {
    $mysqli = new mysqli($conf['host'], $conf['username'], $conf['password'], $conf['database']);
    if ($mysqli->connect_errno) {
        fail(500, $mysqli->connect_error);
    }
    $query = "update ${conf['prefix']}webform_submission_data set value = $pbx_total where sid = $sid and name = 'pbx_total'";
    $mysqli->query($query);
    if ($mysqli->errno) {
        fail(400, $mysqli->error);
    }
    error_log("no error with $sid $pbx_total");
}

function tell_comptex_fc($sid) {
  error_log("telling comptex/fc");
  $ch = curl_init("https://comptex.univ-paris1.fr/fc/api/comptes/new/drupal_webform_create?drupal_sid=$sid");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  $response = curl_exec($ch);
  error_log($response);
}

function fail($status, $msg) {
  error_log($msg);
  echo($msg);
  header('HTTP/1.0 ' . $status);
  exit();
}

// https://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response
function echo_response_and_close_request($response) {
  set_time_limit(0);

  ob_start();
  echo $response;
  header('Connection: close');
  header('Content-Length: '.ob_get_length());
  ob_end_flush();
  @ob_flush();
  flush();
}
