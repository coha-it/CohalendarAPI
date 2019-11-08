<?php

	include_once ('./includes/api.php');
	include_once ('./includes/config.php');

	$client = new ApiClient(API_URL, USERNAME, PASSWORD);

	// Go through Articles
	$aArticles = $client->get('articles')['data'];

	$iEventCounter = -1;
	$aEvents = [];

	foreach ($aArticles as $i => $value1)
	{
		// Get Values
		$iArticleId 		= $value1['id'];
		$aArticle 		= $client->get('articles/'. $iArticleId )['data']; // API Get Property Group
		$iPropertyGroupId 	= $aArticle['propertyGroup']['id'];
		$aPropertyGroup 	= $client->get('propertyGroups/'. $iPropertyGroupId )['data'];  // API: Get Property Group
		$aPropertyOptions 	= $aPropertyGroup['options'];
		$iPropertyId		= false;

		// Find Property Option
		$aPropertyOption = array_values(
			array_filter(
				$aPropertyOptions, 
				function($var) {
					switch ( strtolower($var['name']) ) {
						case 'datum':
						case 'date':
							return true;
							break;

						default:
							return false;
							break;
					}
				}
			)
		)[0];

		// Get ID from Property Option
		$iPropertyId = (int) $aPropertyOption['id'];

		// If No Property ID found
		if(!$iPropertyId) continue;

		// Find Property Values from Article
		$aPropertyValues = array_values(
			array_filter(
				$aArticle['propertyValues'],
				function ($var) use ($iPropertyId) {
					return ($var['optionId'] == $iPropertyId);
				}
			)
		);

		// Get the Article Name and Dates
		$sArticleName 	= $aArticle['name'];
		$aAllDatesGer 	= array_column($aPropertyValues, 'value');

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
				$iEventCounter += 1;
				$aEvents[$iEventCounter] = [
					'name' => $sArticleName,
					'start' => $d1,
					'article_id' => $aArticle['id'],
					'details' => $aArticle['mainDetail']['attribute']['cohaAsShortdescContent'],
					'color' => 'primary'
				];

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

		// We Need
		// 1. Dates
		// 2. Title
		// 3. Subtitle
		// 4. URL


		// Print
		// var_dump($sArticleName);

		// Write Article and Properties to File
		// file_put_contents('../public_events.json', print_r($aPropertyGroup, true), FILE_APPEND);
		// file_put_contents('../public_events.json', print_r($aArticle, true), FILE_APPEND);
		// var_dump($aArticle);

	}

	// Sort all Events without Keys
	// $aEvents = array_values($aEvents);

	// Print All Events
	// var_dump($aEvents);

	// Write all Events to File
	$jEvents = json_encode($aEvents);

	if($jEvents) {
		file_put_contents('../public_html/vuetify_public/dist/public_events.json', json_encode($aEvents));
	}

?>
