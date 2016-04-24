<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

function getClient() {
  $client = new Google_Client();
  $client->setAuthConfigFile(__DIR__ . '/client_secret.json');
  $client->setRedirectUri('http://quickslots.com:4000/oauth2callback.php');
  $client->addScope(Google_Service_Calendar::CALENDAR);
  return $client;
}

$client = getClient();

if (! isset($_GET['code'])) {
  $auth_url = $client->createAuthUrl();
  header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect_uri = 'http://quickslots.com:4000/event.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
 }
