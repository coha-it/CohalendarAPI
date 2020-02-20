<?php 

// Functions
function searchDrOliverHaas($arr) {
	$bFound = false;
	foreach ($arr as $key => $value) {
		$sName = $value;
		$sName = strtolower($sName);
		$sName = preg_replace('/[^A-Za-z0-9\-]/', '', $sName);

		// If Found
		if( $sName == 'droliverhaas' ) {
			$bFound = true;
			continue;
		}
	}
	return $bFound;
}

function findPropertyOption($props, $strings) {
	return array_values(
		array_filter(
			$props, 
			function($var) use ($strings) {
				return in_array(strtolower($var['name']), $strings);
			}
		)
	)[0];
}

// @ return id
function findPropertyOptionId($props, $strings) {
	return (int) findPropertyOption($props, $strings)['id'];
}

// @ return Array
function findPropertyValuesById($aProperties, $iId) {
	$arr = array_values(
		array_filter(
			$aProperties,
			function ($var) use ($iId) {
				return ($var['optionId'] == $iId);
			}
		)
	);
	return array_column($arr, 'value');
}

// @ return Array
function findPropertyValues($aPropertyValues, $aPropertyOptions, $aWords) {
	return findPropertyValuesById(
		$aPropertyValues,
		findPropertyOptionId($aPropertyOptions, $aWords)
	);
}

// @ return Array
function getArticleImages($aArticle) {
	return array_column($aArticle['images'], 'path');
}

// @ return String
function getArticleImage($aArticle) {
	$imgs = $aArticle['images'];
	$img = $imgs[0];

	return $img['path'] . "_600x600." . $img['extension'];
}

// @ return String
function getEventUrl($aArticle) {
	return 
		$aArticle['mainDetail']['attribute']['cohaAsDetailsReplaceLink'] ?? 
		PROTOCOL.SUBDOMAIN.DOMAIN.TLD.'/detail/index/sArticle/'.$aArticle['id']
	;
}

// @ writing to file!
function debugWriteArticleJson($c) {
	$file = dirname(__FILE__).'/public/dist/article.json';
	
	if( gettype($c) == "string") {
		file_put_contents($file, $c, FILE_APPEND);
	} else {
		file_put_contents($file, json_encode($c, JSON_PRETTY_PRINT).',', FILE_APPEND);
	}
}

// @ writing to file!
function writeDrOliverHaasJson($arr) {
	// If it's empty
	if(count($arr['events']) <= 2) {return;}

	// filled - so write file
	file_put_contents($arr['file'], json_encode($arr['events']));
}

function writePublicEventsJson($file, $events) {
	// Write all Events to File
	file_put_contents($file, json_encode($events));
}