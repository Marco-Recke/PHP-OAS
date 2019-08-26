/**
 * JavaScript for retrieving OA- Statistics Data through jsonloader.
 * Uses morris.js. Loads morris.js-barchart into div with id "#oasgraph"
 *
 * Generates HTML
 * 
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */

function oasSetMessage(msg){
    if(msg !== ""){
        $("#oasgraph").html('<p id="oasmessage">'+msg+'</p>');
    }else{
        $("#oasgraph").html('<p id="oasmessage">The OAS server is down for maintenance. Please be patient, our service will be back up soon.</p>');
    }
}

function oasReset(){
     $("#oasgraph").empty();
}

function oasSetWaitingAnimation(){
    $('#oasgraph').html('<img id="oasmessage" src="./includes/pictures/gears.gif" width="64" height="47"/>');
}

function oasSetChart(arr){
    
    var entries    = arr['entries'];
    var definition = arr['entrydef'];

    //Define the requested items and axis-labels
    var ykeys  = [];
    var labels = [];

    //Look which items are given and show only the given ones.
    var i = 0;
    for (i = 0; i < definition.length; ++i) {
        var current = definition[i];

        switch(current) {
            case 'counter':
                ykeys.push('counter');
                labels.push('Downloads');
                break;

            case 'counter_abstract':
                ykeys.push('counter_abstract');
                labels.push('Abstract Downloads');
                break;

            case 'robots':
                ykeys.push('robots');
                labels.push('Robot Hits');
                break;

            case 'robots_abstract':
                ykeys.push('robots_abstract');
                labels.push('Robot Abstract Hits');
                break;
        } 
    }
    
    oasReset();
    
    Morris.Bar({
                element: 'oasgraph',
                axes: true,
                data: entries,
                xkey: 'date',
                ykeys: ykeys,
                labels: labels,
                ymin:0,
                yLabelFormat: function(y){if (y !== Math.round(y)) {return('');}else{return(y);}}
            });
    
}

function handleOasRequest() {
           oasReset();
           oasSetWaitingAnimation();

           $.ajax({
            type:"POST",
            url: "./jsonloader.php",
            data: $("#oasconfig").serialize(),

            complete: function(jqXHR, textStatus) {
                            switch (jqXHR.status) {
                                case 200:
                                    var arr = jQuery.parseJSON(jqXHR.responseText);
                                    oasSetChart(arr);
                                    break;
                                
                                case 204:
                                    oasSetMessage("No data available.");
                                    break;
                                default:
                                    var arr = jQuery.parseJSON(jqXHR.responseText);
                                    var msg = arr['error']['message'];
                                    oasSetMessage("Something went wrong. If the problem persists please contact the administrator.");

                            }
                        }

        });

}; 