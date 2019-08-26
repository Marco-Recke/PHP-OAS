/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$.widget( "oas.barchart", {
    
    //Settings for the plugin
    options: {
        dateformat: "yy-mm-dd",             //format of date
        jsonloader : "./jsonloader.php",    //origin of jsonloader
        
        infopanelEnabled: true,               //enable toolbar
        
        granularityEditable: true,           //enable the edit-box
        granularityStandard: "month",
        
        timespanEditable: true,             //enable from-until
        
        showCounter: true,                  //show Counter
        showCounterAbst: true,              //show CounterAbstract
        showRobots: false,                  //show Robots
        showRobotsAbst: false,              //show RobotsAbstract
        
        statisticsToggleable: true          //dynamic editing
    },
        
    //Constructor
    _create: function(options) {
        this.MorrisContainer = false;
        this.labels          = [];
        this.yKeys           = [];
        this.firstAccess     = "2000-01-01";
        this.entries         = [];
        this.totalEntries    = [];
        this.definition      = [];
        this.jsonArray       = [];
        this.granularity     = "";
        this.showCounter     = true;
        this.showCounterAbst = true;
        this.showRobots      = true;
        this.showRobotsAbst  = true;
        var me = this;
        
        // Override fallback options
        me.options = $.extend(me.options, options );
        if(typeof me.options['from'] === "undefined"){
            me.options['from'] = me._getFirstOfJanuary();
        }else{
            me.options['from'] = new Date(me.options['from'])
        }
        if(typeof me.options['until'] === "undefined"){
            me.options['until'] = me._getYesterday();
        }else{
            me.options['until'] = new Date(me.options['until'])
        }
        
        //Toggle from options
        this.showCounter     = me.options['showCounter'];
        this.showCounterAbst = me.options['showCounterAbst'];
        this.showRobots      = me.options['showRobots'];
        this.showRobotsAbst  = me.options['showRobotsAbst'];
        
        /*
         * Create divs to use
         */
        
        //Create buttons
        var $oasbuttondn = $("<div>", {id: "oastoolbarbtndown",class: "oasconfigbox",
                                        click: function(){ me._hideConfig(); }
                                      });
                                      
        var btnstyle = "display:inline";
        if(!me.options['infopanelEnabled']){
            btnstyle = "display:none";
        }
        
        //If panel is deactivated, dont show this button
        var $oasbuttonup = $("<div>", {id: "oastoolbarbtnup", style:btnstyle,
                                        click: function(){ me._showConfig(); }
                                      });

    //Start container
    var $oacontainer       = $("<div/>", {id: "oascontainer", class: "oastatistics"});
    var $oasgraphcontainer = $("<div/>", {id: "oasgraphcontainer"});
    var $oasgraph          = $("<div/>", {id: "oasgraph"});

    $oasgraphcontainer.append($oasgraph, $oasbuttonup);
    $oacontainer.append($oasgraphcontainer);

    //Start config-container
    var $oasconfig = $("<div/>", {id: "oasconfigcontainer"});
    $oasconfig.append($oasbuttondn);

    //Create form
    var $oasconfigform = $("<form/>", {id: "oasconfig", class:"oasconfigbox"}); 
        $oasconfigform.append( $("<span/>", {text: "Zeitspanne"}) );
        
        //Granularity selector
        var $selector = $("<select>", {id: "granularitypicker", name: "granularity"});
            $selector.append( $("<option>", {value: "day"  , text: "Tag"})   );
            $selector.append( $("<option>", {value: "week" , text: "Woche"}) );
            $selector.append( $("<option>", {value: "month", text: "Monat"}) );
        
        $selector.val(me.options['granularityStandard']);
        $selector.change( function() {me.refresh(); });
        $selector.prop("disabled", !me.options['granularityEditable']);
        
        $oasconfigform.append(me._labelInfoGroup("Auflösung:", $selector));
        //Date
            //FROM
            $datefrom =  $("<input>", {type: "text"  , id: "datepickerfrom", name: "from", value: me._formatDate(me.options['from'])});
            $datefrom.datepicker({
                    changeMonth: me,
                    changeYear: me,
                    numberOfMonths: 2,
                    dateFormat: me.options['dateformat'],
                    defaultDate: me.options['from'],
                    onClose: function( selectedDate ) {
                            $( "#datepickeruntil" ).datepicker( "option", "minDate", selectedDate );

                            //After date was set, toggle the request
                            me.refresh();
                    }
            });
            
         //Disabled?
         $datefrom.prop("disabled", !me.options['timespanEditable']);
         $oasconfigform.append(me._labelInfoGroup("Von:", $datefrom));

        //UNTIL
            $dateuntil = $("<input>", {type: "text"  , id: "datepickeruntil", name: "until", value: me._formatDate(me.options['until'])});
            $dateuntil.datepicker({
                    changeMonth: me,
                    changeYear: me,
                    numberOfMonths: 2,
                    dateFormat: me.options['dateformat'],
                    defaultDate: me.options['until'],
                    minDate : me.options['from'],
                    maxDate: new Date(),
                    onClose: function( selectedDate ) {
                            $( "#datepickerfrom" ).datepicker( "option", "maxDate", selectedDate );

                            //After date was set, toggle the request
                            me.refresh();
                    }
            });
            
            //Disabled?
            $dateuntil.prop("disabled", !me.options['timespanEditable']);
            $oasconfigform.append(me._labelInfoGroup("Bis:", $dateuntil));
            
            //Identifier
            $oasconfigform.append( $("<input>", {type: "hidden", id: "identifier", name: "identifier", value: me.options['identifier']}) );
        //End form
        
        var $oasconfig_info = $("<div/>", {id: "oasconfiginfo_identifier", class: "oasconfigbox"});
        $oasconfig_info.append( $("<span/>", {text: "Details"}) );
        $oasconfig_info.append(  me._labelInfoGroup("Identifier:",$("<div/>", {id: "info_identifier"})));
        $oasconfig_info.append(  me._labelInfoGroup("First Access:",$("<div/>", {id: "info_firstaccess"})));
        
        
        var $oasconfig_info_clicks = $("<div/>", {id: "oasconfiginfo_clicks", class: "oasconfigbox"});
        $oasconfig_info_clicks.append( $("<span/>", {text: "Klicks kumuliert"}) );
        
        
        var $class_clickable = "";
        if(me.options['statisticsToggleable']){
            $class_clickable = "oas_clickable";
        }
        if(me.options['statisticsToggleable'] || me.options['showCounter']){
             $oasconfig_info_clicks.append(  me._labelInfoGroup("Counter:",$("<div/>", {id: "info_counter", class: $class_clickable, 
                                                                                        click: function(){me._toggleCounter();}
                                                                                        }
                                                                            )
                                                                )
                                           );
        }
        
        if(me.options['statisticsToggleable'] || me.options['showCounterAbst']){
             $oasconfig_info_clicks.append(  me._labelInfoGroup("Abstract:",$("<div/>", {id: "info_counter_abstract", class: $class_clickable, 
                                                                                        click: function(){me._toggleCounterAbstract();}
                                                                                        }
                                                                            )
                                                                )
                                           );
        }
        
        if(me.options['statisticsToggleable'] || me.options['showRobots']){
             $oasconfig_info_clicks.append(  me._labelInfoGroup("Robots:",$("<div/>", {id: "info_robots", class: $class_clickable, 
                                                                                        click: function(){me._toggleRobots();}
                                                                                        }
                                                                            )
                                                                )
                                           );
        }
        
        if(me.options['statisticsToggleable'] || me.options['showRobotsAbst']){
             $oasconfig_info_clicks.append(  me._labelInfoGroup("Robots Abstract:",$("<div/>", {id: "info_robots_abstract", class: $class_clickable, 
                                                                                        click: function(){me._toggleRobotsAbstract();}
                                                                                        }
                                                                            )
                                                                )
                                           );
        }        
        $oasconfig.append($oasconfigform, $oasconfig_info, $oasconfig_info_clicks);

    //End config-container
    $oacontainer.append($oasconfig);

    this.element.append($oacontainer);
//    $(this).bind("barchartjsondownload_complete", me._JSONtoObject());
//    $(this).bind("barchartjsontoobject_complete", me._setChart());

    this.refresh();
    return(this);

    },
    
    /*
     * Public functions
     */    
    refresh: function(){
        console.log("refresh");
        
        if(this._checkContentRequestedInvalid()){
            return;
        }
        this._showWaitingAnimation();
        this._downloadJSON();
    },
    
    destroy: function() {
        this.element.empty();

        // Call the base destroy function.
        $.Widget.prototype.destroy.call( this );
    },
    
    
    /*
     * Private functions
     */
    
    // Date of yesterday
    _getYesterday: function() {
        var today      = new Date();
        var yesterday  = new Date(today);

        yesterday.setDate(today.getDate() - 1);
        return(yesterday);
    },
    
    // Date of the first January this year 
    _getFirstOfJanuary: function(){
        var today      = new Date();
        var firstofjan = new Date(today);

        firstofjan.setFullYear(today.getFullYear(), 0, 1);
        return(firstofjan);
    },
    
    //From: http://stackoverflow.com/a/19375264/2720455
    _firstDayOfWeek: function(year, week) {

        // Jan 1 of 'year'
        var d = new Date(year, 0, 1),
            offset = d.getTimezoneOffset();

        // ISO: week 1 is the one with the year's first Thursday 
        // so nearest Thursday: current date + 4 - current day number
        // Sunday is converted from 0 to 7
        d.setDate(d.getDate() + 4 - (d.getDay() || 7));

        // 7 days * (week - overlapping first week)
        d.setTime(d.getTime() + 7 * 24 * 60 * 60 * 1000 
            * (week + (year == d.getFullYear() ? -1 : 0 )));

        // daylight savings fix
        d.setTime(d.getTime() 
            + (d.getTimezoneOffset() - offset) * 60 * 1000);

        // back to Monday (from Thursday)
        d.setDate(d.getDate() - 3);

        return d;
    },
    
    
    _parseWeek: function(datestring){
        //"2014-W27"
        var year = parseInt(datestring.substring(0,4));
        var week = parseInt(datestring.substring(6));
        
        return(this._firstDayOfWeek(year, week));
    },
    
    _hideConfig: function() {
        $( "#oasconfigcontainer" ).hide('slide', {direction: 'down'},'fast');
    },
    
    _showConfig: function() {
        $( "#oasconfigcontainer" ).show('slide', {direction: 'down'},'fast');
    },
    
    
    _setChart: function() {
        console.log("_setChart");
        var morrisoptions = {
                    element: 'oasgraph',
                    axes: true,
                    data: this.entries,
                    xkey: 'date',
                    ykeys: this.yKeys,
                    labels: this.labels,
                    ymin:0,
                    yLabelFormat: function(y){if (y !== Math.round(y)) {return('');}else{return(y);}}
                };
        
        if(this.MorrisContainer === false){
            this.MorrisContainer = Morris.Bar(morrisoptions);
        }else{ 
            this.MorrisContainer.options = $.extend(this.MorrisContainer.options, morrisoptions );
            this.MorrisContainer.setData(this.entries);
        }
        
        //Update the datepickers minimal size
        $( "#datepickerfrom" ).datepicker( "option", "minDate", this.firstAccess );
        this._hideConfig();
    },
    
    
    _setInfo: function() {
        
        $('#info_identifier').text(this.options['identifier']);
        $('#info_firstaccess').text(this.firstAccess);
        
        if(typeof this.totalEntries['counter'] !== 'undefined'){
            $('#info_counter').text(this.totalEntries['counter']);
            this.showCounter = true;
        }else{
            $('#info_counter').text("--");
            this.showCounter = false;
        }
        
        if(typeof this.totalEntries['counter_abstract'] !== 'undefined'){
            $('#info_counter_abstract').text(this.totalEntries['counter_abstract']);
            this.showCounterAbst = true;
        }else{
            $('#info_counter_abstract').text("--");
            this.showCounterAbst = false;
        }
        
        if(typeof this.totalEntries['robots'] !== 'undefined'){
            $('#info_robots').text(this.totalEntries['robots']);
            this.showRobots = true;
        }else{
            $('#info_robots').text("--");
            this.showRobots = false;
        }
        
        if(typeof this.totalEntries['robots_abstract'] !== 'undefined'){
            $('#info_robots_abstract').text(this.totalEntries['robots_abstract']);
            this.showRobotsAbst = true;
        }else{
            $('#info_robots_abstract').text("--");
            this.showRobotsAbst = false;
        }
    },
    
    //set Additional information
    _JSONtoObject: function() {
        console.log("_JSONtoObject");
        this.firstAccess  = this.jsonArray['informational']['first-access'];
        this.entries      = this.jsonArray['entries'];
        this.definition   = this.jsonArray['entrydef'];
        this.totalEntries = this.jsonArray['informational']['total-accesses'];
        this.granularity  = this.jsonArray['granularity'];
        
        //Define the requested items and axis-labels
        this.yKeys  = [];
        this.labels = [];

        //Look which items are given and show only the given ones.
        var i = 0;
        for (i = 0; i < this.definition.length; ++i) {
            var current = this.definition[i];

            switch(current) {
                case 'counter':
                    this.yKeys.push('counter');
                    this.labels.push('Downloads');
                    break;

                case 'counter_abstract':
                    this.yKeys.push('counter_abstract');
                    this.labels.push('Abstract Downloads');
                    break;

                case 'robots':
                    this.yKeys.push('robots');
                    this.labels.push('Robot Hits');
                    break;

                case 'robots_abstract':
                    this.yKeys.push('robots_abstract');
                    this.labels.push('Robot Abstract Hits');
                    break;
            } 
        }
        
        //Look, if the recieved entries are not earlier as the datestamp provided in 'first-access'.        
        var firstAccess = new Date(this.firstAccess);
        var entrydate   = new Date();
        for (var i = 0; i < this.entries.length; i++) {
            if(this.granularity === "day"){
                entrydate = new Date(this.entries[i]['date']);
            }
            
            if(this.granularity === "week"){
                entrydate = this._parseWeek(this.entries[i]['date']);
            }
            
            if(this.granularity === "month"){
                entrydate = new Date(this.entries[i]['date'] + "-01");
            }
            
            if(entrydate < firstAccess){
                this.entries.splice(i, 1);
                i--;
            }else{
                //Exit loop.
                break;
            }

        }
        

        
        this._trigger( 'jsontoobject_complete', null, { value: true } );
        
    },
    
    //Download JSON
    _downloadJSON: function() {
            console.log("_downloadJSON");
           var me = this;
           
          var content = "";
          if(this.showCounter){
              if(content.length > 1){
                  content = content + ",";
              }
              
              content = content + "counter";
          }
          
          if(this.showCounterAbst){
              if(content.length > 1){
                  content = content + ",";
              }
              
              content = content + "counter_abstract";
          }
          
          if(this.showRobots){
              if(content.length > 1){
                  content = content + ",";
              }
              
              content = content + "robots";
          }
          
          if(this.showRobotsAbst){
              if(content.length > 1){
                  content = content + ",";
              }
              
              content = content + "robots_abstract";
          }
           
          
          var parameter = this._getFormularData() + '&' +  
           $.param({
                            informational: 'true',
                            do: 'basic',
                            format: 'json',
                            addemptyrecords: 'true',
                            content: content
                         });
           
           $.ajax({
            context: this,
            type:"GET",
            url: me.options.jsonloader,
            data: parameter,
//            username: me.options['username'],
//            password: me.options['password'],
//            xhrFields: { withCredentials: true },

            success: function(data,textStatus,jqXHR) {
                    
                        if(jqXHR.status === "204"){
                            me._showMessage("No data available.");
                        }
                    
                        this.jsonArray = data;
                        this._trigger( 'jsondownload_complete');
                        this._JSONtoObject();
                        this._setChart();
                        this._setInfo();
                    },
                    
                    error: function(xhr, status){
                            
                            switch(xhr.status){
                                case 204:
                                    me._showMessage("No data available.");
                                    break;
                                    
                                default:
                                    me._showError("Something went wrong. If the problem persists please contact the administrator.");
                                    me._showError(JSON.stringify(xhr));
                            }
                        
                    }
                       
        });
    },
    
    _showWaitingAnimation: function(){
        //$('#oasgraph').html('<img id="oasmessage" src="./includes/pictures/gears.gif" width="64" height="47"/>');
    },
    
     _formatDate: function(d) {
         return($.datepicker.formatDate(this.options['dateformat'], d));
     },
    
     _showError: function(msg) {
         alert("Error:" + msg);
     },
    
     _showMessage: function(msg) {
         alert("Message:" + msg);
         //$("#oasgraph").html("");
         //$("#oasgraph").append($("<div/>", {class:"oas-message", text:msg}));
     },
     
     _labelInfoGroup: function(label, content){
         
        var $group = $("<div/>", {class:"oas-control-group"});
        var $contentwrapper = $("<div/>", {class:"oas-control-group-content"});
        
        $contentwrapper.append(content);
        
        var $id   = content.attr('id');
        
        
        $group.append($("<label/>", {for: $id, class:"oas-control-group-label", text: label}),  $contentwrapper);
            
        return($group);
     },
     
     _toggleCounter: function(){
         
         if(!this.options['statisticsToggleable']){
             return;
         }
        
         
         this.showCounter = !this.showCounter;
         if(this._checkContentRequestedInvalid()){
             this.showCounter = !this.showCounter;
         }
         this.refresh();
     },
     
     _toggleCounterAbstract: function(){
         if(!this.options['statisticsToggleable']){
             return;
         }
         
         this.showCounterAbst = !this.showCounterAbst;
         if(this._checkContentRequestedInvalid()){
             this.showCounterAbst = !this.showCounterAbst;
         }
         this.refresh();
         
     },
     
     _toggleRobots: function(){
         if(!this.options['statisticsToggleable']){
             return;
         }
         
         this.showRobots = !this.showRobots;
         if(this._checkContentRequestedInvalid()){
             this.showRobots = !this.showRobots;
         }
         this.refresh();
         
     },
     
     _toggleRobotsAbstract: function(){
         if(!this.options['statisticsToggleable']){
             return;
         }
         
         this.showRobotsAbst = !this.showRobotsAbst;
         if(this._checkContentRequestedInvalid()){
             this.showRobotsAbst = !this.showRobotsAbst;
         }
         this.refresh();
         
     },
     
     _checkContentRequestedInvalid: function(){
         return((!this.showCounter) && (!this.showCounterAbst) && (!this.showRobots) && (!this.showRobotsAbst));
         
     },
     
     _getFormularData: function(){
         var myform = $('#oasconfig');
         var disabled = myform.find(':input:disabled').removeAttr('disabled');
         var data = $("#oasconfig").serialize();
         disabled.attr('disabled','disabled');
         return(data);
     }
    
});