<?php

// Functions
function searchDrOliverHaas($arr)
{
	$bFound = false;
	foreach ($arr as $key => $value) {
		$sName = $value;
		$sName = strtolower($sName);
		$sName = preg_replace('/[^A-Za-z0-9\-]/', '', $sName);

		// If Found oliver!
		if ($sName == 'droliverhaas') {
			$bFound = true;
			continue;
		}
	}
	return $bFound;
}

function findPropertyOption($props, $strings)
{
	$prop = array_values(
		array_filter(
			$props,
			function ($var) use ($strings) {
				return in_array(strtolower($var['name']), $strings);
			}
		)
	);

	if ($prop && array_key_exists(0, $prop)) {
		return $prop[0];
	}
	return false;
}

// @ return id
function findPropertyOptionId($props, $strings)
{
	$option = findPropertyOption($props, $strings);

	if ($option && array_key_exists('id', $option)) {
		return (int) $option['id'];
	}
	return false;
}

// @ return Array
function findPropertyValuesById($aProperties, $iId)
{
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
function findPropertyValues($aPropertyValues, $aPropertyOptions, $aWords)
{
	return findPropertyValuesById(
		$aPropertyValues,
		findPropertyOptionId($aPropertyOptions, $aWords)
	);
}

// @ return Array
function getArticleImages($aArticle)
{
	return array_column($aArticle['images'], 'path');
}

// @ return String
function getArticleImage($aArticle)
{
	$imgs = $aArticle['images'];
	$img = $imgs[0];

	return $img['path'] . "_600x600." . $img['extension'];
}

// @ return String
function getEventUrl($aArticle)
{
	return
		$aArticle['mainDetail']['attribute']['cohaAsDetailsReplaceLink'] ??
		SHOP_URL . '/detail/index/sArticle/' . $aArticle['id'];
}

// @ writing to file!
function debugWriteArticleJson($c)
{
	$file = dirname(__FILE__) . '/public/dist/article.json';

	if (gettype($c) == "string") {
		file_put_contents($file, $c, FILE_APPEND);
	} else {
		file_put_contents($file, json_encode($c, JSON_PRETTY_PRINT) . ',', FILE_APPEND);
	}
}

// @ writing to file!
function writeDrOliverHaasJson($arr)
{
	// If it's empty
	if (count($arr['events']) <= 2) {
		return;
	}

	// filled - so write file
	file_put_contents($arr['file'], json_encode($arr['events']));
}

function writePublicEventsJson($file, $events)
{
	// Write all Events to File
	file_put_contents($file, json_encode($events));
}

function getMonthName($date)
{
	$monatsnamen = ["", "Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"];
	$iMonth = $date->format('n');
	$sMonth = $monatsnamen[$iMonth ?? 0];
	return mb_substr($sMonth, 0, 3);
}

function fSortByDate($a, $b)
{
	return strcmp($a["date"], $b["date"]);
}

function getExpireDate($aArticle)
{
	// Expire Date
	$sDate = $aArticle['mainDetail']['attribute']['cohaRequireSpeakerPageUntil'] ?? false;

	if ($sDate) {
		return new DateTime($sDate);
	}
	return false;
}

function articleIsPast($art)
{
	$date = new DateTime();
	return $date->getTimestamp() > $art->getTimestamp();
}

function articleIsExpired($aArticle)
{
	// Dates
	$dToday = new DateTime();
	$dDate = getExpireDate($aArticle);

	// If Expire Date exists
	if ($dDate) {
		// If Date is Set and Date is smaller than today
		return $dDate && $dDate->getTimestamp() < $dToday->getTimestamp();
	} else {
		return $aArticle['active'] == 0;
	}
}


/**
 * Check if the value is a valid date
 *
 * @param mixed $value
 *
 * @return boolean
 */
function isDate($value)
{
	if (!$value) {
		return false;
	} else {
		$date = date_parse($value);
		if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
			return checkdate($date['month'], $date['day'], $date['year']);
		} else {
			return false;
		}
	}
}
