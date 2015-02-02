<?php

/* ================================================================
 *  
 *  @author : Régis Robineau
 *  @project : Equipex Biblissima
 *  @description : Create a JSON-LD manifest (IIIF Presentation API 2.0)
 *  for one Gallica object out of two webservices provided by the BnF
 *  (OAI and Pagination), and send it to a MongoDB database
 *
 * ================================================================
*/

/* Aides SimpleXML :
 * - http://www.sitepoint.com/parsing-xml-with-simplexml/
 * - http://www.sitepoint.com/simplexml-and-namespaces/
 * - http://php.net/manual/en/book.simplexml.php
 * - http://www.loria.fr/~abelaid/Enseignement/l3ScCo/PHP/Cours8-SimpleXML.pdf
 * */

/* 
 * ====== ARK identifier
 */

//--- Get ark name from url (dev only)
$ARK = $_GET['ark'];
$ark_array = explode("/", $ARK);
$ARK_NAME = $ark_array[2];

//--- Get ark name from url (in prod)
//$uri = explode("/", $_SERVER['REQUEST_URI']);
//$ARK_NAME = $uri[4];

//--- Get full ark identifier from url
//$ark_array = array($uri[2], $uri[3], $uri[4]);
//$ARK = implode("/", $ark_array);

//--- Create directory named with ark name
/*$mf_dirname = $ARK_NAME;
if (!is_dir($mf_dirname)) {
  mkdir($mf_dirname, 0750);
}*/

/* 
 * ====== XML SOURCE DATA
 */

$PAGINATION_URL = "http://gallica.bnf.fr/services/Pagination?ark=".$ARK;
$OAI_RECORD_URL = "http://oai.bnf.fr/oai2/OAIHandler?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:bnf.fr:gallica/".$ARK;

//--- Load xml files w/ SimpleXML
$pagination_xml = simplexml_load_file("$PAGINATION_URL");
$oai_record_xml = simplexml_load_file("$OAI_RECORD_URL");

/*
 * ====== CONSTANTES
 */

$CONTEXT_PREZ = "http://iiif.io/api/presentation/2/context.json";
$CONTEXT_IMAGE = "http://iiif.io/api/image/1/context.json";
$PROFILE_IMAGE = "http://iiif.io/api/image/1/level2.json";
$IIIF_BASE_URI = "http://gallica.bnf.fr/iiif/" . $ARK;
$IMAGE_BASE_URI = "http://gallica.bnf.fr/" . $ARK;
$IMAGE_QUALITY = "native.jpg";
$GALLICA_URL = $IMAGE_BASE_URI;

$MANIFEST_URI     = "http://iiif.biblissima.fr/manifests/". $ARK ."/manifest.json";
$SEQUENCE_URI     = $IIIF_BASE_URI . "/sequence/normal";
$CANVAS_BASE_URI  = $IIIF_BASE_URI . "/canvas";

/*
 * ====== PRESENTATION RESOURCE PROPERTIES
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

$date = $dc->date;
$language = $dc->language;
$identifier = $dc->identifier; // lien gallica
$title = $dc->title; // utilisé pour mf description
$source = $dc->source; // cote (utilisé pour mf label)
$format = $dc->format;
//$description = $dc->description;
$rights = $dc->rights;
$type = $dc->type;
$creator = $dc->creator;
$contributor = $dc->contributor;

$oaiFields = array(
  "Title"       => $dc->title,
  "Date"        => $dc->date,
  "Language"    => $dc->language,
  "Creator"     => $dc->creator,
  "Contributor" => $dc->contributor
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

// Build label/value pairs for a given field
function setMdField( &$field, $label, $value ) {
  $field["label"] = $label;
  $field["value"] = $value;  
  return $field;
}

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
      $value = (array)$value;
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

$logo = "https://raw.githubusercontent.com/IIIF/m2/master/images/logos/bnf-logo.jpeg";
$license = "https://creativecommons.org/publicdomain/zero/1.0/";
$related = $GALLICA_URL;
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

/* 
 * ====== PRIMARY RESOURCE TYPES
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
        "@context"  =>  $CONTEXT_IMAGE,
        "profile"   => $PROFILE_IMAGE,
        "@id"       => $IIIF_BASE_URI . "/f" . $ordreImg
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
$manifestJson = json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

// PHP <= 5.4
//$manifestJson = str_replace('\\/', '/', json_encode($manifest));
//$manifestJson = mb_convert_encoding($manifest, 'UTF-8');

//echo $manifestJson;

//--- Write manifest.json into the appropriate folder
//$mf_filename = 'manifest.json';
//file_put_contents("$mf_dirname/$mf_filename", $manifestJson);


/* 
 * ====== MongoDB management
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