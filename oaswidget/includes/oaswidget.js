/*
 * Open-Access-Statistics jQuery UI chart widget
 */
$.widget( "oas.chart", {

    // widget options and their default values
    options: {
        type:                       "bar",

        // format of a single day and  month, uses the datepicker (jQuery UI) definitions
        // (https://api.jqueryui.com/datepicker/)
        dayFormat:                  "dd.mm.yy",
        monthFormat:                "M yy",

        jsonLoader :                "./jsonloader.php",    //origin of jsonloader

        showCounter:                true,       //show Counter
        showCounterAbstract:        true,       //show CounterAbstract
        showRobots:                 false,      //show Robots
        showRobotsAbstract:         false,      //show RobotsAbstract

        showToolbar:                true,
        showToolbarDateSelector:    true,
        showToolbarDetails:         false,
        showToolbarCumulated:       true,

        showWaitingAnimation :      true,

        toolbarDateSelectorOptions: {
            granularityEditable:    false,      //enable the edit-box
            timespanEditable:       true,       //enable from-until
        },
        toolbarDetailsOptions: {
            showIdentifier:         false,
            showFirstAccess:        true
        },
        toolbarCumulatedOptions: {
            statisticsToggleable:   true,
            showAll:                false
        },
        from:                       undefined,
        until:                      undefined,
        interval:                   undefined,

        granularity:                undefined,
        identifier:                 undefined,

        morrisOptions: {
        }
    },

    // messages can (language-specific) be configured in a separate js file (see oaswidget-de.js)
    _messages: {
        toolbarDateSelectorHeader: "Zeitspanne",
        toolbarDetailsHeader: "Details",
        toolbarCumulatedHeader: "Gesamt",
        day: "Tag",
        week: "Woche",
        month: "Monat",
        granularitySelector: "Auflösung",
        from: "Von",
        until: "Bis",
        identifier: "Identifier",
        firstAccess: "Erster Zugriff",
        counter: "Counter",
        counter_abstract: "Counter Abstract",
        robots: "Robots",
        robots_abstract: "Robots Abstract",
        noData: "No data available.",
        error: "Something went wrong. If the problem persists please contact the administrator."
    },

    // constructor
    _create: function(options) {
        this.morrisContainer = false;
        this.labels = [];
        this.yKeys = [];
        this.entries = [];
        this.totalEntries = [];
        this.definition = [];
        this.jsonArray = [];
        this.granularity = "";
        this.showCounter = true;
        this.showCounterAbstract = true;
        this.showRobots = true;
        this.showRobotsAbstract = true;
        this.from = undefined;
        this.until = undefined;
        this.interval = undefined;
        this.datepickerFrom = undefined;
        this.datepickerUntil = undefined;
        this.dateFormat = "yy-mm-dd"; //format of date, hardcoded to the API date format
        this.firstRun = true;
        var me = this;

        // override fallback options
        me.options = $.extend(me.options, options);

        // load initial default values for the datepicker if toolbar-part ist enabled
        if (me.options.showToolbarDateSelector) {
            this.datepickerFrom = me._getFirstOfJanuary();
            this.datepickerUntil = me._getYesterday();
        }

        // set granularity from options if set or set to default (month)
        if (typeof me.options.granularity !== "undefined") {
            this.granularity = me.options.granularity;
        } else {
            this.granularity = "month";
        }

        // set from date from options if set or set the interval if set or set to default (first of january this year)
        if (typeof me.options.from !== "undefined") {
            this.from = me.options.from;
        } else if(typeof me.options.interval !== "undefined") {
            this.interval = me.options.interval;
        } else {
            this.from = "first day of January this year";
        }

        // set until date from options if set or set to default (yesterday)
        if(typeof me.options.until !== "undefined") {
            this.until = me.options.until;
        } else {
            this.until = "yesterday";
        }

        // toggle from options
        this.showCounter            = me.options.showCounter;
        this.showCounterAbstract    = me.options.showCounterAbstract;
        this.showRobots             = me.options.showRobots;
        this.showRobotsAbstract     = me.options.showRobotsAbstract;

        // start container
        var $container  = $("<div/>", {class: "oas-container"});
        var $graph      = $("<div/>", {class: "oas-graph"});

        $container.append($graph);

        // create toolbar-container if not disabled
        if(me.options.showToolbar){
            // create the toolbar with its toolbar-parts
            var $toolbar = this._createToolbar();
            $container.append($toolbar);
        }

        this.element.append($container);

        if(me.options.showToolbar){
            $(".oas-toggler").click(function(){
                $(".oas-toolbar").toggleClass('collapsed');
            });
        }
        this.refresh();
        return(this);
    },

    // (re-)loads the content
    refresh: function(){
        this._hideMessage();

        if(this._checkContentRequestedInvalid()){
            return;
        }
        if (this.options.showWaitingAnimation) this._showWaitingAnimation();
        this._downloadJSON();
    },

    // destroys and removes the whole widget
    _destroy: function() {
        this.element.remove();
    },

    // builds the toolbar depending on the options
    _createToolbar: function() {
        var me = this;

        // the toolbar container
        var $toolbar = $("<div/>", {class: "oas-toolbar collapsed"});

        // the toggle button for the expanding/collapsing the toolbar
        var $toolbarToggler = $("<div/>", {class: "oas-toggler"});

        // the parent div for all the possible content types
        var $toolbarContent = $("<div/>", {class:"oas-toolbar-content"});

        // the date selector toolbar part
        if(me.options.showToolbarDateSelector) {
            var $toolbarDateSelector = this._createToolbarDateSelector(me.options.toolbarDateSelectorOptions);
            $toolbarContent.append($toolbarDateSelector);
        }
        // the detail toolbar part
        if(me.options.showToolbarDetails) {
            var $toolbarDetails = this._createToolbarDetails(me.options.toolbarDetailsOptions);
            $toolbarContent.append($toolbarDetails);
        }
        // the cumulated toolbar part
        if(me.options.showToolbarCumulated) {
            var $toolbarCumulated = this._createToolbarCumulated(me.options.toolbarCumulatedOptions);
            $toolbarContent.append($toolbarCumulated);
        }

        // append the content and to
        $toolbar.append($toolbarContent);
        $toolbar.append($toolbarToggler);

        return $toolbar;
    },

    // creates a toolbar-part with a date selector
    _createToolbarDateSelector: function(options) {
        var me = this;

        var $toolbarDateSelector = $("<div/>", {class:"oas-toolbar-item"});
        $toolbarDateSelector.append($("<div/>", {class: "oas-toolbar-item-heading", text: me._messages.toolbarDateSelectorHeader}) );

        var $toolbarDateSelectorForm = $("<form/>", {id: "oas-dateselector"});
        $toolbarDateSelector.append($toolbarDateSelectorForm);

        // granularity selector
        var $granularitySelector = $("<select>", {name: "granularity"});
        $granularitySelector.append( $("<option>", {value: "day"  , text: me._messages.day})   );
        $granularitySelector.append( $("<option>", {value: "week" , text: me._messages.week}) );
        $granularitySelector.append( $("<option>", {value: "month", text: me._messages.month}) );

        $granularitySelector.val(me.granularity);
        $granularitySelector.change( function() {me.refresh(); });

        // the selector is greyed out if set in options
        $granularitySelector.prop("disabled", !options.granularityEditable);

        $toolbarDateSelectorForm.append(me._createFormGroup(me._messages.granularitySelector, $granularitySelector));

        // set language and date format setting for all datepickers
        $.datepicker.setDefaults(
            $.extend(
                {dateFormat: me.dateFormat},
                $.datepicker.regional['de']
            )
        );

        // date-from
        var $dateFrom =  $("<input>", {type: "text"  , id: "datepickerfrom", name: "from", value: me._formatDate(me.dateFormat,me.datepickerFrom)});
        $dateFrom.datepicker({
            changeMonth: me,
            changeYear: me,
            numberOfMonths: 2,
            dateFormat: me.dateFormat,
            defaultDate: me.options.toolbarDateSelectorOptions.fromDefaultDate,
            onSelect: function( selectedDate ) {
                $( "#datepickeruntil" ).datepicker( "option", "minDate", selectedDate );

                //After date was set, toggle the request
                me.refresh();
            }
        });

        // the selector is greyed out if set in options
        $dateFrom.prop("disabled", !options.timespanEditable);

        $toolbarDateSelectorForm.append(me._createFormGroup(me._messages.from, $dateFrom));

        // date-until
        var $dateUntil = $("<input>", {type: "text"  , id: "datepickeruntil", name: "until", value: me._formatDate(me.dateFormat,me.datepickerUntil)});
        $dateUntil.datepicker({
            changeMonth: me,
            changeYear: me,
            numberOfMonths: 2,
            dateFormat: me.dateFormat,
            defaultDate: me.datepickerUntil,
            minDate : me.datepickerFrom,
            maxDate: new Date(),
            onSelect: function( selectedDate ) {
                $( "#datepickerfrom" ).datepicker( "option", "maxDate", selectedDate );

                //After date was set, toggle the request
                me.refresh();
            }
        });

        // the selector is greyed out if set in options
        $dateUntil.prop("disabled", !options.timespanEditable);

        $toolbarDateSelectorForm.append(me._createFormGroup(me._messages.until, $dateUntil));

        // the identifier is sent hidden with the form
        $toolbarDateSelectorForm.append( $("<input>", {type: "hidden", name: "identifier", value: me.options.identifier}) );

        return $toolbarDateSelector;
    },

    // creates a toolbar-part with details
    _createToolbarDetails: function(options) {
        var me = this;
        // open details tab
        var $toolbarDetails = $("<div/>", {class: "oas-toolbar-item"});
        $toolbarDetails.append( $("<div/>", {class: "oas-toolbar-item-heading", text: me._messages.toolbarDetailsHeader}) );

        var $list = $("<ul/>");

        // write the identifier list element (value is filled later)
        if(options.showIdentifier){
            $list.append(me._createListElement(me._messages.identifier, $("<div/>", {id: "info_identifier", class: "oas-toolbar-value"})));
        }

        // write the identifier list element (value is filled later)
        if(options.showFirstAccess){
            $list.append(me._createListElement(me._messages.firstAccess, $("<div/>", {id: "info_firstaccess", class: "oas-toolbar-value"})));
        }

        $toolbarDetails.append($list);
        return $toolbarDetails;
    },

    // creates a toolbar-part with cumulated statistics
    _createToolbarCumulated: function(options) {
        var me = this;
        //Open Clicks-Cumulated Tab
        var $toolbarCumulated = $("<div/>", {id: "oas-configinfo_clicks", class: "oas-toolbar-item"});
        $toolbarCumulated.append( $("<div/>", {class: "oas-toolbar-item-heading", text: me._messages.toolbarCumulatedHeader}) );

        var $clickableClass = "";
        if(options.statisticsToggleable){
            $clickableClass = "oas-clickable";
        }

        var $list = $("<ul/>");
        var $listElement;

        if(me.options.showCounter || (options.statisticsToggleable && options.showAll)){
            $listElement = me._createListElement(me._messages.counter,$("<div/>", {id: "info_counter", class: "oas-toolbar-value"}));
            $listElement.addClass($clickableClass);
            $listElement.click(function() { me._toggleCounter(); });
            $list.append($listElement);

        }

        if(me.options.showCounterAbstract || (options.statisticsToggleable && options.showAll)){
            $listElement = me._createListElement(me._messages.counter_abstract,$("<div/>", {id: "info_counter_abstract", class: "oas-toolbar-value"}));
            $listElement.addClass($clickableClass);
            $listElement.click(function() { me._toggleCounterAbstract(); });
            $list.append($listElement);
        }

        if(me.options.showRobots || (options.statisticsToggleable && options.showAll)){
            $listElement = me._createListElement(me._messages.robots,$("<div/>", {id: "info_robots", class: "oas-toolbar-value"}));
            $listElement.addClass($clickableClass);
            $listElement.click(function() { me._toggleRobots();	});
            $list.append($listElement);
        }

        if(me.options.showRobotsAbstract || (options.statisticsToggleable && options.showAll)){
            $listElement = me._createListElement(me._messages.robots_abstract,$("<div/>", {id: "info_robots_abstract", class: "oas-toolbar-value"}));
            $listElement.addClass($clickableClass);
            $listElement.click(function() { me._toggleRobotsAbstract(); });
            $list.append($listElement);
        }

        $toolbarCumulated.append($list);
        // $list.append($("<div/>", {text: "test"}));
        return $toolbarCumulated;
    },

    // date of yesterday
    _getYesterday: function() {
        var today      = new Date();
        var yesterday  = new Date(today);

        yesterday.setDate(today.getDate() - 1);
        return(yesterday);
    },

    // date of the first of January this year
    _getFirstOfJanuary: function(){
        var today      = new Date();
        var firstofjan = new Date(today);

        firstofjan.setFullYear(today.getFullYear(), 0, 1);
        return(firstofjan);
    },

    // from: http://stackoverflow.com/a/19375264/2720455
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
        // e.g. "2014-W27"
        var year = parseInt(datestring.substring(0,4));
        var week = parseInt(datestring.substring(6));

        return(this._firstDayOfWeek(year, week));
    },

    // runs the morris chart tool with our data
    _setChart: function() {
        var morrisoptions = {
            element:$('.oas-graph'),
            axes: true,
            data: this.entries,
            xkey: 'date',
            ykeys: this.yKeys,
            labels: this.labels,
            ymin:0,
            parseTime:false,
            hideHover:'auto',
            yLabelFormat: function(y){if (y !== Math.round(y)) {return('');}else{return(y);}}
        };
        // accept morris options from the widget options from the object called morrisOptions
        morrisoptions = $.extend(this.options.morrisOptions, morrisoptions);

        if(this.morrisContainer === false){
            switch(this.options.type) {
                case "line":
                    this.morrisContainer = Morris.Line(morrisoptions);
                    break;
                case "area":
                    this.morrisContainer = Morris.Area(morrisoptions);
                    break;
                case "bar":
                default:
                    var baroptions = {
                        barSizeRatio: 0.95,
                        barGap: 1
                    };
                    morrisoptions = $.extend(morrisoptions, baroptions);
                    this.morrisContainer = Morris.Bar(morrisoptions);
            }
        }else{
            this.morrisContainer.options = $.extend(this.morrisContainer.options, morrisoptions );
            this.morrisContainer.setData(this.entries);
        }

        // update the datepickers minimal date
        $( "#datepickerfrom" ).datepicker( "option", "minDate", this.firstAccess );
    },

    // fills the toolbar with data
    _fillToolbarWithData: function() {
        $('#info_identifier').text(this.options.identifier);
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
            this.showCounterAbstract = true;
        }else{
            $('#info_counter_abstract').text("--");
            this.showCounterAbstract = false;
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
            this.showRobotsAbstract = true;
        }else{
            $('#info_robots_abstract').text("--");
            this.showRobotsAbstract = false;
        }
    },

    // gets the statistics from the json file
    _JSONtoObject: function() {
        var me = this;
        var i;
        if ('informational' in this.jsonArray) {
            this.firstAccess  = this.jsonArray['informational']['first-access'];
            this.totalEntries = this.jsonArray['informational']['total-accesses'];
        }
        this.entries      = this.jsonArray['entries'];
        this.definition   = this.jsonArray['entrydef'];
        this.granularity  = this.jsonArray['granularity'];

        // define the requested items and axis-labels
        this.yKeys  = [];
        this.labels = [];

        // look which items are given and show only the given ones
        for (i = 0; i < this.definition.length; ++i) {
            var current = this.definition[i];

            switch(current) {
                case 'counter':
                    this.yKeys.push('counter');
                    this.labels.push(me._messages.counter);
                    break;

                case 'counter_abstract':
                    this.yKeys.push('counter_abstract');
                    this.labels.push(me._messages.counter_abstract);
                    break;

                case 'robots':
                    this.yKeys.push('robots');
                    this.labels.push(me._messages.robots);
                    break;

                case 'robots_abstract':
                    this.yKeys.push('robots_abstract');
                    this.labels.push(me._messages.robots_abstract);
                    break;
            }
        }

        // look, if the received entries are not earlier as the datestamp provided in 'first-access'
        var firstAccess = new Date(this.firstAccess);
        var entrydate   = new Date();
        for (i = 0; i < this.entries.length; i++) {
            if(this.granularity === "day"){
                entrydate = new Date(this.entries[i]['date']);
            } else if(this.granularity === "week"){
                entrydate = this._parseWeek(this.entries[i]['date']);
                // workaround to not splice the starting week
                entrydate.setDate(entrydate.getDate()+7);
            } else if(this.granularity === "month"){
                entrydate = new Date(this.entries[i]['date']);
                // workaround to not splice the starting month
                entrydate.setMonth(entrydate.getMonth()+1);
            }

            if(entrydate < firstAccess){
                this.entries.splice(i, 1);
                i--;
            }else{
                // format the dates which are shown
                if(this.granularity === "day") {
                    this.entries[i]['date'] = this._formatDate(me.options.dayFormat,entrydate);
                } else if(this.granularity === "month") {
                    this.entries[i]['date'] = this._formatDate(me.options.monthFormat,new Date(this.entries[i]['date']));
                }
                // weeks can not easily be formated, the returned value is used
            }

        }

        this._trigger( 'jsontoobject_complete', null, { value: true } );
    },

    // download JSON
    _downloadJSON: function() {
        var me = this;

        // prepare the API parameters with the wanted content types
        var content = [];
        if(this.showCounter){
            content.push("counter");
        }
        if(this.showCounterAbstract){
            content.push("counter_abstract");
        }
        if(this.showRobots){
            content.push("robots");
        }
        if(this.showRobotsAbstract){
            content.push("robots_abstract");
        }


        // build the whole API parameter list, which some settings hardcoded because we definitely need those
        var parameter;

        // set until and granularity from the options on the first run, or if there is no formular data from the
        // date selector
        if (me.firstRun || this._getFormularData()==="") {
            parameter = $.param({
                until: me.until,
                granularity: me.granularity
            });
            // check if we use an interval or a specific from date
            if (typeof me.interval !== "undefined") {
                parameter +=  '&interval=' + me.interval;
            } else {
                parameter +=  '&from=' + me.from;
            }
            me.firstRun = false;
        // set data from the formulardata
        } else {
            parameter = this._getFormularData();
        }

        parameter += '&' +
        $.param({
            informational: 'true',
            do: 'basic',
            identifier: me.options.identifier,
            format: 'json',
            addemptyrecords: 'true',
            content: content.join() // the content array as a comma-separated string
        });

        // you can give additional request parameters in the options, if the jsonloader can interpret them
        if (typeof me.options.requests !== "undefined") {
            parameter += '&' +
            $.param(me.options.requests);
        }

        $.ajax({
            context: this,
            type:"GET",
            url: me.options.jsonLoader,
            data: parameter,
            success: function(data,textStatus,xhr) {
                me._hideWaitingAnimation();
                if(xhr.status == 204){
                    me._showMessage("No data available.");
                } else {
                    this.jsonArray = data;
                    this._trigger( 'jsondownload_complete');
                    this._JSONtoObject();
                    this._setChart();
                    this._fillToolbarWithData();
                }
            },
            error: function(xhr){
                me._hideWaitingAnimation();
                switch(xhr.status){
                    case 204:
                        me._showMessage(me._messages.noData);
                        break;

                    default:
                        me._showMessage(me._messages.error);
                }
            }
        });
    },

    _showWaitingAnimation: function(){
        $('.oas-container').append($("<div/>", {class:"oas-loading"}));
    },

    _hideWaitingAnimation: function(){
        $('.oas-loading').hide();
    },

    _formatDate: function(format,d) {
        return($.datepicker.formatDate(format, d));
    },

    _showMessage: function(msg) {
        $(".oas-container").append($("<div/>", {class:"oas-message", text:msg}));
    },

    _hideMessage: function() {
        $(".oas-message").remove();
    },

    _createFormGroup: function(label, form){
        var $formgroup = $("<div/>", {class:"oas-form-group"});

        // append the label to the form group for the id of the form
        $formgroup.append($("<label/>", {for: form.attr("id"), text: label}));
        // append the form the the form group
        $formgroup.append(form);

        return($formgroup);
    },

    _createListElement: function(key,div) {
        var $listElement = $("<li/>", {text: key});
        $listElement.append(div);

        return($listElement);
    },

    _toggleCounter: function(){
        if(!this.options.toolbarCumulatedOptions.statisticsToggleable){
            return;
        }
        this.showCounter = !this.showCounter;
        if(this._checkContentRequestedInvalid()){
            this.showCounter = !this.showCounter;
        }
        this.refresh();
    },

    _toggleCounterAbstract: function(){
        if(!this.options.toolbarCumulatedOptions.statisticsToggleable){
            return;
        }

        this.showCounterAbstract = !this.showCounterAbstract;
        if(this._checkContentRequestedInvalid()){
            this.showCounterAbstract = !this.showCounterAbstract;
        }
        this.refresh();
    },

    _toggleRobots: function(){
        if(!this.options.toolbarCumulatedOptions.statisticsToggleable){
            return;
        }

        this.showRobots = !this.showRobots;
        if(this._checkContentRequestedInvalid()){
            this.showRobots = !this.showRobots;
        }
        this.refresh();
    },

    _toggleRobotsAbstract: function(){
        if(!this.options.toolbarCumulatedOptions.statisticsToggleable){
            return;
        }

        this.showRobotsAbstract = !this.showRobotsAbstract;
        if(this._checkContentRequestedInvalid()){
            this.showRobotsAbstract = !this.showRobotsAbstract;
        }
        this.refresh();
    },

    _checkContentRequestedInvalid: function(){
        return((!this.showCounter) && (!this.showCounterAbstract) && (!this.showRobots) && (!this.showRobotsAbstract));
    },

    _getFormularData: function(){
        var form = $('#oas-dateselector');
        var disabled = form.find(':input:disabled').removeAttr('disabled');
        var data = form.serialize();

        disabled.attr('disabled','disabled');
        return(data);
    }
});