<?php
/**
 * This script updates the database to the specified game version.
 * Example of updating to version 0.2.0:
 * 
 * (from  /rpg_slack/temp  directory)
 *   php bin/update.php -v 0.2.0
 *
 * Options:
 * 		-v 		Version number to update to.
 *		-f 		Force the script to run the update in the -v option, even if it is up to date.
 *
 */

require_once('config.php');
require_once(RPG_SERVER_ROOT.'/includes/db.inc');

$version_db_table_name = 'game_version';
$path_to_updates = RPG_SERVER_ROOT.'/bin/updates';

// Get the parameters passed in from the PHP command line.
$shortopts = 'v:'; // Required
$shortopts .= 'f::'; // Optional
$longopts = array(
	'version:',	// Required
	'force::', // Optional
);
$opts = getopt($shortopts, $longopts);

// Get the current version and make sure we don't overwrite stuff.
$cur_version = get_current_version();

output_string("Current Version: {$cur_version}");

// If no version is sent, we're done.
if ( !isset($opts['v'])  ||  empty($opts['v']) ) {
	output_string("Please add a version number to update to using:  php [file] -v 1.2.3\n");
	exit;
}

$update_version = $opts['v'];
$force_update = isset($opts['f']);

// Look for versions in between that we need to update.
$updates = get_version_diff( $cur_version, $update_version, isset($opts['f']) );

if ( count($updates) <= 0 ) {
	if ( $cur_version == $update_version ) {
		output_string("Database is already up to date.\n");
	}
	else if ( version_gte_update($cur_version, $update_version) ) {
		output_string("Cannot downgrade the database version.\nSelect a version higher than the current version: {$cur_version}.\n");
	}
	else {
		output_string("Not sure why there are no updates...?\n");
	}
	exit;
}

output_string("Updating from {$cur_version} -> {$update_version}.");

// Create the version table if it hasn't already been created.
create_version_table();

// Run the updates.
foreach( $updates as $version => $filename ) {
	// Add in file so we can update.
	require_once( $filename );

	$func = 'update_version_'.str_replace('.', '_', $version);
	$queries = array();

	if ( function_exists($func) ) {
		output_string("Running version {$version} update...\n");

		// Populate the queries array.
		call_user_func($func, $force_update);

		// Start the queries.
		run_update_queries();

		// Update the version.
		update_version_table( $version );

		output_string("Completed version {$version} updates.");
	}
	else {
		output_string("Update {$version} was skipped because no update function was found.");
	}
}

output_string("Finished updating to version {$update_version}.\n");


// =======================================================
// Utility functions
//
// Shouldn't need to add anything below here.
// =======================================================

function add_update_query ( $query, $data = array() ) {
	global $queries;

	$queries[] = array(
		'query' => $query,
		'data' => $data,
	);
}

function run_update_queries () {
	global $queries;

	if ( count($queries) <= 0 ) {
		output_string("No database items to update.");
		return;
	}

	foreach( $queries as $query ) {
		output_string( 'Performing: '. str_replace(array_keys($query['data']), array_values($query['data']), $query['query']) .'... ', false );

		//$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		$statement = pdo_prepare($query['query']);
		$result = $statement->execute($query['data']);

		if ( $result === false ) {
			output_string("FAILED:");
			output_string( var_export($statement->errorInfo(), true)."\n" );
		}
		else {
			output_string("SUCCESS\n");
		}
	}
}

function create_version_table () {
	global $version_db_table_name;

	// Create the table for versions (only creates if it doesn't exist).
	$table = array();
	$table[] = "versionid INT(11) AUTO_INCREMENT";
	$table[] = "version VARCHAR(100) NOT NULL";
	$table[] = "updated INT UNSIGNED NOT NULL";
	$table[] = "PRIMARY KEY ( versionid )";

	$query = "CREATE TABLE IF NOT EXISTS ".$version_db_table_name." (". implode(',', $table) .")";
	$statement = pdo_prepare($query);
	$result = $statement->execute();
}

function update_version_table ( $version ) {
	global $version_db_table_name;
	
	$values = array(
		':version' => $version,
		':updated' => time(),
	);
	
	$fields = array();
	foreach( $values as $token => $value ) {
		$fields[] = substr($token, 1);
	}

	$query = "INSERT INTO ".$version_db_table_name." (". implode(', ', $fields) .") VALUES (". implode(', ', array_keys($values)) .")";	
	$statement = pdo_prepare($query);
	$result = $statement->execute($values);
}

function get_current_version () {
	global $version_db_table_name;
	
	$query = "SELECT version FROM ".$version_db_table_name." ORDER BY updated DESC, version DESC LIMIT 1";
	$statement = pdo_prepare($query);
	$result = $statement->execute();

	if ( $result === false  ||  $statement->rowCount() <= 0 ) {
		return '(none)';
	}

	$row = $statement->fetch();

	return $row['version'];
}

function get_version_diff ($cur, $update, $force_cur = false) {
	global $path_to_updates;

	if ( $cur == '(none)' ) {
		$cur = '0.0.-1';
	}

	$info = explode('.', $cur);
	$upinfo = explode('.', $update);
	$versions = array();

	if ( count($info) < 3  ||  count($upinfo) < 3 ) {
		return $versions;
	}

	foreach( $info as $key => $value ) {
		$info[$key] = (int)$value;
	}
	foreach( $upinfo as $key => $value ) {
		$upinfo[$key] = (int)$value;
	}

	// Keep track of indexes.
	$iprime = 0;
	$imajor = 1;
	$iminor = 2;

	// Track if we've tested all versions.
	$prime = false;
	$major = false;

	while ( true ) {
		$info[$iminor]++;

		if ( version_info_gte_update($info, $upinfo) ) break;

		$filename = $path_to_updates.'/version_'.implode('_', $info).'.php';

		// Check if there are any versions with the next number.
		if ( file_exists($filename) ) {
			$major = false;
			$prime = false;
			$versions[implode('.', $info)] = $filename;
		}
		// Otherwise, skip to the next version.
		else {
			// Reset the minor.
			$info[$iminor] = -1;
			// If we didn't increment the major last time, do it now.
			if ( $major == false ) {
				// Increase the major and mark it.
				$info[$imajor]++;
				$major = true;
			}
			// If we already increased the major and didn't find anything, increase the prime.
			else if ( $prime == false ) {
				// Reset the major.
				$info[$imajor] = 0;
				$major = false;
				// Increase the prime and mark it.
				$info[$iprime]++;
				$prime = true;
			}
			// We checked for a major increase AND a prime increase, and found nothing, so we're done!
			else {
				break;
			}
		}
	}

	// If we're forcing the current version to re-run, add it now.
	if ( $force_cur ) {
		$filename = $path_to_updates.'/version_'.implode('_', $upinfo).'.php';
		if ( file_exists($filename) ) {
			$versions[implode('.', $upinfo)] = $filename;
		}
	}

	return $versions;
}

function version_info_gte_update ( $info, $upinfo ) {
	// Convert to a number (remove decimals) and check value.
	$numinfo = implode('', $info);
	$numup = implode('', $upinfo);
	return( (int)$numinfo > (int)$numup );
}

function version_gte_update ( $cur, $update ) {
	return version_info_gte_update( explode('.', $cur), explode('.', $update) );
}

function output_string ( $msg, $linebreak = true ) {
	echo $msg .($linebreak ? "\n" : "");
}