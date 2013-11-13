<?php

/********** CONFIGURATION ************/

/***
 *
 * The applicaiton ID and key required below can be generated for free at 
 * https://developer.flightstats.com/signup
 * 
***/

$application_id 	= '';
$application_key 	= '';


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
	if(isset($data->error->httpStatusCode) && $data->error->httpStatusCode != 200)
	{
		echo $data->error->errorMessage.' ('.$data->error->errorId.')';
		exit;
	}

	$flights = $data->flightStatuses;
	$output = array();
	foreach($flights as $flight)
	{
		switch($flight->status)
		{
			case 'A':
				$status = 'On-Time';
				break;
			case  'C':
				$status = 'Cancelled';
				break;
			case 'R':
			case 'D':
				$status = 'Diverted';
				break;
			case 'L':
				$status = 'Landed';
				break;
			case 'S':
				$status = 'Scheduled';
				break;
			case 'U':
			case 'NO':
			case 'DN':
			default:
				$status = 'Unknown';
				break;
		}

		if($status != 'Unknown')
		{
			$tmp = array();
			$tmp = array(
						'carrier' => $flight->carrierFsCode,
						'flightnumber' => $flight->flightNumber,
						'status' => $status,
						);
			if($flight_type == 'dep')
			{
				if(isset($flight->operationalTimes->estimatedGateDeparture->dateLocal))
				{
					$time = $flight->operationalTimes->estimatedGateDeparture->dateLocal;
				}elseif(isset($flight->operationalTimes->estimatedRunwayDeparture->dateLocal))
				{
					$time = $flight->operationalTimes->estimatedRunwayDeparture->dateLocal;
				}else{
					$time = $flight->operationalTimes->publishedDeparture->dateLocal;
				}
				$tmp['est_time'] = date("H:i",strtotime($time));
				if(isset($flight->operationalTimes->actualGateDeparture->dateLocal))
				{
					$time = $flight->operationalTimes->actualGateDeparture->dateLocal;
				}elseif(isset($flight->operationalTimes->actualRunwayDeparture->dateLocal))
				{
					$time = $flight->operationalTimes->actualRunwayDeparture->dateLocal;
				}else{
					$time = '';
				}
				$tmp['act_time'] = date("H:i",strtotime($time));
				$tmp['airport'] = $flight->arrivalAirportFsCode;
				@$tmp['terminal'] = $flight->departureTerminal;
				@$tmp['gate'] = $flight->departureGate;
			}else{
				if(isset($flight->operationalTimes->estimatedRunwayArrival->dateLocal))
				{
					$time = $flight->operationalTimes->estimatedRunwayArrival->dateLocal;
				}elseif(isset($flight->operationalTimes->estimatedGateArrival->dateLocal))
				{
					$time = $flight->operationalTimes->estimatedGateArrival->dateLocal;
				}else{
					$time = $flight->operationalTimes->publishedArrival->dateLocal;
				}
				$tmp['est_time'] = date("H:i",strtotime($time));
				if(isset($flight->operationalTimes->actualRunwayArrival->dateLocal))
				{
					$time = $flight->operationalTimes->actualRunwayArrival->dateLocal;
				}elseif(isset($flight->operationalTimes->actualGateArrival->dateLocal))
				{
					$time = $flight->operationalTimes->actualGateArrival->dateLocal;
				}else{
					$time = '';
				}
				$tmp['act_time'] = date("H:i",strtotime($time));
				$tmp['airport'] = $flight->departureAirportFsCode;
				@$tmp['terminal'] = $flight->arrivalTerminal;
				@$tmp['gate'] = $flight->arrivalGate;
			}

			if($tmp['carrier'] == '00')
			{
				$tmp['carrier'] = '';
			}

			$codeshares = array();
			if(isset($flight->codeshares))
			{
				foreach($flight->codeshares as $codeshare)
				{
					$codeshares[] = $codeshare->fsCode.$codeshare->flightNumber;
				}
			}
			$tmp['codeshares'] = $codeshares;

			$output[] = $tmp;
		}
	}
	aasort($output,"est_time");
	$data = array();
	foreach($output as $flight)
	{
		$data[] = $flight;
	}
	echo json_encode($data);
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

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

?>
