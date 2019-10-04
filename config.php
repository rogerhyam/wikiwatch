<?php

require_once('../wikiwatch_secret.php');

// create and initialise the database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);

// connect to the database
if ($mysqli->connect_error) {
  echo $mysqli->connect_error;
}

if (!$mysqli->set_charset("utf8")) {
  echo printf("Error loading character set utf8: %s\n", $mysqli->error);
}

function get_bgbase_connection(){
	
	global $bgbase_credentials;
	global $bgbase_mysqli;
	
	if(isset($bgbase_mysqli))return $bgbase_mysqli;
	
	// create and initialise the database connection
	$bgbase_mysqli = new mysqli($bgbase_credentials->host, $bgbase_credentials->user, $bgbase_credentials->password, $bgbase_credentials->database);    

	// connect to the database
	if ($bgbase_mysqli->connect_error) {
	  echo $bgbase_mysqli->connect_error;
	}

	if (!$bgbase_mysqli->set_charset("utf8")) {
	  echo printf("Error loading character set utf8: %s\n", $bgbase_mysqli->error);
	}
	
	return $bgbase_mysqli;
}

function get_specify_connection(){
	
	global $specify_credentials;
	global $specify_mysqli;
	
	if(isset($specify_mysqli))return $specify_mysqli;
	
	// create and initialise the database connection
	$specify_mysqli = new mysqli($specify_credentials->host, $specify_credentials->user, $specify_credentials->password, $specify_credentials->database);    

	// connect to the database
	if ($specify_mysqli->connect_error) {
	  echo $specify_mysqli->connect_error;
	}

	if (!$specify_mysqli->set_charset("utf8")) {
	  echo printf("Error loading character set utf8: %s\n", $specify_mysqli->error);
	}
	
	return $specify_mysqli;
	
}


?>