<?php
include('resources/constants.php');

$pageTitle      = DEFAULT_PAGE_TITLE . PAGE_TITLE_SEPARATOR . "API-Dokumentation";
$pageCurrent    = "apidoc";

include(TEMPLATE_FOLDER . 'header.html');
include(TEMPLATE_FOLDER . 'topnav.html');
include(TEMPLATE_FOLDER . 'sidenav.html');

?>

<!-- page content -->

    <h1 class="page-header">API-Dokumentation</h1>
    <div class="swagger-section">
      <div id="message-bar" class="swagger-ui-wrap">&nbsp;</div>
      <div id="swagger-ui-container" class="swagger-ui-wrap"></div>
    </div>

<!-- end page content -->

<?php
include(TEMPLATE_FOLDER . 'jsfiles.html');
?>
  <!-- these scripts are only necessary for the api-documentation, no need to load the whole bunch for every page -->
  <script src="<?=APIDOC_URL?>lib/shred.bundle.js"></script>
  <script src='<?=APIDOC_URL?>lib/jquery-1.8.0.min.js'></script>
  <script src='<?=APIDOC_URL?>lib/jquery.slideto.min.js'></script>
  <script src='<?=APIDOC_URL?>lib/jquery.wiggle.min.js'></script>
  <script src='<?=APIDOC_URL?>lib/jquery.ba-bbq.min.js'></script>
  <script src='<?=APIDOC_URL?>lib/handlebars-1.0.0.js'></script>
  <script src='<?=APIDOC_URL?>lib/underscore-min.js'></script>
  <script src='<?=APIDOC_URL?>lib/backbone-min.js'></script>
  <script src='<?=APIDOC_URL?>lib/swagger.js'></script>
  <script src='<?=APIDOC_URL?>swagger-ui.js'></script>
  <script src='<?=APIDOC_URL?>lib/highlight.7.3.pack.js'></script>



  <script type="text/javascript">
    // this is used in swagger-ui.js in the api docs path
    var APIDOCS_IMAGE_PATH  = API_DOCS_PATH + "images/";

    $(function () {
      window.swaggerUi = new SwaggerUi({
      url: "https://oase.gbv.de/api/v1/docs/api-docs",
      dom_id: "swagger-ui-container",
      supportedSubmitMethods: ['get', 'post', 'put', 'delete'],
      onComplete: function(swaggerApi, swaggerUi){
        log("Loaded SwaggerUI");

        if(typeof initOAuth == "function") {
          /*
          initOAuth({
            clientId: "your-client-id",
            realm: "your-realms",
            appName: "your-app-name"
          });
          */
        }
        $('pre code').each(function(i, e) {
          hljs.highlightBlock(e)
        });
      },
      onFailure: function(data) {
        log("Unable to Load SwaggerUI");
      },
      docExpansion: "list"
    });

    $('#input_apiKey').change(function() {
      var key = $('#input_apiKey')[0].value;
      log("key: " + key);
      if(key && key.trim() != "") {
        log("added key " + key);
        window.authorizations.add("key", new ApiKeyAuthorization("api_key", key, "query"));
      }
    })
    window.swaggerUi.load();
  });
  </script>
<?php
include(TEMPLATE_FOLDER . 'footer.html');
?>