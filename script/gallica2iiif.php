<?php
/* ================================================================
 *  
 *  @author : Régis Robineau
 *  @project : Equipex Biblissima
 *  @description : Create a JSON-LD manifest (IIIF Presentation API 2.0) for one Gallica object out of two webservices provided by the BnF (OAI-PMH and Pagination.xml), and send it to a MongoDB database
 *
 * ================================================================
*/
 
/* ======================================
 * ## GET ARK PARAMETER
 * ======================================
 */

//--- Get ark name from url param (in dev)
$ARK = $_GET['ark'];
$ark_array = explode("/", $ARK);
$ARK_NAME = $ark_array[2];

//--- Get ark name from url (in prod)
//$uri = explode("/", $_SERVER['REQUEST_URI']);
//$ARK_NAME = $uri[4];

//--- Get full ark identifier from url (build manifest from url for any Gallica object)
//$ark_array = array($uri[2], $uri[3], $uri[4]);
//$ARK = implode("/", $ark_array);


/* ======================================
 * ## DATA INPUT METHODS
 * ======================================
 */
 
/* 
 * From CSV
 */
//$csvMethodIsEnabled  = true;
$csvFile = "all_mss_traite.csv";

$csvData = readCSV( $csvFile );
$EADID = getEadIdfromCsv( $csvData, $ARK_NAME);

/* 
 * From Sparql (data.bnf)
 */
 
//--- Get BAM url
//$requestURL = getUrlBam( $ARK_NAME );
//$responseArray = json_decode( request($requestURL), true);
//$BAM_URL = $responseArray['results']['bindings'][0]['urlBam']['value'];

//--- Get workManifested uri
// TODO...?

//--- Get marcrel:aut uri
// TODO..?


/* ======================================
 * ## FUNCTIONS
 * ======================================
 */

/* 
 * Read and retrieve CSV data
 */
function readCSV( $input ){
  $file = fopen($input,"r");
  $csv = array();
  while (($row = fgetcsv($file)) !== false) {
    $csv[] = $row;
  }
  fclose($file);
	return $csv;
}

/* 
 * Build label/value pairs for a given field
 */
function setMdField( &$field, $label, $value ) {
  $field["label"] = $label;
  $field["value"] = $value;  
  return $field;
}

/* 
 * Get EAD ID from CSV file
 */
function getEadIdfromCsv( $csv, $idArk ) {
  foreach( $csv as $item ) {
    
    $eadId = $item[1];
    $arkName = $item[2];
    
    if( $arkName == $idArk ) {
      return $eadId;
    }
  }
}

/* 
 * Request with Curl and return data
 */
function request( $url ) {
  // is curl installed?
  if (!function_exists('curl_init')){
    die('CURL is not installed!');
  }
  
  // get curl handle
  $ch = curl_init();
  
  // set request url
  curl_setopt($ch,
    CURLOPT_URL,
    $url);
  
  // return response, don't print/echo
  curl_setopt($ch,
    CURLOPT_RETURNTRANSFER,
    true);
  
  // More options for curl: http://www.php.net/curl_setopt	
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

/* 
 * Query data.bnf Sparql endpoint to retrieve BAM URL for a given ARK name
 */
function getUrlBam( $arkName ) {
  
  $format = 'application/sparql-results+json';
  
  $sparqlQuery =
  "SELECT ?urlBam
  WHERE {
    ?p <http://www.w3.org/2000/01/rdf-schema#seeAlso> ?urlBam .
    ?p <http://rdvocab.info/RDARelationshipsWEMI/electronicReproduction> <http://gallica.bnf.fr/ark:/12148/".$arkName."> .
    FILTER regex(?urlBam,'^http://archivesetmanuscrits','i')
  }";
  
  $queryUrl = 'http://data.bnf.fr/sparql?default-graph-uri='
  .'&query='.urlencode($sparqlQuery)
  .'&format='.urlencode($format)
  .'&timeout=0&debug=off';
  
  return $queryUrl;
}

/* 
 * Convert object to array recursively
 */
function object_to_array($obj) {
  if(is_object($obj)) $obj = (array) $obj;
  if(is_array($obj)) {
    $new = array();
    foreach($obj as $key => $val) {
      $new[$key] = object_to_array($val);
    }
  }else $new = $obj;
  return $new;
}


/* ======================================
 * ## XML DATA SOURCES
 * ======================================
 */

$PAGINATION_URL = "http://gallica.bnf.fr/services/Pagination?ark=".$ARK;
$OAI_RECORD_URL = "http://oai.bnf.fr/oai2/OAIHandler?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:bnf.fr:gallica/".$ARK;

//--- Load xml files w/ SimpleXML
$pagination_xml = simplexml_load_file("$PAGINATION_URL");
$oai_record_xml = simplexml_load_file("$OAI_RECORD_URL");


/* ======================================
 * ## CONSTANTES
 * ======================================
 */

$CONTEXT_PREZ = "http://iiif.io/api/presentation/2/context.json";
$CONTEXT_IMAGE = "http://library.stanford.edu/iiif/image-api/1.1/context.json";
$PROFILE_IMAGE = "http://library.stanford.edu/iiif/image-api/1.1/compliance.html#level2";
$IIIF_BASE_URI = "http://gallica.bnf.fr/iiif/" . $ARK;
$IMAGE_BASE_URI = "http://gallica.bnf.fr/" . $ARK;
$IMAGE_QUALITY = "native.jpg";
$GALLICA_URL = $IMAGE_BASE_URI;

$MANIFEST_URI     = "http://iiif.biblissima.fr/manifests/". $ARK ."/manifest.json";
$SEQUENCE_URI     = $IIIF_BASE_URI . "/sequence/normal";
$CANVAS_BASE_URI  = $IIIF_BASE_URI . "/canvas";


/* ======================================
 * ## PRESENTATION RESOURCE PROPERTIES
 * ======================================
 */

/* 
 * Get metadata from OAI-DC
 */

$ns = $oai_record_xml->getNamespaces(true);
$nsUriOaiDc = $ns['oai_dc'];
$nsUriDc    = $ns['dc'];
//$nsXml      = $ns['xml'];

$oai_record_metadata = $oai_record_xml->GetRecord->record->metadata;
$metadata = $oai_record_metadata->children($nsUriOaiDc);
$dc = $metadata->children($nsUriDc);

$dc = object_to_array($dc); // convert simpleXml objects to array

$title = $dc['title'];
$date = $dc['date'];
$language = $dc['language'];
$identifier = $dc['identifier']; // gallica link
$title = $dc['title']; // used for mf description
$source = $dc['source']; // shelfmark (used for mf label)
$format = $dc['format'];
//$description = $dc->description;
//$rights = $dc['rights'];
$rights = "public domain";
$type = $dc['type'];
$creator = $dc['creator'];
$contributor = $dc['contributor'];

$oaiFields = array(
  "Title"       => $title,
  "Date"        => $date,
  "Language"    => $language,
  "Creator"     => $creator,
  "Contributor" => $contributor
);


/* 
 * Manifest label : custom formatted shelfmark
 */

//if( !empty($source) ) {
if( $source != '' ) {

  $cote = explode(",", $source);
  
  // Differents cas de figure dans la notation de la cote
  // cote abregee pour le label du manifest = $cote_bib, $cote_depot, $shelfmark
  
  // Cas pour 1ere partie de la cote
  // si commence par "Bibliothèque nationale de France" 
  if( preg_match("#Bibliothèque nationale de France#i", $cote[0]) ) {
    $cote_bib = "Paris, BnF, ";
    $repository = "Bibliothèque nationale de France, ";
  // sinon si contient "Arsenal"
  }elseif( preg_match("#Arsenal#", $cote[0]) ) {
    $cote_bib = "Paris, BnF, Bibliothèque de l'Arsenal";
    $repository = "Bibliothèque nationale de France, Bibliothèque de l'Arsenal";
  // sinon si commence par "Département des Manuscrits"
  }elseif( preg_match("#^Département des Manuscrits#i", $cote[0]) ) {
    $cote_bib = "Paris, BnF, MSS ";
    $repository = "Bibliothèque nationale de France, Département des Manuscrits";
  // sinon on mise par defaut sur le Département des Manuscrits
  } else {
    $cote_bib = "Paris, BnF, MSS ". $cote[0];
    $repository = "Bibliothèque nationale de France, Département des Manuscrits";
  }
  
  // Cas pour 2e partie de la cote
  // si contient "Département des Manuscrits"
  if( preg_match("#Département des Manuscrits#i", $cote[1]) ) {
    $cote_depot = "MSS";
    $repository .= "Département des Manuscrits";
  // sinon si contient "département Arsenal"
  }elseif( preg_match("#département Arsenal#i", $cote[1]) ) {
    $cote_depot = "Arsenal";
    $repository .= "Bibliothèque de l'Arsenal";
  // sinon 
  }else {
    $cote_depot = $cote[1];
  }
  
  // Cas pour 3e partie de la cote
  // if not empty or not null
  if( !empty($cote[2]) ) {
    $shelfmark = trim($cote[2]); // Numeric shelfmark (fonds + number)
    $mfLabel = "$cote_bib $cote_depot $shelfmark"; // Manifest label
  }elseif( !empty($cote[1]) ) {
    $shelfmark = trim($cote[1]);
    $mfLabel = "$cote_bib $cote_depot";
  }else {
    $shelfmark = trim($cote[0]);
    $mfLabel = "$cote_bib";
  }
  
}else { // if no dc:source
  $mfLabel = $title;
  $shelfmark = "n/a";
  $repository = "Bibliothèque nationale de France";
}

/* 
 * Metadata fields (Manifest level)
 */

// Metadata field array
$mfMetadata = array();

// Repository
//$repository = "$cote[0], $cote[1]";
$mdRepository = array();
setMdField( $mdRepository, "Repository", $repository );

// Shelfmark
$mdShelfmark = array();
setMdField( $mdShelfmark, "Shelfmark", $shelfmark );

array_push( $mfMetadata, $mdRepository, $mdShelfmark );

// Build label/value pairs for each OAI field
foreach( $oaiFields as $label => $value ) {
  if( !empty($value) ) {
    if( count($value) > 1 ) { // if multiple values
      //$value = (array)$value;
      $array_val = array();
      foreach( $value as $val) {
        array_push($array_val, $val);
        $mdField = array(
          "label" => $label,
          "value" => $array_val
        );
      }
    }else {
      $mdField = array(
        "label" => $label,
        "value" => "$value"
      );
    }
    array_push( $mfMetadata, $mdField );
  }
}

// Provider
$provider = array();
setMdField( $provider, "Provider", "Bibliothèque nationale de France" );

// Disseminator
$disseminator = array();
setMdField( $disseminator, "Disseminator", "Biblissima" );

// Source images
$sourceImages = array();
setMdField( $sourceImages, "Source Images", $GALLICA_URL );

array_push( $mfMetadata, $provider, $disseminator, $sourceImages );
//echo json_encode( $mfMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);


/* 
 * Other properties (Manifest level)
 */

if( !empty($title) ) {
  $description = $title;
}

if( !empty($rights) ) {
  $attribution = "BnF - ". $rights ."";
}
  
$thumbnail = array(
  "@id" => $IIIF_BASE_URI . "/f1/full/,150/0/" .$IMAGE_QUALITY,
  "service" => array(
    "@context"  =>  $CONTEXT_IMAGE,
    "profile"   =>  $PROFILE_IMAGE,
    "@id"       =>  $IIIF_BASE_URI . "/f1"
  )
);

$logo = "http://static.biblissima.fr/images/bnf-logo.jpg";
$license = "https://creativecommons.org/publicdomain/zero/1.0/";

if( !empty($EADID) ) {
  $BAM_URL = "http://archivesetmanuscrits.bnf.fr/ead.html?id=".$EADID;
  $related = array(
    array(
      "@id" => $GALLICA_URL,
      "format" => "text/html"
    ),
    array(
      "@id" => $BAM_URL,
      "format" => "text/html"
    )
  );
}else {
  $related = "$GALLICA_URL";
}

$seeAlso = array(
  "@id"     => $OAI_RECORD_URL,
  "format"  => "application/xml"
);

$mfProperties = array(
  "description" => (string)$description,
  "attribution" => $attribution,
  "thumbnail"   => $thumbnail,
  "logo"        => $logo,
  "license"     => $license,
  "related"     => $related,
  "seeAlso"     => $seeAlso,
  "viewingHint" => "paged"
);


/* ======================================
 * ## PRIMARY RESOURCE TYPES
 * ======================================
 */

/* 
 * Canvases from Pagination
 */

$pages = $pagination_xml->pages;

$canvases = array( "canvases" => array() );

foreach($pages->page as $page) {
  
  $canvasLabel  = $page->numero;
  $imageWidth   = $page->image_width;
  $imageHeight  = $page->image_height;
  $ordreImg     = $page->ordre;
  
  $images = array(
    "@type"       => "oa:Annotation",
    "motivation"  => "sc:painting",
    "resource"    => array(
      "@id"     => $IMAGE_BASE_URI . "/f" . $ordreImg . ".highres",
      "format"  => "image/jpg",
      "@type"   => "dctypes:Image",
      "service" => array(
        "@context"  => $CONTEXT_IMAGE,
        "profile"   => $PROFILE_IMAGE,
        "@id"       => $IIIF_BASE_URI . "/f" . $ordreImg,
        "width"     => (int)$imageWidth,
        "height"    => (int)$imageHeight
      )
    ),
    "on"  => $CANVAS_BASE_URI . "/f" . $ordreImg
  );
  
  $canvas = array(
    "@id"   => $CANVAS_BASE_URI . "/f" . $ordreImg,
    "@type" => "sc:Canvas",
    "label" => "$canvasLabel",
    "width" => (int)$imageWidth,
    "height" => (int)$imageHeight,
    "images"  => array(),
  );
  
  $images["resource"]["width"] = (int)$imageWidth;
  $images["resource"]["height"] = (int)$imageHeight;

  array_push( $canvas['images'], $images );
  array_push( $canvases['canvases'], $canvas );
}

/* 
 * Sequence
 */

$sequences = array(
  "sequences" => array(
    "@id"     => $SEQUENCE_URI,
    "@type"   => "sc:Sequence",
    "label"   => "Normal"
));

$sequences = $sequences['sequences'] + $canvases;

/* 
 * Manifest
 */

$manifest = array(
  "@context"  => $CONTEXT_PREZ,
  "@id"       => $MANIFEST_URI,
  "@type"     => "sc:Manifest",
  "label"     => "$mfLabel",
  "metadata"  => $mfMetadata,
  "sequences" => array($sequences)
);

$manifest = array_merge( $manifest, $mfProperties );

// PHP >= 5.4
//$manifestJson = json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// PHP <= 5.4
//$manifestJson = str_replace('\\/', '/', json_encode($manifest));
//$manifestJson = mb_convert_encoding($manifest, 'UTF-8');

//echo $manifestJson;

/* 
 * ====== Write manifest to disk
 */

//--- Create directory named with ark name
/*$mf_dirname = $ARK_NAME;
if (!is_dir($mf_dirname)) {
  mkdir($mf_dirname, 0750);
}*/

//--- Write manifest.json into the appropriate folder
/*$mf_filename = 'manifest.json';
file_put_contents("$mf_dirname/$mf_filename", $manifestJson);*/


/* 
 * ====== Insert into MongoDB
 */

$m = new MongoClient(); // connect to mongo
$db = $m->selectDB("manifests"); // select database
$coll = $db->selectCollection("prototype_IM"); // select collection
//echo "Collection de travail : " . $coll->getName() . ".\n";
//$item = $coll->findOne();
//echo "ID manifest : " . $item['@id'];

$criteria = array( '@id' => $MANIFEST_URI );
$cursor = $coll->find( $criteria );

// If manifest already in db
if( $cursor->count() > 0 ) {
  $coll->update( $criteria, $manifest );
} else{
  $coll->insert( $manifest ); // insert manifest array as json
}

?>
