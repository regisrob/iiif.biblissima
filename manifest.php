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
require('include/functions.php');

$baseUri = "http://iiif.biblissima.fr/manifests/";

//--- Get ark identifier from url parameter (dev only)
//$ARK = $_GET['ark'];
//$ark_array = explode("/", $ARK);
//$ARK_NAME = $ark_array[2];

//--- Get ark identifier from url (in prod)
$uri = explode("/", $_SERVER['REQUEST_URI']);
$ark_array = array($uri[2], $uri[3], $uri[4]); // server
//$ark_array = array($uri[4], $uri[5], $uri[6]); // localhost
$ARK = implode("/", $ark_array);

//--- Manifest @id
$mfId = $baseUri.$ARK."/manifest.json";

//-- List Mongo collections in db
$collections = $db->listCollections();

if ( !IsManifestInDb( $collections, $mfId ) ) {
  echo "Ce manifest n'existe pas dans la base de donn&eacute;es";
} else {
  $cursor = IsManifestInDb( $collections, $mfId );
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
}

?>