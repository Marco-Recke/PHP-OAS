<?php
/**
 * Index- File for graphrovider.
 *
 * Generates HTML
 * 
 * @author Marc Giesmann <giesmann@sub.uni-goettingen.de> for VZG Göttingen
 * @package graphprovider
 * @version 0.1
 */


    require_once("./generateparams.php");
?>
<!DOCTYPE html>
<html>
    
    
    <head>
        <meta charset="utf-8"/>
        
        <script src="./includes/jquery-1.11.1/jquery-1.11.1.min.js"></script>
        <script src="./includes/raphael-2.1.2/raphael-min.js"></script>
        <script src="./includes/jquery-ui-1.11.1.custom/jquery-ui.js"></script>
        <script src="./includes/morris.js-0.5.1/morris.min.js"></script>
        
        <link rel="stylesheet" href="./includes/jquery-ui-1.11.1.custom/jquery-ui.css">
        <link rel="stylesheet" href="./includes/morris.js-0.5.1/morris.css">
        <link rel="stylesheet" href="./includes/oastatistic.css">
        <script src="./includes/oastatistic.js"></script>
        <script>
         
            //When document is ready, start loading the oas-graph with default values
            $(document).ready(function() {                
                
                //Hide Menu initially
                $( "#oasconfig" ).hide(0);
                
                //Clickhandler for button
                $( "#oastoolbarbtndown" ).click(function() {
                    $( "#oasconfig" ).slideDown("slow")
                    
                    $( "#oastoolbarbtndown" ).hide("slow");
                });
                
                 $( "#oastoolbarbtnup" ).click(function() {
                    $( "#oasconfig" ).slideUp("slow");
                    
                    $( "#oastoolbarbtndown" ).show("slow");
                });
                
                //Function to initialize the datepickers and the granularity picker
                $(function() {
                    
                    $( "#granularitypicker").change( function() {handleOasRequest();});
                    
                    $( "#datepickerfrom" ).datepicker({
                            changeMonth: true,
                            changeYear: true,
                            numberOfMonths: 2,
                            dateFormat: "yy-mm-dd",
                            defaultDate: new Date("<?php echo $from; ?>"),
                            onClose: function( selectedDate ) {
                                    $( "#datepickeruntil" ).datepicker( "option", "minDate", selectedDate );
                                    
                                    //After date was set, toggle the request
                                    handleOasRequest();
                            }
                    });
                    $( "#datepickeruntil" ).datepicker({
                            changeMonth: true,
                            numberOfMonths: 2,
                            changeYear: true,
                            dateFormat: "yy-mm-dd",
                            defaultDate: new Date("<?php echo $until; ?>"),
                            onClose: function( selectedDate ) {
                                    $( "#datepickerfrom" ).datepicker( "option", "maxDate", selectedDate );
                                    
                                    //After date was set, toggle the request
                                    handleOasRequest();
                            }
                    });
                    
                });
                
                
                //Handle OAS- Request for the first time
                handleOasRequest();
            });
         

          
        </script>
        
        <title>Open Access Statistic: Powered by Verbundzentrale Göttingen</title>
        <meta charset="UTF-8">
    </head>
    <body>
    
        <div id="oascontent">
            
            <div id="oasgraphcontainer">
                <div id="oasgraph"></div>
                <div id="oastoolbarbtndown"></div>
            </div>
                
                
                <div id="oasconfigcontainer">
                    <form id="oasconfig">
                            <div id="oastoolbarbtnup"></div>
                            Auflösung:

                            <select name="granularity" id="granularitypicker">
                                <option value="day" <?php if($dateintervalparameter == "D") echo 'selected="selected"';  ?> >Tag</option>
                                <option value="week"<?php if($dateintervalparameter == "W") echo 'selected="selected"';  ?> >Woche</option>
                                <option value="month"<?php if($dateintervalparameter == "M") echo 'selected="selected"';  ?> >Monat</option>
                            </select> 
                            Von:
                            <input type="text" id="datepickerfrom" name="from" value="<?php echo $from; ?>">
                            Bis:
                            <input type="text" id="datepickeruntil" name="until" value="<?php echo $until; ?>">
                            <input type="hidden" id="identifier" name="identifier" value="<?php echo $id; ?>">

                    </form>
                </div>
        </div>
    </body>
</html>
