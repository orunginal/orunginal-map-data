<?php
	
include_once('model/segment/SegmentService.php');
include_once('model/segment/Segment.class.php');
include_once('model/node/Node.class.php');
include_once('model/pointGPS/PointGPS.class.php');
include_once('controller/Writer.php');

//echo 'includes ok';
// Test insert

//$idseg = insert_segment_into_segments(12, 9, 2.9, 126079, 1472874878);
//var_dump($idseg[0]['id']);
//insert_segment_into_s2p(1, array(0 => 1472874878, 1 => 126079, 2 => 143412), true);

//$points = get_segment_points_ordered(3,4);


/**
*	Display some statistics about s2p
*
*/
function display_stats($limit)
{
	echo "<h2>----- TABLE S2P - STATISTICS ------</h2>";
	$nodes = find_intersection_in_s2p($limit);
	$nb = count_rows_in_s2p();
	$nbNodes = count_nodes_in_s2p();
	echo 'Number of rows = '.$nb[0]['count'].'<br/><br/>';
	echo 'Number of intersections = '.$nbNodes[0]['count'].'<br/><br/>';
	print json_encode($nodes);
}

function display_s2p($limit)
{
	echo "<h2>----- DISPLAY TABLE S2P ------</h2>";
	$segments = get_s2p($limit);
	echo 'Number of segments displayed = '.$limit.'<br/><br/>';
	print json_encode($segments);
}

function print_delete_s2p($anIdSegment)
{
	echo "<h2>----- DELETE SEGMENT FROM S2P ------</h2>";
	$deletedItem = delete_from_s2p_by_id($anIdSegment);
	echo 'Number of segments deleted = '.count($deletedItem).'<br/><br/>';
	print json_encode($deletedItem);
}

function display_segments($limit)
{
	echo "<h2>----- DISPLAY TABLE SEGMENTS ------</h2>";
	$segments = get_segment($limit);
	$nb = count_rows_in_segments();
	echo 'Number of segments = '.$nb[0]['count'].'<br/><br/>';
	print json_encode($segments);
}

function print_delete_segments($anIdSegment)
{
	echo "<h2>----- DELETE SEGMENT FROM SEGMENTS ------</h2>";
	$deletedItem = delete_from_segments_by_id($anIdSegment);
	echo 'Number of segments deleted = '.count($deletedItem).'<br/><br/>';
	print json_encode($deletedItem);
}

/**
*	Returns the gps points for a segment in the right order
* 	starting with the $startpoint
*/
function get_segment_points_ordered($idSegment, $idStartPoint)
{
	// Call sql request
	$pointsDB = get_segment_points_by_id($idSegment);

	// Data processing
	$points = array();
	$pointsLength = count($pointsDB);
	
	if ($pointsDB[0]['id']==$idStartPoint)
	{
		// GPS points are in the RIGHT ORDER
		foreach($pointsDB as $point) 
		{
			$points[] = new PointGPS($point['id'],$point['lat'],$point['lon']);
		}
	}
	else if ($pointsDB[$pointsLength-1]['id']==$idStartPoint)
	{
		// GPS points are in the REVERSE ORDER
		$i = $pointsLength - 1;
		foreach($pointsDB as $point) 
		{
			$points[$i] = new PointGPS($point['id'],$point['lat'],$point['lon']);
			$i--;
		}
	}
	else
	{
		echo '<br/><strong>ERROR:[get_segment_points_by_id] idStartPoint incorrect !!</strong><br/>';
	}

	print json_encode($pointsDB);
	return $points;
}

/*************************************************************************************/
//									SET DISTANCES
/*************************************************************************************/

update_distances(100);

/**
*	Erase content from file setDistances.sql
*
*/
function reset_file_setdistances()
{
	$file=dirname(__DIR__).'/../../scripts/setDistances.sql';

	reset_write_to_file($file, "");
}

/**
* 	Sets the distance value for each segment
* 	//	1. FOR EACH SEGMENT
*	//	2. GET THE LIST OF POINTS
*	//	3. Sum the distance between each couple of points
*/
function update_distances($limit)
{
	reset_file_setdistances();

	$segments = get_segment($limit);

	foreach ($segments as $segment) 
	{
		$segmentPoints = get_segment_points_by_id($segment['id']);
		$distance = 0;
		$nbPoints = count($segmentPoints);
		for ($i=0; $i < $nbPoints-1; $i++) 
		{ 
			$distance = $distance + get_distance($segmentPoints[$i]['lat'],
												 $segmentPoints[$i]['lon'],
												 $segmentPoints[$i+1]['lat'],
												 $segmentPoints[$i+1]['lon']);
		}

		//	UPDATE TABLE SEGMENT
		set_distance($segment['id'], $distance);
	}
}

/**
* 	Returns the distance between two gps points
* 	
*/
function get_distance($lat1, $lon1, $lat2, $lon2) 
{
  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  
  		  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;

  return ($miles * 1.609344);
}

update_distances(100);

// Display view
include_once(dirname(__DIR__).'/../view/segment/index.php');
