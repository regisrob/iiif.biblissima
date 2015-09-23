<?php
require('include/mongo_connect.php');
require('include/functions.php');

$baseUri = "http://iiif.biblissima.fr/manifests/";
$baseUriHttps = "https://iiif.biblissima.fr/manifests/";

//-- Params from POST
/*if(isset($_POST['manifest']) && isset($_POST['label']) ) {
  $mfId = htmlentities( $_POST['manifest'], ENT_QUOTES );
  $label = htmlentities( $_POST['label'], ENT_QUOTES );
}*/

//-- Params from url
$uri = explode("/", $_SERVER['REQUEST_URI']);
$naan = $uri[4]; //vserver
$ARK_NAME = $uri[5]; //vserver
if ( !empty($uri[6]) ) {
  $folio = $uri[6]; //vserver
}
//$naan = $uri[7]; // localhost
//$ARK_NAME = $uri[8]; // localhost

//-- If manifest from bnf
if( $naan == "12148" ) {
  $mfId = $baseUri."ark:/12148/".$ARK_NAME."/manifest.json";
  
  if( !empty($folio) ) {
    $startCanvas = "http://gallica.bnf.fr/iiif/ark:/12148/".$ARK_NAME."/canvas/".$folio;
  }
}

$mfUrl = $baseUriHttps."ark:/12148/".$ARK_NAME."/manifest.json";;

//-- List Mongo collections in db
$collections = $db->listCollections();

if ( !IsManifestInDb( $collections, $mfId ) ) {
  echo "Ce manifest n'existe pas dans la base de donn&eacute;es";
} else {
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <link rel="stylesheet" type="text/css" href="/manifests/js/mirador/build/mirador/css/mirador-combined.css">
  <title><?php //echo $label; ?>Mirador Viewer</title>
  <style type="text/css">
    body { padding: 0; margin: 0; overflow: hidden; font-size: 70%; }
    #viewer { background: #333 url(/manifests/js/mirador/build/mirador/images/debut_dark.png) left top repeat; width: 100%; height: 100%; position: fixed; }
 </style>
</head>
<body>
  <div id="viewer"></div>

  <script src="/manifests/js/mirador/build/mirador/mirador.js"></script>
  <script type="text/javascript">
    
    $(function() {

    /*var anno_token;
    jQuery.ajax({
      url: "http://oculus-dev.harvardx.harvard.edu/cgi-bin/token.py", 
      success: function(data) {
      anno_token = data.replace(/[\n\r]/g, '');
      },
      async:false
    });*/
      Mirador({
        "id": "viewer",
        "workspaceType": "singleObject",
        "saveSession": false,
        //"workspaceType": "singleObject",
        "layout": "1x1",
        "mainMenuSettings" : {"show": false, "buttons" : {"bookmark" : false, "layout" : false}},
        "showAddFromURLBox" : false,
        "data": [
          { "manifestUri": "<?php echo $mfUrl ?>", "location": "BnF"}
          //{ "manifestUri": "http://iiif.biblissima.fr/manifests/ark:/12148/btv1b53014833h/manifest.json", "location": "BnF"},
          //{ "manifestUri": "http://oculus-dev.harvardx.harvard.edu/manifests/huam:198021", "location": "Harvard University"}
          //{ "manifestUri": "http://iiif.biblissima.fr/manifests/ark:/12148/btv1b53014833h/manifest.json", "location": "BnF"},
        ],
        "windowObjects": [
        {
          "loadedManifest": "<?php echo $mfUrl ?>",
          <?php
            if( !empty($startCanvas) ) {
              echo " \"viewType\": \"ImageView\", ";
              echo " \"canvasID\": \"". $startCanvas ."\", ";
            }else{
              echo " \"viewType\": \"ThumbnailsView\", ";
            }
          ?>
          //"viewType" : "ImageView", 
        	// "canvasID": "http://dms-data.stanford.edu/data/manifests/Walters/qm670kv1873/canvas/canvas-12",
        	//"bottomPanel" : true,
        	//"sidePanel" : false,
        	//"availableViews" : ['ThumbnailsView', 'ImageView', 'BookView'],
          "displayLayout" : false,
        	//"overlay" : false
          "layoutOptions": {
            "newObject" : false,
            "close" : false,
            "slotRight" : false,
            "slotLeft" : false,
            "slotAbove" : false,
            "slotBelow" : false
          }
          }
        ]
        /*"annotationEndpoints": [
          {
            "name":"Harvard CATCH Dev",
            "module": "CatchEndpoint",
            "options": {
                token: anno_token,
                // The endpoint of the store on your server.
                prefix: "http://54.148.223.225:8080/catch/annotator",
            }
          }
        ]*/
      });
    });
  </script>
</body>
</html>
<?php 
} 
?>
