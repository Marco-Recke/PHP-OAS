/**
 * JavaScript for retrieving OA- Statistics Data through jsonloader.
 * Uses morris.js. Loads morris.js-barchart into div with id "#oasgraph"
 * 
 * @requires <script src="./includes/jquery-1.11.1/jquery-1.11.1.min.js"></script>
 *           <script src="./includes/raphael-2.1.2/raphael-min.js"></script>
 *           <script src="./includes/jquery-ui-1.11.1.custom/jquery-ui.js"></script>
 *           <script src="./includes/morris.js-0.5.1/morris.min.js"></script>
 *       
 *           <link rel="stylesheet" href="./includes/jquery-ui-1.11.1.custom/jquery-ui.css">
 *           <link rel="stylesheet" href="./includes/morris.js-0.5.1/morris.css">
 *           <link rel="stylesheet" href="./includes/oastatistic.css">
 */


/**
 * Generates HTML
 * 
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */
(function ( $ ) {
    
    handleRequest = function () {
           //oasReset();
           //oasSetWaitingAnimation();

           $.ajax({
            type:"POST",
            url: "./jsonloader.php",
            data: $("#oasconfig").serialize(),

            complete: function(jqXHR, textStatus) {
                            switch (jqXHR.status) {
                                case 200:
                                    var arr = jQuery.parseJSON(jqXHR.responseText);
                                    showChart(arr);
                                    break;
                                
                                case 204:
                                    //oasSetMessage("No data available.");
                                    break;
                                default:
                                    var arr = jQuery.parseJSON(jqXHR.responseText);
                                    var msg = arr['error']['message'];
                                    //oasSetMessage("Something went wrong. If the problem persists please contact the administrator.");

                            }
                        }

        });
    };
    
    resetChart = function (){
        $("#oasgraph").empty();
    }
    
    showChart = function(arr){
    
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

        resetChart();

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
    
    };
    
    
    $.fn.oasgraph = function(options) {
        
        var $today      = new Date();
        var $yesterday  = new Date($today);
        var $firstofjan = new Date($today);
        
        $yesterday.setDate($today.getDate() - 1);
        $firstofjan.setFullYear($today.getFullYear(), 0, 1);
        
         // This is the easiest way to have default options.
        var settings = $.extend({
            from:  $firstofjan,      //first day of year
            until: $yesterday,     //yesterday
            showparameters: true,                               //toggle "from/until" panel
            dateformat: "yy-mm-dd"
            
            //ToDo : Add locales? Languages and stuff
        }, options );
                
        //Format Date properly
        settings['from']  = $.datepicker.formatDate(settings['dateformat'], settings['from']);
        settings['until'] = $.datepicker.formatDate(settings['dateformat'], settings['until']);
        
        //Apply DIVs
        return this.each( function() {
            
                //Create buttons
                var $oasbuttonup = $("<div>", {id: "oastoolbarbtnup",
                                               click: function(){
                                                    $( "#oasconfig" ).hide('slide', {direction: 'up'},'fast');
                                                    $( "#oastoolbarbtndown" ).show('slide', {direction: 'down'},'fast');
                                               }
                });
                
                var $oasbuttondn = $("<div>", {id: "oastoolbarbtndown",
                                               click: function(){
                                                    $( "#oasconfig" ).fadeIn('fast','linear');
                                                    $( "#oastoolbarbtndown" ).hide("fast");
                                               }
                });
            
            //Start container
            var $oacontainer       = $("<div/>", {id: "oascontainer", class: "oastatistics"});
            var $oasgraphcontainer = $("<div/>", {id: "oasgraphcontainer"});
            var $oasgraph          = $("<div/>", {id: "oasgraph"});
            
            $oasgraphcontainer.append($oasgraph, $oasbuttondn);
            $oacontainer.append($oasgraphcontainer);
            
            //Start config-container
            var $oasconfig = $("<div/>", {id: "oasconfigcontainer"});
            
                //Create form
                var $oasconfigform = $("<form/>", {id: "oasconfig"}); //TODO: add display:none" or hide at beginning
                    $oasconfigform.append($oasbuttonup);
                    $oasconfigform.append("Auflösung:");

                    //Granularity selector
                    var $selector = $("<select>", {id: "granularitypicker", name: "granularity"});
                        $selector.append( $("<option>", {value: "day"  , text: "Tag"})   );
                        $selector.append( $("<option>", {value: "week" , text: "Woche"}) );
                        $selector.append( $("<option>", {value: "month", text: "Monat"}) );
                        
                        //ToDo
                        //$selector.change( function() {handleOasRequest();});
                    $oasconfigform.append($selector);

                    //Date
                    $oasconfigform.append( "Von" + ":" );
                    
                        //FROM
                        $datefrom =  $("<input>", {type: "text"  , id: "datepickerfrom", name: "from", value: settings['from']});
                        $datefrom.datepicker({
                                changeMonth: true,
                                changeYear: true,
                                numberOfMonths: 2,
                                dateFormat: settings['dateformat'],
                                defaultDate: settings['from'],
                                onClose: function( selectedDate ) {
                                        $( "#datepickeruntil" ).datepicker( "option", "minDate", selectedDate );

                                        //After date was set, toggle the request
                                        handleRequest();
                                }
                        });

                    $oasconfigform.append($datefrom);
                    
                    $oasconfigform.append( "Bis" + ":" );
                    
                    //UNTIL
                        $dateuntil = $("<input>", {type: "text"  , id: "datepickeruntil", name: "until", value: settings['until']});
                        $dateuntil.datepicker({
                                changeMonth: true,
                                changeYear: true,
                                numberOfMonths: 2,
                                dateFormat: settings['dateformat'],
                                defaultDate: settings['until'],
                                minDate : settings['from'],
                                onClose: function( selectedDate ) {
                                        $( "#datepickerfrom" ).datepicker( "option", "maxDate", selectedDate );

                                        //After date was set, toggle the request
                                        handleRequest();
                                }
                        });
                    $oasconfigform.append($dateuntil);
                    //Identifier
                    $oasconfigform.append( $("<input>", {type: "hidden", id: "identifier", name: "identifier", value: "oas-123"}) );
                //End form
                
                //Hide initially
                $oasconfig.append($oasconfigform);
                
            //End config-container
            $oacontainer.append($oasconfig);
            
            //Append container to div
            $(this).append($oacontainer);
        });
        
        
       
    };
    
   }( jQuery));