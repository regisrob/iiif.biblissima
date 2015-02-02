<?php
// Connect to Mongodb
try {
  $client = new MongoClient(); // connect to mongo
  $db = $client->selectDB("manifests"); // select database
}
catch(Exception $e){
    echo $e->getMessage();
}
?>