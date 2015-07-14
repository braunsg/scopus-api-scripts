<?php
/*
scopus-api-scripts
http://www.github.com/braunsg/scopus-api-scripts

Copyright (c) 2015 Steven Braun
Created: 2015-06-18
Last updated: 2015-07-14

This script expands data in the Experts bulk export XML file into a MySQL database,
specifically grabbing ***Scopus author IDs*** for faculty in Experts; could be adapted
to extra other data as well, such as publications

This script is shared under an MIT License, which grants free use, modification, 
and distribution for all users. See LICENSE.md for more details.

////////////////////////////////////////////////////////////////////////////////////////*/


// Instantiate connection to database; replace database credentials appropriately
$con = mysqli_connect("SERVER_HOST","DATABASE_USER","DATABASE_PASSWORD","DATABASE_NAME");


// In this script, we will read through and parse the bulk export XML file
// using a PHP library called XMLReader

// Increase the memory limit so script does not halt from overload
ini_set('memory_limit', '1024M');

// Auto-detect line endings
ini_set('auto_detect_line_endings', true);

// Optional: error reporting parameters
error_reporting(E_ERROR | E_PARSE);

// Instantiate an array that will be used to store Scopus publication IDs
$pubIDs = array();

// If you know all the internetIDs for the faculty of interest (School of Nursing),
// instantiate an array to store them for later matching (example IDs shown here)
// Note the structure: x500s are indexes (keys), and each key has an empty array as its value
// (this is important later)
$internet_ids = array("test_user1" => array(), "test_user2" => array());


print "Pulling Scopus ID data from bulk export file...\n";
sleep(2);

// Assign the name of the XML file to open
$bulkExportFile = 'name_of_bulk_export_file.xml';

// Instantiate new XMLReader object
$reader = new XMLReader();

// Open the XML file
$reader->open($bulkExportFile);

// Instantiate some variables that will help us to move through the XML file 
// more quickly -- the use of these will be demonstrated below
$expertContinue = 0;
$readingPubs = 0;
$readAuthors = 0;

// Read through the XML file, one node at a time
while($reader->read()) {

	// Pull the timestamp information from file
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'Data') {
		$timeStamp = $reader->getAttribute('Timestamp');
		$dateStamp = date("Ymd",strtotime($timeStamp));

		// Create data table in the database to hold Experts data from this XML load specifically
		$tableName = "scopus_id_data" . $dateStamp;
		$table_sql = "CREATE TABLE IF NOT EXISTS $tableName (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
internetID VARCHAR(30) NOT NULL,
scopusID VARCHAR(30) NOT NULL)";

		if(mysqli_query($con,$table_sql)) {
			print "Table $tableName created.\n";
		} else {
			die("Table creation error: " . mysqli_error($con) . "\n");
		}
	}

	// Alert when you've hit the Experts parent node	
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'Experts') {
		print "Reading Experts tree...\n";
	}
	

	// If node is of type 'Expert,' read the node
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'Expert') {
		
		// Grab internet ID (x500) for the Expert
		$getInternetId = $reader->getAttribute('InternetID');

		/////////////////////////////////////////////////////////////////////////////////
		//// NOTE ///////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////
		
		/*
		
		Experts assigns unique IDs (OrgaUnitId) for all departmental/college affiliations of faculty. You could
		determine the IDs of specific interest (i.e., CBS, CFANS, Medical School departments)
		to filter out only faculty with those affiliations as indicated in Experts. Faculty affiliations
		are identified as subnodes to each faculty node. If a given faculty node shows an affiliation
		ID matching one of those of interest, then read the node; otherwise, skip over it.
		
		If you know the internetIDs of the faculty of interest a priori, you can assign those to
		an $internet_ids array and check the value of $getInternetId for a match in the array.
		
		Example shown below:
		
		*/

		// If internetID of current faculty node is in $internet_ids array, then continue reading the node
		if(array_key_exists($getInternetId,$internet_ids)) {
			print $getInternetId . "\n";
			$expertContinue = 1;

		// If internetID is NOT in $internet_ids array, then skip over the rest of this Expert node
		} else {
			$expertContinue = 0;
			$reader->next();
		}

		/*
		If you do NOT know all the internetIDs in advance, this becomes more complicated because author
		affiliations are stored in the subnode <Affiliations>, which comes at the end of the Expert record.
		You'll need a looping procedure where you go through ALL Expert nodes,
		identify which Experts have a matching OrgaUnitID, store those internetIDs to
		the $internet_ids array, and then go through the procedure here again.
		*/
		

	}

	// If hit an end element for Expert node, set $expertContinue = 0 so we know
	// that we've closed out working with one node. This is really only useful if
	// you're filtering faculty based on affiliation ID as described above
	if($reader->nodeType == XMLREADER::END_ELEMENT && $reader->name == 'Expert') {
		$expertContinue = 0;
	}

	// If we've identified this node as matching an internetID in our target population,
	// continue reading through the node elements
	if($expertContinue == 1) {
	
		// If we hit an Authorship element, read it
		if($reader->name == 'Authorship') {
			
			// Grab PublicationId and publication type from the element
			$pubId = $reader->getAttribute('PublicationId');
			$pubType = $reader->getAttribute('PubType');

			// Only read the authorship element if it's a Scopus Publication;
			// we're doing this here because in this script we're specifically trying
			// to grab Scopus author IDs for faculty, but this could be changed
			if($pubType === "ScopusPub") {
			
				// If PublicationId not currently in $pubIDs array,
				// store it in the array
				if(!array_key_exists($pubId,$pubIDs)) {

					// The $pubIDs array is indexed by PublicationId ($pubId),
					// and each index key is paired with an array that holds
					// all internetIDs of faculty of interest who are authors
					// of that publication
					$pubIDs[$pubId] = array($getInternetId);

				// If PublicationId already in $pubIDs array, then add internetID of 
				// faculty author to the corresponding array for the index
				} else {
					$pubIDs[$pubId][] = $getInternetId;
				}
				
				// Grab the author position of this faculty member on this publication
				// and store it back in the $internet_ids array for later reference
				$authorPosition = $reader->getAttribute('AuthorNr');
				$internet_ids[$getInternetId]['pubIDs'][$pubId] = $authorPosition;
			}
		}
	}
		
	// If we're done reading the Experts node and have moved on to the ScopusPublications
	// node, change $readingPubs = 1
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'ScopusPublications') {
		$readingPubs = 1;
		print "Reading ScopusPublications tree...\n";

	// Otherwise, if this node is the end element for ScopusPublications, quit reading
	// the file -- we're done (or, continue reading other nodes from the file)
	} else if($reader->nodeType == XMLREADER::END_ELEMENT && $reader->name == 'ScopusPublications') {
		$readingPubs = 0;
		$reader->close($bulkExportFile); // Done reading bulk export file		
	}

	// If this is a Publication node, read it
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'Publication' && $readingPubs == 1) {

		// Grab the PublicationId
		$getPubId = $reader->getAttribute('Id');

		// If this PublicationId matches one in the $pubIDs array, then set a flag
		// indicating we should read the authors for this node
		if(array_key_exists($getPubId,$pubIDs)) {
			$readAuthors = 1;
		} else {
			$readAuthors = 0;
		}
	}
	
	// If this is an Author element, and we need to read the authors for this Publication node,
	// then continue reading
	if($reader->nodeType == XMLREADER::ELEMENT && $reader->name == 'Author' && $readAuthors == 1) {

		// Grab Scopus author ID (ScopusId) for this Author element
		$getScopusId = $reader->getAttribute('ScopusId');
		
		// Grab author number/index (i.e., position in author list)
		$getAuthorNr = $reader->getAttribute('AuthorNr');

		// Loop through all the internetIDs associated with this Publication in
		// the $pubIDs array
		foreach($pubIDs[$getPubId] as $internetId) {
			
			// If the author index of this element matches the author index of
			// the faculty member (internetID) for this particular Publication...
			if($getAuthorNr == $internet_ids[$internetId]["pubIDs"][$getPubId]) {

				// If this ScopusId is not associated with the internetID in the
				// faculty array, add it
				if(!in_array($getScopusId,$internet_ids[$internetId]["scopusIDs"])) {
					$internet_ids[$internetId]["scopusIDs"][] = $getScopusId;
					
					// Now insert this internetID - Scopus author ID pair into the table
					
					$insert_sql = "INSERT INTO $tableName (internetID, scopusID) VALUES ('$internetId','$getScopusId')";
					if(mysqli_query($con,$insert_sql)) {
						print "Added\t" . $internetId . "\t" . $getScopusId . "\n";
					} else {
						die("Error: " . mysqli_error($con) . "\n");
					}
				}		
			}
		}
	}
	
	// If we've hit the end of the Authors node, quit reading Author elements
	if($reader->nodeType == XMLREADER::END_ELEMENT && $reader->name == 'Authors') {
		$readAuthors = 0;
	}
	
	
}





mysqli_close($con);




?>