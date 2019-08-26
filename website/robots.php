<?php
include('resources/constants.php');

$pageTitle 		= DEFAULT_PAGE_TITLE . PAGE_TITLE_SEPARATOR . "Robots";
$pageCurrent 	= "robots";

include(TEMPLATE_FOLDER . 'header.html');
include(TEMPLATE_FOLDER . 'topnav.html');
include(TEMPLATE_FOLDER . 'sidenav.html');

?>

<!-- page content -->

          <h1 class="page-header">Robot-List</h1>
          <p>Folgende regulären Ausdrücke werden bei der Filterung von User-Agents angewendet.</p>
          <div class="table-responsive">
            <table class="table-striped" data-toggle="table" data-url="<?php echo API_PATH ?>robots.json" data-pagination="false">
                <thead>
                    <tr>
                        <th data-field="useragent" data-sortable="true">Robot RegExp</th>
                        <th data-field="source" data-sortable="true">Quelle</th>
                        <th data-field="updated" data-sortable="true">Hinzugefügt</th>
                    </tr>
                </thead>
            </table>
          </div>

<!-- end page content -->

<?php
include(TEMPLATE_FOLDER . 'jsfiles.html');
include(TEMPLATE_FOLDER . 'footer.html');
?>