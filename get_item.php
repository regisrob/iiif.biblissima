<?php
require('include/mongo_connect.php');

// get MongoId sent by ajax
$id = $_GET['id']; 

// select mongo collection
$coll = $db->selectCollection("prototype_IM");

// fields for which to search
$query = array(
  '_id' => new MongoId($id)
);

// Fields to return
$projection = array(
  '_id'       => 0, // Mongodb 1.4.4-3 does not support this
  'label'     => true,
  'thumbnail' => true,
  'logo'      => true,
  'metadata'  => true,
  'related'   => true,
  '@id'       => true
);

// for Mongodb 1.4.4-3 only (does not support exclude option)
//unset($doc['_id']);

// Find the doc based on its MongoId
$doc = $coll->findOne( $query, $projection );

// Serialize as json
echo json_encode( $doc );

// close mongo connection
$client->close();
?>