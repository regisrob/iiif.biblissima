<?php
/* 
 * Check if manifest is in db
 */
function IsManifestInDb( $collections, $id ) {
  
  foreach ($collections as $coll) {
    
    //echo $coll->count(), "\n";
    
    //--- MongoDB select collection
    //$coll = $db->selectCollection( $collection );

    // Selection criteria
    $criteria = array(
      '@id' => $id
    );

    // Projection operator to exclude _id field from Mongodb
    $projection = array(
      '_id' => 0 // not applied by Mongodb 1.4.4 (Debian 6): see workaround w/ unset
    );

    // Return a cursor of results with 'find'
    $cursor = $coll->find($criteria, $projection);

    // If manifest already in db
    if( $cursor->count() > 0 ) {
      // Function iterator_to_array() not appropriate because it returns an array...
      //echo json_encode(iterator_to_array($mf));

      return $cursor;
    }
  }
  
  return false;
}

?>