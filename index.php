<?php
require('include/mongo_connect.php');
require_once('common.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>IIIF Manifests Repository - Biblissima</title>
  
  <meta name="description" content="">
    
  <link href="<?php echo $staticBaseUrl; ?>libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?php echo $staticBaseUrl; ?>/css/style.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="css/typeaheadjs.css" rel="stylesheet">
  <link href="css/zebra_pagination.css" rel="stylesheet">
  
  <link rel="shortcut icon" href="favicon.ico">
  <link rel="icon" type="image/png" href="favicon.png">
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
  <![endif]-->
</head>
<body>
  <nav class="navbar navbar-default" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand logo-biblissima pfm" href="http://www.biblissima-condorcet.fr" title="Site web de Biblissima"><span>biblissima</span></a> <p class="navbar-text">IIIF Manifests Repository</p>
    </div>
  </nav>
  
  <div class="container content">
  
  <?php
  // select Mongo collection
  $coll = $db->selectCollection("prototype_IM");
  //echo "Collection de travail : " . $coll->getName() . ".\n";

  /*echo "<pre>";
  print_r(iterator_to_array($cursor));
  echo "</pre>";*/

  $records_per_page = 10;

  // include pagination class
  require 'Zebra_Pagination.php';

  // instantiate the pagination object
  $pagination = new Zebra_Pagination();

  // fetch docs in db->coll with limit and skip
  $limit = $records_per_page;
  $skip = ($pagination->get_page() - 1) * $records_per_page;

  $cursor = $coll->find()->limit( $limit )->skip( $skip );

  // count the total number of docs
  $rows = $cursor->count();

  // pass the total number of records to the pagination class
  $pagination->records( $rows );

  // records per page
  $pagination->records_per_page( $records_per_page );
  ?>
  
  <div class="page-header">
    <h1>IIIF Manifests Repository at Biblissima <small>(work-in-progress)</small></h1>
  </div>
  
  <div class="well">
    <p>This is a first test of a web interface for a <strong>manifests repository at Biblissima</strong> (<span class="text-danger">work-in-progress</span>). Initially this demo presents a selection of digital objects from Gallica available via IIIF standards. It is part of the Initiale/Mandragore prototype on medieval illuminations (work-in-progress too).</p>
    <p class="text-info">Total number of objects = <strong><?php echo $rows; ?></strong></p>
  </div>
  
  <form id="form-search" class="center-block" method="post" action="">
    <div class="input-group">
      <input class="typeahead form-control input-lg" type="text" placeholder="Enter a shelfmark (e.g. Arabe 1184)" id="searchByShelfmark" autocomplete="off">
      <p class="help-block">Start typing a numeric shelfmark (e.g. Arabe 1184, or just 1184)</p>
    </div>
  </form>

<div id="item-ahead"></div>

<div class="results">

  <?php

  // render the pagination links
  // $pagination->render();

  // fetch docs from db
  foreach ($cursor as $doc) {
  ?>
    <div class="item clearfix">
      <div class="item-img">
        <div class="thumbnail">
          <img src="<?php echo $doc['thumbnail']['@id']; ?>" alt="">
        </div>
        <small><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>&nbsp; <a href="<?php echo $doc['related']; ?>" target="_blank">View in Gallica</a></small>
      </div>
      <div class="item-logo">
        <img src="<?php echo $doc['logo']; ?>" alt="" width="90" height="90">
      </div>
      <div class="item-text">
        <h3><?php echo $doc['label']; ?></h3>
        <p class="text-muted description">
          <em>
            <?php
              $titre = $doc['metadata'][2]['value'];
              if(is_array($titre)) {
                foreach( $titre as $val ) {
                   echo $val . "<br />";
                }
              } else {
                echo $titre;
              }
            ?>
          </em>
        </p>
        <p>Manifest : <a href="<?php echo $doc['@id']; ?>" target="_blank"><?php echo $doc['@id']; ?></a></p>
        
        <form action="viewer.php" method="post" target="_blank">
          <button type="submit" class="btn btn-default" data-id="<?php echo $doc['@id']; ?>"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>&nbsp; Open in Mirador</button>
          <input type="hidden" name="manifest" value="<?php echo $doc['@id']; ?>" />
          <input type="hidden" name="label" value="<?php echo $doc['label']; ?>" />
        </form>
      </div>
    </div>
  <?php
  }

  // render the pagination links
  $pagination->render();
  
  // close mongo connection
  $client->close();
  ?>
  
</div><!-- /.results -->

  </div><!-- /.container -->
  
  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <p class="navbar-text navbar-right">This repository is compliant with <abbr title="International Image Interoperability Framework">IIIF</abbr> APIs &nbsp; <a href="http://iiif.io"><img src="images/iiif-logo.png" width="34" height="30" alt="IIIF logo"></a></p>
    </div>
  </footer>
  
<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="js/jquery.min.js"></script>
<script src="js/typeahead.bundle.min.js"></script>
<script src="js/handlebars.min.js"></script>
<script src="js/zebra_pagination.js"></script>
<script src="<?php echo $staticBaseUrl; ?>libs/bootstrap/js/bootstrap.min.js"></script>

<!-- HANDLEBARS TEMPLATE -->
<script id="item-template" type="text/x-handlebars-template">
  <div class="item clearfix">
    <div class="item-img">
      <div class="thumbnail">
        <img src="{{thumbnail.[@id]}}" alt=""></img>
      </div>
      <small><span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>&nbsp; <a target="_blank" href="{{related}}">View in Gallica</a></small>
    </div>
    <div class="item-logo">
      <img src="{{logo}}" height="90" width="90" alt="">
    </div>
    <div class="item-text">
      <h3>{{label}}</h3>
      <p class="text-muted"><em>{{metadata.[2].value}}</em></p>
      <p>Manifest : <a target="_blank" href="{{[@id]}}">{{[@id]}}</a></p>
      <form action="viewer.php" method="post" target="_blank">
        <button type="submit" class="btn btn-default" data-id="{{[@id]}}"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>&nbsp; Open in Mirador</button>
        <input type="hidden" name="manifest" value="{{[@id]}}" />
        <input type="hidden" name="label" value="{{label}}" />
      </form>
    </div>
  </div>
</script>
<script>
$(document).ready(function(){
  /*
   * Handlebars
   */
  var source = $("#item-template").html();
  var template = Handlebars.compile(source);
  var itemAheadDiv = $("#item-ahead");
 
  /*
   * jquery-autocomplete
   */
  
  /*$("#searchByShelfmark").autocomplete({
    serviceUrl: 'autocomplete.php',
    minChars: 3,
    dataType: 'json'
  });*/

  /*
   * Typeahead.js
   */

  // Bloodhound
  var labels = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    //prefetch: 'autocomplete.php?term=%term',
    remote: 'autocomplete.php?term=%QUERY',
    limit: 15
  });

  labels.initialize();

  // autocomplete field
  $('#searchByShelfmark').typeahead({
    hint: true,
    highlight: true,
    minLength: 3
  },
  {
    name: 'labels',
    displayKey: 'label', // default: 'value'
    //source: substringMatcher(states)
    source: labels.ttAdapter()
  });

  // after clicking a label in the suggestions list
  $('#searchByShelfmark').on('typeahead:selected', function (object, datum) {
    //console.log(object);
    
    // empty input
    $('#searchByShelfmark').val('').focus();

    // Datum containg value, tokens, and custom properties
    //console.log(datum._id);
    
    // get item data based on MongoId
    $.ajax({
      url : 'get_item.php',
      data: 'id='+datum._id,
      dataType : 'json'
    }
    ).done(function(data) {
      //console.log(data['@id']);
      
      // slideup #itemAhead if it already has contents
      if( $(itemAheadDiv).length > 0 ){
        $(itemAheadDiv).slideUp(200, function(){
          $(this).empty();
          $(this).html(template(data));
        });
        $(itemAheadDiv).delay(400).slideDown(400);
      }else {
        // insert handlebars template
        $(itemAheadDiv).html(template(data));
        $(itemAheadDiv).delay(400).slideDown(400);
      }
    });
  });
});
</script>
</body>
</html>