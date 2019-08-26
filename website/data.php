<?php
include('resources/constants.php');

$pageTitle 		= DEFAULT_PAGE_TITLE . PAGE_TITLE_SEPARATOR . "Zugriffszahlen";
$pageCurrent 	= "data";

include(TEMPLATE_FOLDER . 'header.html');
include(TEMPLATE_FOLDER . 'topnav.html');
include(TEMPLATE_FOLDER . 'sidenav.html');

?>

    <!-- page content -->

    <h1 class="page-header">Zugriffszahlen</h1>

    <div class="table-responsive">
        <table class="table-striped" data-toggle="table" id="events-id2" data-url="<?php echo API_PATH ?>reports/basic.json?granularity=total&content=counter,counter_abstract,robots,robots_abstract&from=2000-01-01&until=yesterday&jsonheader=false<?php echo $_SESSION['currentrep']!=0 ? '&id='.$_SESSION['currentrep'] : ''; ?>" data-pagination="true" data-search="true">
            <thead>
            <tr>
                <th data-field="operate" data-width="10" data-formatter="operateFormatter" data-events="operateEvents"></th>
                <th data-field="identifier" data-sortable="true">Document Identifier</th>
                <th data-field="counter" data-sortable="true">Fulltext Downloads</th>
                <th data-field="counter_abstract" data-sortable="true">Abstract Views</th>
                <th data-field="robots" data-sortable="true">Robots (Downloads)</th>
                <th data-field="robots_abstract" data-sortable="true">Robots (Views)</th>
            </tr>
            </thead>
        </table>
    </div>
    <div id="graph-container"></div>

    <!-- end page content -->

<?php
include(TEMPLATE_FOLDER . 'jsfiles.html');
?>
    <script>
        // data table function - operation for showing graph in data table
        window.operateEvents = {
            'click .showGraph': function (e, value, row, index) {
                // remove previous graph if existing
                $( "#graph" ).remove();

                // create new graph
                var $graph = $("<div/>", {id:"graph"});

                // create header for graph
                var $header = $("<h2/>", {text: "Grafische Ausgabe für " + row['identifier']});

                $( "#graph-container").append($graph);

                $graph.append($header);
                $graph.chart({
                    type: "bar",
                    jsonLoader : "resources/oaswidget/jsonloader.php",
                    identifier: row['identifier'],
                    from: '2010-01-01',
                    showCounter: true,
                    showCounterAbstract: true,
                    showRobots: true,
                    showRobotsAbstract: true,
                    showToolbar: true,
                    showToolbarDateSelector: true,
                    showToolbarDetails: true,
                    showToolbarCumulated: true,
                    showWaitingAnimation: false,

                    toolbarDateSelectorOptions: {
                        granularityEditable:    true,  //enable the edit-box
                        timespanEditable:       true   //enable from-until
                    },
                    toolbarDetailsOptions: {
                        showIdentifier: true,
                        showFirstAccess: true
                    },
                    toolbarCumulatedOptions: {
                        statisticsToggleable: true,
                        showAll: true
                    },
                    requests: {
                        rep: CURRENT_REP
                    }
                } );
            }
        };
    </script>

<?php
include(TEMPLATE_FOLDER . 'footer.html');
?>