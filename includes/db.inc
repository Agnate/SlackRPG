<?php

// Create connection
$pdo = new PDO ("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);


function pdo_prepare ( $query ) {
	global $pdo;

	return $pdo->prepare($query);
}

function get_pdo () {
	global $pdo;

	return $pdo;
}