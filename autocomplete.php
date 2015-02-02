<?php
require('include/mongo_connect.php');

// get user input sent by ajax
$term = $_GET['term'];

/*
 * Method 1: search with regex and collection->find
 */

/*
$coll = $db->selectCollection("prototype_IM"); // select collection
$regexObj = new MongoRegex("/$term/i"); // regex


// fields for which to search
$query = array(
  'label' => $regexObj
);

//$where = array('name' => array('$regex' => new MongoRegex("/^$search/i")));

// Fields to return
$projection = array(
  'label' => true
);

// Find in collection
$cursor = $coll->find( $query, $projection );
*/
// fin Method 1


/*
 * Method 2: full-text indexed search
 * NB : Mongodb 1.4.4-3 does not support it
 */

$results = $db->command(
  array(
    'text' => 'prototype_IM', // collection where we are searching
    'search' => "$term", // string to search
    //'limit' => 5, // number of results (default: 1000)
    //'language' => 'fr',
    'project' => Array( // fields to retrieve from db
      'label' => 1
      //'_id'   => 0
    )
  )
);
// fin Method 2

/*
 * Format and print results
 */

// Specific response format for jquery-autocomplete
/*$results = array(
  "query" => "Unit",
  "suggestions" => array()
);

foreach ($cursor as $doc) {
  unset($doc['_id']);
  $doc = implode( array_values( $doc ) );
  array_push( $results['suggestions'], $doc);
  //echo $doc['label'];
}*/

// Response format for typeahead.js
$response = array();  

// data coming from Method 1
/*foreach ($cursor as $doc) {
  //unset($doc['_id']);
  //$doc['_id'] = $doc['_id'].$id; // $id raises php Notice: undefined variable
  $doc['_id'] = $doc['_id']->{'$id'}; // this one is correct
  //$doc['_id'] = implode( array_values( $doc['_id'] ) );
  //$doc = implode( array_values( $doc ) );
  array_push( $response, $doc);
}*/

// data coming from Method 2
$results = $results['results'];
foreach ($results as $result) {
  //$result = array(
  //  "label" => implode( array_values($result['obj']) )
  //);
  //$result = implode( array_values($result['obj']) );
  $result = $result['obj'];
  //$result['_id'] = $result['_id'].$id;
  $doc['_id'] = $doc['_id']->{'$id'};
  array_push( $response, $result);
}

echo json_encode( $response );

// close mongo connection
$client->close();
?>