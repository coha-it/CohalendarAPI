<?php

include_once ('includes/api.php');
include_once ('includes/config.php');
include_once ('includes/functions.php');

$client = new ApiClient(API_URL, USERNAME, PASSWORD);
$file = dirname(__FILE__).'/public/dist/public_events.json';
$iEventCounter = 0;
$aEvents = [];

// Go through Articles
foreach ($client->get('articles')['data'] as $i => $value1)
{
	// Get Values
	$iArticleId 				= $value1['id'];
	$aArticle 					= $client->get('articles/'. $iArticleId )['data']; // API Get Property Group
	$aPropertyValues 		= $aArticle['propertyValues'];
	$iPropertyGroupId 	= $aArticle['propertyGroup']['id'];
	$aPropertyGroup 		= $client->get('propertyGroups/'. $iPropertyGroupId )['data'];  // API: Get Property Group
	$aPropertyOptions 	= $aPropertyGroup['options'];

	if($aArticle['active'] == 0) continue; // If Deactivated

	$iPropertyDateId = findPropertyOptionId($aPropertyOptions, ['datum', 'date']) ?? false; // Get ID from Property Option

	if(!$iPropertyDateId) continue; // If No Property ID found

	$aPropertyDateValues = findPropertyValuesById($aPropertyValues, $iPropertyDateId); // Find Property Values from Article

	
	$aAllDatesGer = array_column($aPropertyValues, 'value'); // Get the Article Name and Dates

	// Format Dates to UNIX Date
	$aAllDates = [];
	foreach ($aAllDatesGer as $sDate) {
		$dDate = date('Y-m-d', strtotime($sDate));
		array_push($aAllDates, $dDate); 
	}

	// Order Dates Alphabetically
	sort($aAllDates);

	// Go Through all Dates
	$iSearch = 0;

	for ($i = 0; $i < count($aAllDates); $i++ )
	{
		$d1 = $aAllDates[$i];

		// You are not a searched entry
		if($iSearch == 0) {
			$aEvents[$iEventCounter] = [
				'name' => $sArticleName,
				'start' => $d1,
				'article_id' => $aArticle['id'],
				'details' => $aArticle['mainDetail']['attribute']['cohaAsShortdescContent'],
				'color' => 'primary'
			];
			$iEventCounter += 1;

			// the next date exists! 
			if(array_key_exists( $i+1 , $aAllDates)) {
				$d2 = $aAllDates[$i+1];
				$diff = strtotime($d2) - strtotime($d1);
				$days = round($diff / (60 * 60 * 24));

				// it's small enough so it will be catched?
				if($days < 4) {
					// now start searching in the next loop
					$iSearch += 1;
				}
			}
		}

		// You are a searched entry!
		else {
			// echo $i ." => ". $aAllDates[$i]."\n";

			// Is the next one existing and bigger?
			if(array_key_exists( $i+1 , $aAllDates)) {
				$d2 = $aAllDates[$i+1];
				$diff = strtotime($d2) - strtotime($d1);
				$days = round($diff / (60 * 60 * 24));

				// And its too big?
				if($days >= 4) {
					$aEvents[$iEventCounter]['end'] = $d1;
					$iSearch = 0;
				}

				// it's small enough to be catched?
				else {
					$iSearch += 1;
				}
			}
		}
	}
}

writePublicEventsJson($file, $aEvents);
