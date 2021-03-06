<?php


/**echo "serializeDB";
* Open $fileName & append $line at the end of the file
*
*/
function append_to_file($fileName, $line)
{
	//	Open file 
	$myfile = fopen($fileName, "a") or die("Unable to open file!");

	//	Write line
	fwrite($myfile, $line.PHP_EOL);
				
	fclose($myfile);
}

/**
* Open $fileName & append $content at the end of the file
*
*/
function append_to_file_json($fileName, $content)
{
	//	Open file 
	$myfile = fopen($fileName, "a") or die("Unable to open file!");

	//	Write content in JSON to the file
	fputs($myfile, json_encode($content, JSON_PRETTY_PRINT));
	//var_dump($res);
				
	fclose($myfile);
}

/**
* Open $fileName, erase content & write $line at the end of the file
*
*/
function reset_write_to_file($fileName, $line)
{
	//	Open file 
	$myfile = fopen($fileName, "w") or die("Unable to open file!");

	//	Write line
	fwrite($myfile, $line.PHP_EOL);
				
	fclose($myfile);
}