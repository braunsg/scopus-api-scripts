<?php

// Created by Steven Braun, 2015-06-08

// This script pulls data for publications based on known Scopus publication eIDs
// using the Scopus search API
// API base: http://api.elsevier.com/content/search/scopus
// Script current as of API 2015-06-02 release
// API documentation: http://api.elsevier.com/documentation/SCOPUSSearchAPI.wadl

// Define an array of known Scopus publication eIDs to loop through;
// here, I'm using just one, but you can specify multiple
$eidArray = array('PUBLICATION_EID');

print "Obtaining publication data for...\n";
$thisCount = 0;
$continueCt = 0;
$eidCount = count($eidArray);

// Loop through each eID, one by one
foreach($eidArray as $eid) {
	$thisCount++;
					
	print "eID: " . $eid . " (" . $thisCount . "/" . $eidCount . ")\n";

	// Define some parameters to control how many results are retrieved
	// The Scopus APIs have some limits as to how many results it will retrieve in a single API call;
	// also, it is easier to work with the returned data if the results set is smaller
	$offset = 0;
	$countTotal = 0;

	// Let's pull a maximum of 50 publication results per API call	
	$countIncrement = 50;
	$loopThrough = 1;
	$totalResults = null;
	$pubCtr = 0;
	
	// Let's loop through API calls as long as it keeps returning us results --
	// In this example, we are forming our query based on a known unique eID, and so the API
	// should return only a single result; in other situations, using different query parameters
	// might yield more results, making this looping more relevant	
	while($loopThrough == 1) {

		// Define the query string for the API in a variable. This string can be written in the
		// same way as an advanced search string on the Scopus online database, with some field names changed.
		// Here, I will do a Scopus search for the publication identified by the given eID.
		// NOTE: The query string must be URL-encoded
		$query = urlencode('eid(' . $eid . ')');
		
		// Define the URL of the API to query. The API is RESTful and also allows other parameters beyond the query,
		// such as limiting which fields to return and the number of results to return, that are defined
		// in the API documentation		
		$url = 'http://api.elsevier.com/content/search/scopus?query=' . $query . '&view=COMPLETE&count=' . $countIncrement . '&start=' . $offset;

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
			// Here, we're defining a variable $pubs that holds all of the PUBLICATION data,
			// which is represented in the JSON under search-results -> entry
			$pubs = $json['search-results']['entry'];
			$pubsCount = count($pubs);
			$countTotal += count($pubs);
			
			
			if(is_null($totalResults)) {

				// Grab the total number of results returned from the query
				$totalResults = $json['search-results']['opensearch:totalResults'];

				if($totalResults == 0) {
					print "\tNo publications recorded with this ID.\n";

					// If the query returns 0 results, then quit looping through this publication eID
					$loopThrough  = 0;
					continue;
				} else {
					print "\tTotal results: " . $totalResults . "\n";
				}
			}
		
			// Let's walk through each publication result stored in $pubs, one by one,
			// and display the returned data for each;
			// since the example here is performing the search by eID, the query should
			// only return one result
			foreach($pubs as $key => $pubInfo) {
				$pubCtr++;
				print "\tPublication " . $pubCtr . "/" . $totalResults . "\n";

				// If the publication entry has an error...
				if($pubInfo['error']) {
					$thisError = $pubInfo['error'];
					if($thisError !== "Result set was empty") {
						print "Error message: " . $thisError . "\n";
					}

				// Otherwise, proceed with publication entry					
				} else {

					// This is where you'd take the JSON data and do what you want with it,
					// such as dump it into a database, do some analyses, echo it out, etc.
				
					print_r($pubInfo);
					
				} // End if($pubInfo['error']) structure

			} // End foreach($pubs) structure

		} // End if($result === false) structure

		// Check to see if we need to keep looping through this particular publication search
		// and retrieve additional records
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