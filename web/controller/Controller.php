<?php

$webDir = dirname(__DIR__);
include_once($webDir.'/model/connexion_sql.php');
include_once('Writer.php');
include_once('DatabaseSerializer.php');
include_once($webDir.'/ExecAlgo.php');

//----- POINTGPS
include_once($webDir.'/model/pointGPS/PointGPS.class.php');
include_once($webDir.'/model/pointGPS/PointGpsService.php');
include_once('pointGPS/index.php');

//----- SEGMENTS
include_once($webDir.'/model/segment/SegmentService.php');
include_once('segment/index.php');


/**
 * Process action corresponding to http get from main
 * @param unknown $request
 * @param unknown $params
 */
function analyzeRequest($request, $params)
{
	global $webDir;

	switch($request)
	{
		case "displayPoints":
		$arr[5]=true;
		var_dump($arr[5]);
		var_dump($arr[6]);
			// Display view
			include_once($webDir.'/view/pointGPS/index.php');
		break;

		case "displaySegments":
			// Display view
			include_once($webDir.'/view/segment/index.php');
		break;

		case "parse":
			//	Populate DB
			include_once('parserOsm.php');
		break;

		case "getDistances":
			//	Write distances to script scripts/setDistances.sql
			update_distances(10000);
			echo "DONE : Check file scripts/setDistance.sql !<br/>";
			break;

		case "getRoutes":
			if (!empty($params))
			{
				getRoutes($params);
			}
			else
				print "ERROR: No parameters !";
			break;

		case "serializeDB":
			//	Write to file .OSM all the points & segments of DB
			reset_osm_file();
			print_osm_nodes(2500);
			//print_osm_ways(5500);
			end_osm_file();
		break;

		default:
			print "Action not found";
			break;
	}
}

/**
*	Returns the routes matching the criterias from $params
*
*	0. Extract the parameters
*	1. Get closer point to $deb & closer point to $fin
*	2. Get segments contained in the rectangle area
*	3. Print the located segments to param.json file
*	4. Call algorithm to find solutions
*	5. Fill the solutions with the GPS points for the app to display the solutions
*	6. Return the solutions to the app
*/
function getRoutes($params)
{
	global $webDir;
	$fileParam = $webDir.'/../files/json/param.json';

	//format the params to get only the name
	$paramsFormatted = explode(";", $params, 9);

	//	START & END POINTS
	$latDeb = $paramsFormatted[0];
	$lonDeb = $paramsFormatted[1];
	$latFin = $paramsFormatted[2];
	$lonFin = $paramsFormatted[3];

	//	RECTANGLE COORDINATES
	$latMin = $paramsFormatted[4];
	$latMax = $paramsFormatted[5];
	$lonMin = $paramsFormatted[6];
	$lonMax = $paramsFormatted[7];

	//	DISTANCE
	$distance = $paramsFormatted[8];

	//	1. Get closer points to $deb & closer point to $fin
	// $closestDeb = process_closer_point($latDeb, $lonDeb);
	// var_dump($closestDeb);
	// $closestFin = process_closer_point($latFin, $lonFin);
	// var_dump($closestFin);
	// //	2. Get segments contained in the rectangle area
	// $selectedSegments = get_segments_in_rectangle($latMin, $lonMin, $latMax, $lonMax);
	// var_dump(count($selectedSegments));
	// //	3. Print the located segments to param.json file
	// format_response_nodes_ways($selectedSegments, $distance, $closestDeb[0]['idosm'], $closestFin[0]['idosm']);

	//	4. Call algorithm to find solutions
	call_algo();
}



/**
*	Formats the response from service to proper array
*	to print it in param.json
*/
function format_response_nodes_ways($response, $distance, $idDeb, $idFin)
{
	global $webDir;
	$fileParam = $webDir.'/../files/json/param.json';

	$resFormated[0]['nom'] = 'param';
	$resFormated[0]['resultat'] = array('distance' => $distance);

	$resFormated[1]['nom'] = 'points';
	$resFormated[1]['resultat'] = array();

	$resFormated[2]['nom'] = 'arete';
	$resFormated[2]['resultat'] = array();

	//	Structure to remember which nodes were added -> to add each node only once
	$alreadyAdded = array();
	$fileOsm=dirname(__DIR__).'/../files/osm/database.osm';
	reset_osm_file();
	foreach ($response as $segment) 
	{
		$line = "<node id='".$segment['idnodea']."' lat='".$segment['lata']."' lon='".$segment['lona']."'/>";
		append_to_file($fileOsm, $line);
		$line = "<node id='".$segment['idnodeb']."' lat='".$segment['latb']."' lon='".$segment['lonb']."'/>";
		append_to_file($fileOsm, $line);

		if ($segment['idnodea']==$idDeb)
		{
			//	Replace the id value with 'deb'
		
			if (!in_array($segment['idnodea'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodea'];
				$resFormated[1]['resultat'][] = array('id' => 'deb',
													  'lat' => $segment['lata'],
													  'lng' => $segment['lona']);
			}
			if (!in_array($segment['idnodeb'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodeb'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodeb']."",
													  'lat' => $segment['latb'],
													  'lng' => $segment['lonb']);
			}

			$resFormated[2]['resultat'][] = array('id' => $segment['id'], 
												  'id_a' => 'deb',
												  'id_b' => "".$segment['idnodeb']."", 
												  'distance' => $segment['distance']);
		}
		elseif ($segment['idnodeb']==$idDeb) 
		{
			//	Replace the id value with 'deb'
		
			if (!in_array($segment['idnodea'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodea'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodea']."",
													  'lat' => $segment['lata'],
													  'lng' => $segment['lona']);
			}
			if (!in_array($segment['idnodeb'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodeb'];
				$resFormated[1]['resultat'][] = array('id' => 'deb',
													  'lat' => $segment['latb'],
													  'lng' => $segment['lonb']);
			}

			$resFormated[2]['resultat'][] = array('id' => $segment['id'], 
												  'id_a' => "".$segment['idnodea']."",
												  'id_b' => 'deb', 
												  'distance' => $segment['distance']);
		}
		elseif ($segment['idnodea']==$idFin) 
		{
			//	Replace the id value with 'deb'
		
			if (!in_array($segment['idnodea'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodea'];
				$resFormated[1]['resultat'][] = array('id' => 'fin',
													  'lat' => $segment['lata'],
													  'lng' => $segment['lona']);
			}
			if (!in_array($segment['idnodeb'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodeb'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodeb']."",
													  'lat' => $segment['latb'],
													  'lng' => $segment['lonb']);
			}

			$resFormated[2]['resultat'][] = array('id' => $segment['id'], 
												  'id_a' => 'fin',
												  'id_b' => "".$segment['idnodeb']."", 
												  'distance' => $segment['distance']);
		}
		elseif ($segment['idnodeb']==$idFin) 
		{
			//	Replace the id value with 'deb'
		
			if (!in_array($segment['idnodea'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodea'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodea']."",
													  'lat' => $segment['lata'],
													  'lng' => $segment['lona']);
			}
			if (!in_array($segment['idnodeb'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodeb'];
				$resFormated[1]['resultat'][] = array('id' => 'fin',
													  'lat' => $segment['latb'],
													  'lng' => $segment['lonb']);
			}

			$resFormated[2]['resultat'][] = array('id' => $segment['id'], 
												  'id_a' => "".$segment['idnodea']."",
												  'id_b' => 'fin', 
												  'distance' => $segment['distance']);
		}
		else
		{
		
			if (!in_array($segment['idnodea'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodea'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodea']."",
													  'lat' => $segment['lata'],
													  'lng' => $segment['lona']);
			}
			if (!in_array($segment['idnodeb'], $alreadyAdded))
			{
				// Not found
				$alreadyAdded[] = $segment['idnodeb'];
				$resFormated[1]['resultat'][] = array('id' => "".$segment['idnodeb']."",
													  'lat' => $segment['latb'],
													  'lng' => $segment['lonb']);
			}

			$resFormated[2]['resultat'][] = array('id' => $segment['id'], 
												  'id_a' => "".$segment['idnodea']."",
												  'id_b' => "".$segment['idnodeb']."", 
												  'distance' => $segment['distance']);
		}
	}
	end_osm_file();
	reset_write_to_file($fileParam, "");
	append_to_file_json($fileParam, $resFormated);
}


function buildSolutionsFromSegments($idDeb)
{
	$contents = fread(fopen('output.json', "r"), filesize('output.json'));
	$contents = json_decode($contents);

	$tab = array();
	$i=0;
	foreach($contents->solutions as $sol)
	{
		$solution = array();
		foreach($sol->arcs as $arc)
		{
			$points = get_segment_points_ordered($arc->idArc, $arc->noeudDeb == "Deb" ? $idDeb : $arc->noeudDeb);
			$solution = array_merge($solution, $points);
		}
		$tab[$i++] = $solution;
	}

	print json_encode($tab);
	return $tab;
}
