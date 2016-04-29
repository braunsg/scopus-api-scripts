# scopus-api-scripts
Template scripts for working with the Scopus APIs
LAST UPDATED: 2015-07-14

Please refer to the file "Scopus APIs - Documentation.pdf" for more complete information.

# Overview
This package includes a set of scripts that can be used to query the Scopus API (application program interface). Although these particular scripts are written in PHP (http://www.php.net/) with API calls executed via cURL, any other scripting language that enables interaction with a RESTful API would produce similar results.

# Scripts
The following scripts are included in this package:

  get-author-publications.php
    API: content/search/scopus
    Retrieves data about publications authored by a specified list of people, based on known Scopus author IDs

  get-author-publications_mysql.php
    API: content/search/scopus
    Retrieves data about publications authored by a specified list of people, based on known Scopus author IDs,
    AND injects data into MySQL database

  get-publication-data.php
    API: content/search/scopus
    Retrieves data about a specified list of publications, based on known Scopus publication eIDs (electronic identifiers)

  get-publication-data_mysql.php
    API: content/search/scopus
    Retrieves data about a specified list of publications, based on known Scopus publication eIDs (electronic identifiers),
    AND injects data into MySQL database

  search-for-author.php
    API: content/search/author
    Searches for and retrieves data about authors profiled in Scopus, based on name, affiliation, and/or other parameters

  expand-experts-data.php
    A generic script that parses XML dumped from Experts and expands Scopus author IDs 
    store therein
  
  tables/
  		faculty_publications.mssql
  		  MySQL query generating structure for a generic table holding data about
  		  researcher authorships (publications)
  		  
  		publication_data.mssql
  		  MySQL query generating structure for a generic table holding data about 
  		  publications, such as title, publication date, and citation count
  		  
  		faculty_identifiers.mssql
  		  MySQL query generating structure for a generic table holding data about 
  		  researcher IDs, such as Scopus author IDs or ORCIDs
