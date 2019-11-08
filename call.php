<?php

	include_once ('includes/api.php');
	include_once ('includes/config.php');

	$client = new ApiClient(URL, USERNAME, PASSWORD);

	// Empty File!
	file_put_contents('response.json', '');

	// Go through Articles
	$aArticles = $client->get('articles')['data'];

	foreach ($aArticles as $i => $value1)
	{
		// Get Values
		$iArticleId 		= $value1['id'];
		$aArticle 			= $client->get('articles/'. $iArticleId )['data']; // API Get Property Group
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
		$aDatesGer 		= array_column($aPropertyValues, 'value');

		// Format Dates to UNIX Date
		$aDates = [];
		foreach ($aDatesGer as $sDate) {
			$dDate = date('Y-m-d', strtotime($sDate));
			array_push($aDates, $dDate);
		}

		// Order Dates Alphabetically
		sort($aDates);


		// Print
		var_dump($sArticleName);
		var_dump($aDates);

		// file_put_contents('response.json', print_r($aPropertyGroup, true), FILE_APPEND);
		// file_put_contents('response.json', print_r($aArticle, true), FILE_APPEND);

	}

?>