<?php 

	// dependancies
	include '../vendor/autoload.php';
	
	$client = new Google\Client();

	$client->setApplicationName("Civic_Info_Reps");
	$client->setDeveloperKey('AIzaSyClRzDqDh5MsXwnCWi0kOiiBivP6JsSyBw');

	$service = new Google_Service_CivicInfo($client);
	$representatives = $service->representatives;
	$optParams = [
		'address'=> 80202,
		'levels' => 'country',
		'roles'  => ['legislatorUpperBody', 'legislatorLowerBody']
	];
	$results = $representatives->representativeInfoByAddress($optParams);

	print_r($results['officials']);