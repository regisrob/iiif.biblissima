<?php

/* ================================================================
 *  
 *  @author : Régis Robineau
 *  @project : Equipex Biblissima
 *  @description : Get a JSON-LD manifest for one Gallica object
 *  from a MongoDB database based on its @id
 *
 * ================================================================
*/

require('include/mongo_connect.php');

$baseUri = "http://iiif.biblissima.fr/manifests/";

//--- Get ark identifier from url parameter (dev only)
//$ARK = $_GET['ark'];
//$ark_array = explode("/", $ARK);
//$ARK_NAME = $ark_array[2];

//--- Get ark identifier from url (in prod)
$uri = explode("/", $_SERVER['REQUEST_URI']);
//$ARK_NAME = $uri[4];
//$ARK_NAME = $uri[5];
$ark_array = array($uri[2], $uri[3], $uri[4]);
$ARK = implode("/", $ark_array);

//--- Manifest @id
$mfId = $baseUri.$ARK."/manifest.json";

//--- MongoDB select collection
$coll = $db->selectCollection("prototype_IM");

// Selection criteria
$criteria = array(
  '@id' => $mfId
);

// Projection operator to exclude _id field from Mongodb
$projection = array(
  '_id' => 0 // not applied by Mongodb 1.4.4 (Debian 6): see the workaround below
);

// Return a cursor of results with 'find'
$cursor = $coll->find($criteria, $projection);

// If manifest already in db
if( $cursor->count() > 0 ) {
  // Function iterator_to_array() not appropriate because it returns an array...
  //echo json_encode(iterator_to_array($mf));

  // ... So loop through the only result and json_encode it to output the manifest
  foreach ($cursor as $doc) {
    
    // For PHP >= 5.4
    //echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    // For Mongodb 1.4.4 (current version on Debian 6)
    unset($doc['_id']); // remove _id field from result, since projection operator does not seem to work w/ Mongodb 1.4.4
    
    // For PHP <= 5.4 (json_encode constants not supported)
    $doc = str_replace('\\/', '/', json_encode($doc));
    //$doc = mb_convert_encoding($doc, 'UTF-8');
    echo $doc;
  }
} else{
  echo "Ce manifest n'existe pas dans la base de données";
}

?>