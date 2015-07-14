<?php
/*
scopus-api-scripts
http://www.github.com/braunsg/scopus-api-scripts

Copyright (c) 2015 Steven Braun
Created: 2015-06-08
Last updated: 2015-07-14

This script does a search for an author based on name and affiliation
using the Scopus author API

API base: http://api.elsevier.com/content/search/scopus
Script current as of API 2015-06-02 release
API documentation: http://api.elsevier.com/documentation/SCOPUSSearchAPI.wadl

This script is shared under an MIT License, which grants free use, modification, 
and distribution for all users. See LICENSE.md for more details.

//////////////////////////////////////////////////////////////////////////////////////////
///// NOTE

This script is adapted from scripts used to pull data for Manifold, the research impact 
analytics system developed by Steven Braun for the University of Minnesota Medical School

FOR MORE INFO, see

manifold-impact-analytics
http://www.github.com/braunsg/manifold-impact-analytics

////////////////////////////////////////////////////////////////////////////////////////*/


// Define an array of known author names to loop through; these are the names of people
// that you are attempting to search for
// Here, I'm using just one as an example, but you can specify multiple
// Also, you can choose additional or other parameters to perform your search

$nameArray = array(
				array("firstName" => "FIRST_NAME",
					  "lastName" => "LAST_NAME")
			);

print "Attempting to search for...\n";
$thisCount = 0;
$continueCt = 0;
$nameCount = count($nameArray);

// Loop through each name, one by one
foreach($nameArray as $nameData) {
	$thisCount++;

	$firstName = $nameData["firstName"];
	$lastName = $nameData["lastName"];
						
	print "Name: " . $lastName . ", " . $firstName . " (" . $thisCount . "/" . $nameCount . ")\n";

	// Define some parameters to control how many results are retrieved
	// The Scopus APIs have some limits as to how many results it will retrieve in a single API call;
	// also, it is easier to work with the returned data if the results set is smaller
	$offset = 0;
	$countTotal = 0;

	// Let's pull a maximum of 50 publication results per API call
	$countIncrement = 50;
	$loopThrough = 1;
	$totalResults = null;
	$authorCtr = 0;

	// Let's loop through API calls as long as it keeps returning us results
	// For example, if an author search returns 60 results and the return count increment
	// is set at 50, the script will loop through 2 times:
	//		(1) 50 results (total retrieval count: 50)
	//		(2) 10 results (total retrieval count: 60)
	while($loopThrough == 1) {


		// Define the query string for the API in a variable. This string can be written in the
		// same way as an advanced search string on the Scopus online database, with some field names changed.
		// Here, I will do a search for authors with the first/last name indicated 
		// and with an EXAMPLE affiliation of 'University of Minnesota' at some point in their affiliation history 
		// NOTE: The query string must be URL-encoded

		$query = urlencode("affil(university of minnesota) AND authfirst(" . $firstName . ") AND authlast(" . $lastName . ")");

		// Define the URL of the API to query. The API is RESTful and also allows other parameters beyond the query,
		// such as limiting which fields to return and the number of results to return, that are defined
		// in the API documentation		
		$url = 'http://api.elsevier.com/content/search/author?query=' . $query;

		// Since this script is written in PHP, the API call will be executed via cURL
		$openCurl = curl_init();

		curl_setopt_array($openCurl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => array(
					// Specify the API key -- replace with your own once registered
					'X-ELS-APIKey: [YOUR API KEY HERE]',
					'Accept: application/json'
				)
		));

		// Store the data returned by the API in a variable $result
		$result = curl_exec($openCurl);
		$httpCode = curl_getinfo($openCurl, CURLINFO_HTTP_CODE); // Retrieve HTTP Response

		// If the cURL call returns an error...
		if($result === false) {
			echo 'Curl error: ' . curl_error($openCurl);

		// If the cURL call is successful, but returns an HTTP error code...
		} else if($httpCode !== 200) {
			print "HTTP Response Error - Code: " . $httpCode . "\n";

		// Otherwise, proceed with returned results
		} else {
			// The query response returns data in JSON format, but we need to encode it as such
			// so PHP can know how to read it.
			// You could print out the $json variable to see the returned data in its entirety
			$json = json_decode($result,true);

			// The query response returns a lot of different data in a structured format.
			// Here, we're defining a variable $authors that holds all of the AUTHOR data,
			// which is represented in the JSON under search-results -> entry
			$authors = $json['search-results']['entry'];
			$authorsCount = count($authors);
			$countTotal += count($authors);
			
			
			if(is_null($totalResults)) {
	
				// Grab the total number of results returned from the query
				$totalResults = $json['search-results']['opensearch:totalResults'];

				if($totalResults == 0) {
					print "\tNo results found.\n";

					// If the query returns 0 results, then quit looping through this author name
					$loopThrough  = 0;
					continue;
				} else {
					print "\tTotal results: " . $totalResults . "\n";
				}
			}
		

			// Let's walk through each author result stored in $authors, one by one,
			// and display the returned data for each
			foreach($authors as $key => $authorInfo) {
				$authorCtr++;
				print "\tAuthor result " . $authorCtr . "/" . $totalResults . "\n";

				// If the author entry has an error...
				if($authorInfo['error']) {
					$thisError = $authorInfo['error'];
					if($thisError !== "Result set was empty") {
						print "Error message: " . $thisError . "\n";
					}
					
				// Otherwise, proceed with author entry					
				} else {
				
					// This is where you'd take the JSON data and do what you want with it,
					// such as dump it into a database, do some analyses, echo it out, etc.
					// See get-publication-data_mysql.php and 
					// get-author-publications_mysql.php for examples
				
					print_r($authorInfo);

				} // End if($authorInfo['error']) structure

			} // End foreach($authors) structure

		} // End if($result === false) structure

		// Check to see if we need to keep looping through author results
		// and retrieve additional query matches
		if($totalResults - $countTotal > 0) {
			$offset += $countIncrement;
		} else {
			$loopThrough = 0;
		}

	} // End LOOPTHROUGH control structure

	// Close the cURL connection
	curl_close($openCurl);
}

?>