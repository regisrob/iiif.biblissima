<?php
// get Manifest uri
//$manifestURI = htmlentities( $_GET['manifest'], ENT_QUOTES );
if(isset($_POST['manifest']) && isset($_POST['label']) ) {
  $manifestURI = htmlentities( $_POST['manifest'], ENT_QUOTES );
  $label = htmlentities( $_POST['label'], ENT_QUOTES );
}
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <link rel="stylesheet" type="text/css" href="js/m2/build/mirador/css/mirador-combined.css">
  <title><?php echo $label; ?> - Mirador Viewer</title>
  <style type="text/css">
    body { padding: 0; margin: 0; overflow: hidden; font-size: 70%; }
    #viewer { background: #333 url(js/m2/build/mirador/images/debut_dark.png) left top repeat; width: 100%; height: 100%; position: fixed; }
 </style>
</head>
<body>
  <div id="viewer"></div>

  <script src="js/m2/build/mirador/mirador.js"></script>
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
        "currentWorkspaceType": "singleObject",
        "workspaceAutoSave": false,
        //"workspaceType": "singleObject",
        //"layout": "1x2",
        "mainMenuSettings" : {"show": false, "buttons" : {"bookmark" : false, "layout" : false}},
        //'showAddFromURLBox' : false,
        "data": [
          { "manifestUri": "<?php echo $manifestURI ?>", "location": "BnF"}
          //{ "manifestUri": "http://iiif.biblissima.fr/manifests/ark:/12148/btv1b53014833h/manifest.json", "location": "BnF"},
          //{ "manifestUri": "http://oculus-dev.harvardx.harvard.edu/manifests/huam:198021", "location": "Harvard University"}
          //{ "manifestUri": "http://iiif.biblissima.fr/manifests/ark:/12148/btv1b53014833h/manifest.json", "location": "BnF"},
        ],
        "windowObjects": [
        {
          "loadedManifest": "<?php echo $manifestURI ?>",
          //"loadedManifest": "http://oculus-dev.harvardx.harvard.edu/manifests/huam:198021"
          //"viewType" : "ImageView", 
        	// "canvasID": "http://dms-data.stanford.edu/data/manifests/Walters/qm670kv1873/canvas/canvas-12",
        	//"bottomPanel" : true,
        	//"sidePanel" : false,
        	//"availableViews" : ['ThumbnailsView', 'ImageView', 'BookView'],
          "displayLayout" : false
        	//"overlay" : false
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
