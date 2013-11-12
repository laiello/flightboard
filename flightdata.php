<?php

/********** CONFIGURATION ************/

/***
 *
 * The applicaiton ID and key required below can be generated for free at 
 * https://developer.flightstats.com/signup
 * 
***/

$application_id 	= '8a41f691';
$application_key 	= '7404f9c7a2b7acf781872c6f70d39deb';


/***
 * airport_code
 * this is the Airport's IATA Code for which you want to load data
 ***/
$airport_code 	= 'SFO';	

/***
 * flight_type (either "arr" or "dep")
 ***/
$flight_type 	= 'dep';	

/***
 * minutes_behind (cannot be more than 360 minutes)
 * #departures - the number of minutes after departure that a flight should remain on the board
 * #arrivals - the number of minutes after arrival that a flight should remain on the board
 ***/
$minutes_behind = '30';

/***
 * num_hours (cannot be more than 5 hours)
 * #departures - the number of hours prior to departure that a flight should appear on the board
 * #arrivals - the number of hours prior to arrival that a flight should appear on the board
 ***/
$num_hours 		= '1';

/***
 * max_flights (can be left blank/NULL)
 * the maximum number of flights to be displayed
 ***/
$max_flights 	= '';


/******** END CONFIGURATION **********/

date_default_timezone_set('UTC'); // set timezone to UTC for server-independent time calculations

// configuration option validation
if($airport_code == '')
{
	echo 'You must specify the airport code.';
	exit;
}

if($flight_type == '')
{
	echo 'You must specify the flight type (arr or dep).';
	exit;
}

if($minutes_behind == '')
{
	$minutes_behind = 30;
}elseif($minutes_behind > 360)
{
	$minutes_behind = 360;
}

if($num_hours == '')
{
	$num_hours = 1;
}elseif($num_hours > 5)
{
	$num_hours = 5;
}

// calculate hour_start
$hour_start = strtotime("- ".$minutes_behind."minutes");
$hour_start = date("H",$hour_start);

// prepare url
$params = array($airport_code,$flight_type,date("Y"),date("m"),date("d"),$hour_start);
$target = 'https://api.flightstats.com/flex/flightstatus/rest/v2/json/airport/status/'.implode('/',$params).'?appId='.$application_id.'&appKey='.$application_key.'&utc=true&numHours='.$num_hours;
if($max_flights != '' && $max_flights != NULL)
{
	$target .= '&maxFlights=5';
}

$result = api_request($target);
if($result)
{
	$data = json_decode($result);
	if($data->error->httpStatusCode != 200)
	{
		echo $data->error->errorMessage.' ('.$data->error->errorId.')';
		exit;
	}

	print_r($data);
}else{
	echo 'An error occured retrieving flight data.';
	exit;
}

function api_request($target)
{
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_URL,$target);
	curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec($curl_handle);
	curl_close($curl_handle);

	if(empty($response))
	{
		return FALSE;
	}else{
		return $response;
	}
}
?>
