<?php
include('resources/constants.php');

$pageTitle      = DEFAULT_PAGE_TITLE . PAGE_TITLE_SEPARATOR . "Übersicht";
$pageCurrent    = "index";

include(TEMPLATE_FOLDER . 'header.html');
include(TEMPLATE_FOLDER . 'topnav.html');
include(TEMPLATE_FOLDER . 'sidenav.html');

?>

<!-- page content -->

		<h1 class="page-header">Übersicht</h1>
          <div class="smalltablewidth">
            <table id="info-table"></table>
          </div>
          <br />

          <h2 class="nobottommargin">Gesamtzugriffszahlen</h2><span id="earliestDate"></span>
          <div class="mediumtablewidth">
            <table class="table-striped" data-toggle="table" id="events-id2" data-url="<?php echo API_PATH ?>reports/basic.json?granularity=year&content=counter,counter_abstract&from=2000-01-01&until=yesterday&jsonheader=false&summarized=true<?php echo $_SESSION['currentrep']!=0 ? '&id='.$_SESSION['currentrep'] : ''; ?>">
                <thead>
                    <tr>
                        <th data-field="date" data-sortable="true">Jahr</th>
                        <th data-field="counter" data-sortable="true">Dokumentenaufrufe</th>
                        <th data-field="counter_abstract" data-sortable="true">Abstract Views</th>
                    </tr>
                </thead>
            </table>
          </div>
          <br />

          <div id="graph-container">
            <button id="graph-button" type="button" class="btn btn-default">Lade grafische Monatsübersicht</button>
          </div>


<!-- end page content -->

<?php
include(TEMPLATE_FOLDER . 'jsfiles.html');
?>

<script>
	$( document ).ready(function() {
      loadTable(CURRENT_REP);

      // creates iframe with graph
    $("#graph-button").click(function(){
        // remove previous graph if existing
        $( "#graph" ).remove();

        // create new graph
        var $graph = $("<div/>", {id:"graph"});

        $( "#graph-container").append($graph);

        $graph.chart({
            type: "line",
            jsonLoader : "resources/oaswidget/jsonloader.php",
            identifier: repData.default_identifier,
            from: repData.earliest_date,
            showCounter: true,
            showCounterAbstract: true,
            showRobots: true,
            showRobotsAbstract: true,
            showToolbar: false,
            showToolbarDateSelector:    false,
            showToolbarDetails:         true,
            showToolbarCumulated:       false,

            showWaitingAnimation :      true,

            toolbarDateSelectorOptions: {
                granularityDefault:     "month",
                granularityEditable:    false,  //enable the edit-box
                timespanEditable:       true   //enable from-until
            },

            requests: {
                rep: CURRENT_REP
            }
        } );


//      var graphIframeDiv  = document.getElementById('graph-iframe');
//      var graphIframeName = 'graphIframe';
//      var iframe  = document.createElement('iframe');
//      iframe.id   = graphIframeName;
//      iframe.src  = GRAPHPROVIDER_URL + '?identifier=' + repData.default_identifier + '&rep=' + CURRENT_REP;
//      graphIframeDiv.appendChild(iframe);
      $("#graph-button").remove();
    });
  });

// loads overview table
function loadTable(id) {
  // we add the id if necessary (for admin purposes)
  if(id == 0) {
    requestString = '';
  } else {
    requestString = '&id=' + id;
  }
  // create a table
  $('#info-table').bootstrapTable({
    url: API_PATH + 'index.php?do=status' + requestString,
    responseHandler: function(res) {
      var overviewData = [
        {
          key: 'Identifier-Anzahl',
          value: res.count_identifiers
        },
        {
          key: 'Dokumentenaufrufe (gesamt)',
          value: res.counter
        },
        {
          key: 'Abstract-Views (gesamt)',
          value: res.counter_abstract
        }
      ];
      repData = res;
      return overviewData;
    },
    cache: true,
    showHeader: false,
    striped: true,
    columns: [{
        field: 'key'
      },
      {
        field: 'value'
      }
    ]
  });

}

</script>

<?php
include(TEMPLATE_FOLDER . 'footer.html');
?>