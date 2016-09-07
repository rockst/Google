<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/path/to/google-api-php-client/src');
require_once 'Google/Client.php';
require_once 'Google/Service/Books.php';
echo 'rock';
$client = new Google_Client();
$client->setApplicationName("Client_Library_Examples");
$client->setDeveloperKey("AIzaSyDosSBxfj4zgOLqzYSSHcn8vQ0Bfj81oao");

$service = new Google_Service_Books($client);
$optParams = array('filter' => 'free-ebooks');
$results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);
foreach ($results as $item) {
echo $item['volumeInfo']['title'], "<br /> \n";
}

