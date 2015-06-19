# scopus-api-scripts
Template scripts for working with the Scopus APIs

Please refer to the file "Scopus APIs - Documentation.pdf" for more complete information.

OVERVIEW
This package includes a set of scripts that can be used to query the Scopus API (application program interface). Although these particular scripts are written in PHP (http://www.php.net/) with API calls executed via cURL, any other scripting language that enables interaction with a RESTful API would produce similar results.

SCRIPTS
The following scripts are included in this package:


getAuthorPublications.php
  API: content/search/scopus
  Retrieves data about publications authored by a specified list of people, based on known Scopus author IDs

getPublicationData.php
  API: content/search/scopus
  Retrieves data about a specified list of publications, based on known Scopus publication eIDs (electronic identifiers)

searchForAuthor.php
  API: content/search/author
  Searches for and retrieves data about authors profiled in Scopus, based on name, affiliation, and/or other parameters
