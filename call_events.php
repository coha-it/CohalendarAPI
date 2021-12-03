<?php

include_once('includes/api.php');
include_once('includes/config.php');
include_once('includes/functions.php');

$client = new ApiClient(API_URL, USERNAME, PASSWORD);
$file = dirname(__FILE__) . '/public/dist/public_events.json';
$iEvent = 0;
$aEvents = [];


// Go through Articles
foreach ($client->get('articles')['data'] as $i => $value1) {
	// Get Values
	$iArticleId 				= $value1['id'];
	$aArticle 					= $client->get('articles/' . $iArticleId)['data']; // API Get Property Group

	if ($aArticle['active'] === 0) continue; // If Deactivated ignore the rest

	// Set Default-Dates
	$date1 = $aArticle['mainDetail']['attribute']['cohaEventDate'];
	$date2 = $aArticle['mainDetail']['releaseDate'];
	$aAllDatesGer = [$date1 ? $date1 : $date2];

	// Search for Property Dates
	$aPropertyGroup = $aArticle['propertyGroup'];
	if ($aPropertyGroup) {
		$aPropertyGroup 		= $client->get('propertyGroups/' . $aArticle['propertyGroup']['id'])['data'];  // API: Get Property Group
		$aPropertyOptions 	= array_key_exists('options', $aPropertyGroup) ? $aPropertyGroup['options'] : [];
		$iPropertyDateId 		= findPropertyOptionId($aPropertyOptions, ['datum', 'date']) ?? false; // Get ID from Property Option

		if ($iPropertyDateId) {
			$aPropertyValues = $aArticle['propertyValues'];
			$aPropertyDateValues = findPropertyValuesById($aPropertyValues, $iPropertyDateId); // Find Property Values from Article

			// Format Dates to UNIX Date
			$aAllDatesGer = array_column($aPropertyValues, 'value'); // Get the Article Name and Dates
		}
	}

	$aAllDates = [];
	foreach ($aAllDatesGer as $sDate) {
		if (isDate($sDate)) {
			$dDate = date('Y-m-d', strtotime($sDate));
			array_push($aAllDates, $dDate);
		}
	}

	// Order Dates Alphabetically
	sort($aAllDates);

	// Go Through all Dates
	$iSearch = 0;

	for ($i = 0; $i < count($aAllDates); $i++) {
		$d1 = $aAllDates[$i];

		// You are not a searched entry
		if ($iSearch == 0) {
			$aEvents[$iEvent] = [
				'name' => $aArticle['name'],
				'start' => $d1,
				'article_id' => $aArticle['id'],
				'details' => $aArticle['mainDetail']['attribute']['cohaAsShortdescContent'],
				'categories' => $aArticle['categories'],
				'seoCategories' => $aArticle['seoCategories'],
				'description' => $aArticle['description'],
				'keywords' => $aArticle['keywords'],
				'metaTitle' => $aArticle['metaTitle'],
			];
			$aEvents[$iEvent] = array_filter($aEvents[$iEvent]);

			$iEvent += 1;

			// the next date exists!
			if (array_key_exists($i + 1, $aAllDates)) {
				$d2 = $aAllDates[$i + 1];
				$diff = strtotime($d2) - strtotime($d1);
				$days = round($diff / (60 * 60 * 24));

				// it's small enough so it will be catched?
				if ($days < 4) {
					// now start searching in the next loop
					$iSearch += 1;
				}
			}
		}

		// You are a searched entry!
		else {
			// echo $i ." => ". $aAllDates[$i]."\n";

			// Is the next one existing and bigger?
			if (array_key_exists($i + 1, $aAllDates)) {
				$d2 = $aAllDates[$i + 1];
				$diff = strtotime($d2) - strtotime($d1);
				$days = round($diff / (60 * 60 * 24));

				// And its too big?
				if ($days >= 4) {
					$aEvents[$iEvent]['end'] = $d1;
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

if (count($aEvents) > 1) {
	writePublicEventsJson($file, $aEvents);
}
