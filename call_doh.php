<?php

include_once ('includes/api.php');
include_once ('includes/config.php');
include_once ('includes/functions.php');

$client = new ApiClient(API_URL, USERNAME, PASSWORD);

$aDrOliverHaas = [
	'file' => realpath(dirname(__FILE__)).'/public/dist/dr_oliver_haas_events.json',
	'events' => []
];

// Go through Articles
foreach ($client->get('articles')['data'] as $i => $value1)
{
	// Get Values
	$iArticleId 				= $value1['id'];
	$aArticle 					= $client->get('articles/'. $iArticleId )['data']; // API Get Property Group
	$aPropertyValues 		= $aArticle['propertyValues'];
	$iPropertyGroupId 	= $aArticle['propertyGroup']['id'];
	$aPropertyGroup 		= $client->get('propertyGroups/'. $iPropertyGroupId )['data'];  // API: Get Property Group
	$aPropertyOptions 	= array_key_exists('options', $aPropertyGroup) ? $aPropertyGroup['options'] : [];


	$aImages 						= array_key_exists('images', $aArticle) ? $aArticle['images'] : false;
	$aImage 						= $aImages ? $client->get('media/'. $aImages[0]['mediaId'] )['data'] : false;

	// If Deactivated
	if($aArticle['active'] == 0) continue;

	// Find Property ID from "Datum" and from "Vortragender"
	$iPropertyDateId = findPropertyOptionId($aPropertyOptions, ['datum', 'date']) ?? false;
	$iPropertyOrgId  = findPropertyOptionId($aPropertyOptions, ['vortragende(r)', 'vortragende*r', 'vortragender', 'vortragende']) ?? false;

	if( // If No Property ID found 
		(!$iPropertyDateId) || // If no "Datum" jump to next article
		(!$iPropertyOrgId) || // If no "Veranstalter" jump to next article
		(!searchDrOliverHaas( findPropertyValuesById($aPropertyValues, $iPropertyOrgId) )) // Search for Dr. Oliver Haas. If not found: get along
	) continue;

	
	// Dates
	setlocale(LC_TIME, "de_DE");
	$sDate = $aArticle['mainDetail']['attribute']['cohaEventDate'];
	$date = new DateTime($sDate);

	// Create New Article
	$aEvent = [
		'id' 							=> $aArticle['id'], // ID
		'name' 						=> $aArticle['name'], // 1 Name
		'desc' 						=> $aArticle['description'], // 2 ShortDesc
		'address' 				=> join(findPropertyValues($aPropertyValues, $aPropertyOptions, ['adresse']), ', '), // 3 Ort - Adresse 
		'place' 					=> join(findPropertyValues($aPropertyValues, $aPropertyOptions, ['ort']), ', '), // (3.1 Ort-Daten!)
		'img_name' 				=> $aImage ? $aImage['name'] : '', // 4 Image(s)
		'img_url' 				=> $aImage ? $aImage['path'] : '', // 4 Image(s)
		'date' 						=> $sDate, // 5 Startet AM
		'd_y' 						=> $date->format('Y'), // Year
		'd_m' 						=> getMonthName($date), // Mont
		'd_d' 						=> $date->format('d'), // Day
		'art_url' 				=> getEventUrl($aArticle), // 5.1 Event-Url (replacing-URL) or normal URL for Article
	];

	$aDrOliverHaas['events'][] = $aEvent;
}


function getMonthName($date) {
	$monatsnamen = ["", "Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"];
	$iMonth = $date->format('n');
	$sMonth = $monatsnamen[$iMonth ?? 0];
	return substr($sMonth, 0, 3);
}

// Write all Events to File (Only if its big enoug )
writeDrOliverHaasJson($aDrOliverHaas);
