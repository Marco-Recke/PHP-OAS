/* 
 * Renders a fancy chart
 * @param divId id of the div the chart should be shown in
 * @param width width of the chart in pixels
 * @param height height of the chart in pixels
 * @param tableId id of the table that contains the chart data
 * @param dataClasses array with the classes of the table rows 
 * 
 */
function Chart (divId, width, height, tableId, dataClasses) {
	
	// Grab the data
	//
	// Expected table layout:
	// Label Header    | Data Header B   | Data Header C (currently unused)
	// Row Label 1     | Row Data B1     | Row Data C1
	// Row Label 2     | Row Data B2     | Row Data C2
	// ...
	var labels = readLabels(),
		data = [];
	
	for (var i = 0; i < dataClasses.length; i++) {
		data[i] = readData(dataClasses[i]);
	}
	
	// Draw
	var	leftpadding = 40,
			bottompadding = 32,
			toppadding = 19,
			rightpadding = 28,
			r = Raphael(divId, width, height),
			txt1 = {font: '10px Arial', fill: "#333", display: "block"},
			txt2 = {font: '10px Arial', fill: "#000", "font-weight": "bold"},
			linecolor = "rgb(47, 115, 224)",
			barcolor = "rgb(47, 95, 191)";
	
	function render (labels, data) {
		var max = data.max();
		if (max < 11) {
			max = 8; // elsewise chart will be ugly for very small values
			var step = 2;
		} else {
			var step = Math.pow(10, String(max).length - 2);
			while (max / step > 8) {
				step = step * 10;
			}
			while (max / step < 3 && step % 2 == 0) {
				step = step / 2;
			}
		}
		
		max = parseInt(max/step) * step + step;
		
		// calculate width of one step in pixels
		var X = (width - leftpadding - rightpadding) / labels.length,
			Y = (height - bottompadding - toppadding) / max;
		
		// Grid:
		// gridsteps = labels.length - 1;
		// gridsteps = 1;
		// while (width/gridsteps < 20) gridsteps /= 2;
		// r.drawGrid(leftpadding, toppadding, width - leftpadding - rightpadding, height - toppadding - bottompadding, gridsteps, 1, "#aaa"); // max/step
		r.rect(leftpadding, toppadding, width - leftpadding - rightpadding, height - toppadding - bottompadding).attr({fill: "none", stroke: "#aaa"});
		
		// Labels y axis:
		for (var i = 0; i <= max; i = i + step) {
			r.text(leftpadding - 7, toppadding + Y * i - 1, max - i + '').attr("text-anchor", "end");
			//r.path({stroke: "#666"}).moveTo(leftpadding - 5, Math.round(toppadding + Y * i)).lineTo(leftpadding, Math.round(toppadding + Y * i));
			r.rect(leftpadding - 5, toppadding + Y * i - 1, 5, 1).attr({fill: "#666", stroke: "none"});
		}
		
		// Bottom label: sum
		r.text(leftpadding + (width - leftpadding - rightpadding) / 2, height - 6, "Summe letzte " + data.length + " Tage: "+ data.sum());
		
		var path = r.path().attr({stroke: linecolor, "stroke-width": 2, "stroke-linejoin": "round"}),
			bgp = r.path().attr({stroke: "none", opacity: .3, fill: linecolor}).moveTo(leftpadding, height - bottompadding),
			// Popup box:
			frame = r.rect(0, 0, 80, 32, 4).attr({fill: "#ddd", stroke: "#aaa", "stroke-width": 1, opacity: .9}).hide(),
			label = [],
			is_label_visible = false,
			leave_timer,
			blanket = r.set();
		label[0] = r.text(60, 10, "").attr(txt1).hide();
		label[1] = r.text(60, 40, "").attr(txt2).attr({fill: linecolor}).hide();
	
		// Render chart and labels
		var lastmonth = 0;
		var lastyear = 0;
		var day = 0;
		var month = 0;
		var year = 0;
		var backfill = '#fff';
		for (var i = 0; i < data.length; i++) {
			var y = Math.round(height - bottompadding - Y * data[i]),
				x = Math.round(leftpadding + X * i + X / 2);
			
			// Optional: draw bars
			r.rect(x - X / 2 + 1, y, X, height - bottompadding - y).attr({stroke: linecolor, fill: barcolor});
			
			// Labels x axis
			// 1st line: dd.mm.yyyy
			day = labels[i].substring(8, 10);
			month = labels[i].substring(5, 7);
			year = labels[i].substring(0, 4);
			
			// Retain > 80 pixels between date labels on x-axis or overlapping may occur
			if ((i == 0 && ((daysInMonth(year, month) - day) * X > 80))
					|| (day == 1 && 31 * X > 80)
					|| (day == 1 && month % 2 == 1 && 62 * X > 80)
					|| (day == 1 && month == 1)
					|| (i == data.length - 1 && day * X > 80)) {
				r.text(x, height - bottompadding + 11, day + '.' + month + '.' + year).attr(txt1).toBack();
				// r.path({stroke: "#666"}).moveTo(x, height - bottompadding).lineTo(x, height - bottompadding + 5);
				r.rect(x, height - bottompadding, 1, 6).attr({fill: "#aaa", stroke: "none"}).toBack();
			} else if (X > 4) {
				// r.path({stroke: "#666"}).moveTo(x, height - bottompadding).lineTo(x, height - bottompadding + 2);
				r.rect(x, height - bottompadding, 1, 3).attr({fill: "#aaa", stroke: "none"}).toBack();
			}
			
			// monthly changing background
			if (day == 1) backfill = backfill == '#fff' ? '#ddd' : '#fff';
			if (i < data.length) r.rect(x - X / 2 - 1, toppadding, Math.round(X) + 1.5, height - toppadding - bottompadding).attr({fill: backfill, stroke: "none"}).toBack();
			
//			Begin of new month only
//			if (labels[i].substring(0, 2) == "01") r.text(x, height - 6, labels[i]).attr(txt1).toBack();
//			2nd line: months
//			if (lastmonth != month) {
//				r.text(x - 6, height - bottompadding + 24, nameOfMonth[month - 1] + ' >>', 'left').attr(txt1).toBack();
//				lastmonth = month;
//			}
//			// 3rd line: years
//			if (lastyear != year) {
//				r.text(x - 6, height - bottompadding + 36, year + ' >>', 'left').attr(txt1).toBack();
//				lastyear = year;
//			}
	
//			bgp[i == 0 ? "lineTo" : "cplineTo"](x, y, 4);
//			path[i == 0 ? "moveTo" : "cplineTo"](x, y, 4).toFront();						
			//bgp["lineTo"](x, y);
			//path[i == 0 ? "moveTo" : "lineTo"](x, y).toFront();
			
			// Highlighted bars and animated popup box
			var highlight = r.rect(x - X / 2 + 1, y, X, height - bottompadding - y).attr({stroke: '#666', fill: '#fff', opacity: .5}).hide();
			blanket.push(r.rect(leftpadding + X * i, toppadding, X, height - bottompadding).attr({stroke: 'none', fill: '#fff', opacity: 0}));
			var rect = blanket[blanket.length - 1];
			(function (x, y, data, lbl, highlight) {
				$(rect.node).hover(function () {
					clearTimeout(leave_timer);
					var newcoord = {x: x + .5 * X + 16, y: y - 16};
					if (newcoord.x + 80 > width) newcoord.x -= 80 + X + 32;
					frame.show().animate({x: newcoord.x, y: newcoord.y}, 200 * is_label_visible).toFront();
					var date = new Date(lbl.substring(0, 4), lbl.substring(5, 7) - 1, lbl.substring(8)); 
					label[0].attr({text: date.format('D, d.m.Y')}).show().animate({x: +newcoord.x + 40, y: +newcoord.y + 9}, 200 * is_label_visible).toFront();
					label[1].attr({text: data + ' Aufruf' + ((data == 1) ? '' : 'e')}).show().animate({x: +newcoord.x + 40, y: +newcoord.y + 23}, 200 * is_label_visible).toFront();
					highlight.show();
					is_label_visible = true;
					r.safari();
				}, function () {
					highlight.hide();
					r.safari();
					leave_timer = setTimeout(function () {
						frame.hide();
						label[0].hide();
						label[1].hide();
						is_label_visible = false;
						r.safari();
					}, 1);
				});
			})(x, y, data[i], labels[i], highlight);
		}
		bgp.lineTo(x, height - bottompadding).andClose();
	}
	
	function readLabels () {
		var labels = [];
		$("#" + tableId + " tbody tr th").each(function () {
			labels.push($(this).html());
		});
		return labels;
	}
	
	function readData (dataClass) {
		var data = [];
		$("#" + tableId + " tbody tr td." + dataClass).each(function () {
			data.push(parseInt($(this).html()));
		});
		return data;
	}
	
    this.draw = function (days, newDataClass) {
    	if (r) r.remove();
    	r = Raphael(divId, width, height);
    	if (newDataClass) {
    		dataClass = newDataClass;
    		data[0] = readData(dataClass);
    	}
    	// alert('ID: ' + tableId + '\nClass: ' + dataClass + '/' + newDataClass);
    	render(labels.slice(labels.length - days), data[0].slice(data[0].length - days));
    };
}

// Helper functions below
function daysInMonth (month, year) {
	var d = new Date(year, month, 0);
	return d.getDate();
}

function nameOfMonth (month) {
	var months = ["Januar", "Februar", "März", "April", "Mai","Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"];
	return months[month];
}

// Add sum, max and min methods to every array
Array.prototype.sum = function(){
	for(var i = 0, sum = 0; i < this.length; sum += this[i++]);
	return sum;
}
Array.prototype.max = function() {
	return Math.max.apply({},this)
}
Array.prototype.min = function() {
	return Math.min.apply({},this)
}

// Simulates PHP's date function
Date.prototype.format = function(format) {
	var returnStr = '';
	var replace = Date.replaceChars;
	for (var i = 0; i < format.length; i++) {
		var curChar = format.charAt(i);
		if (replace[curChar]) {
			returnStr += replace[curChar].call(this);
		} else {
			returnStr += curChar;
		}
	}
	return returnStr;
};
Date.replaceChars = {
	shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
	longMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
	shortDays: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
	longDays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
	
	// Day
	d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
	D: function() { return Date.replaceChars.shortDays[this.getDay()]; },
	j: function() { return this.getDate(); },
	l: function() { return Date.replaceChars.longDays[this.getDay()]; },
	N: function() { return this.getDay() + 1; },
	S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
	w: function() { return this.getDay(); },
	z: function() { return "Not Yet Supported"; },
	// Week
	W: function() { return "Not Yet Supported"; },
	// Month
	F: function() { return Date.replaceChars.longMonths[this.getMonth()]; },
	m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
	M: function() { return Date.replaceChars.shortMonths[this.getMonth()]; },
	n: function() { return this.getMonth() + 1; },
	t: function() { return "Not Yet Supported"; },
	// Year
	L: function() { return (((this.getFullYear()%4==0)&&(this.getFullYear()%100 != 0)) || (this.getFullYear()%400==0)) ? '1' : '0'; },
	o: function() { return "Not Supported"; },
	Y: function() { return this.getFullYear(); },
	y: function() { return ('' + this.getFullYear()).substr(2); },
	// Time
	a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
	A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
	B: function() { return "Not Yet Supported"; },
	g: function() { return this.getHours() % 12 || 12; },
	G: function() { return this.getHours(); },
	h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
	H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
	i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
	s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
	// Timezone
	e: function() { return "Not Yet Supported"; },
	I: function() { return "Not Supported"; },
	O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
	P: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + ':' + (Math.abs(this.getTimezoneOffset() % 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() % 60)); },
	T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result;},
	Z: function() { return -this.getTimezoneOffset() * 60; },
	// Full Date/Time
	c: function() { return this.format("Y-m-d") + "T" + this.format("H:i:sP"); },
	r: function() { return this.toString(); },
	U: function() { return this.getTime() / 1000; }
};

// Raphael downward compatibility workarounds 
Raphael.el.isAbsolute = true;
Raphael.el.absolutely = function () {
    this.isAbsolute = 1;
    return this;
};
Raphael.el.relatively = function () {
    this.isAbsolute = 0;
    return this;
};
Raphael.el.moveTo = function (x, y) {
    this._last = {x: x, y: y};
    return this.attr({path: this.attrs.path + ["m", "M"][+this.isAbsolute] + parseFloat(x) + " " + parseFloat(y)});
};
Raphael.el.lineTo = function (x, y) {
    this._last = {x: x, y: y};
    return this.attr({path: this.attrs.path + ["l", "L"][+this.isAbsolute] + parseFloat(x) + " " + parseFloat(y)});
};
Raphael.el.arcTo = function (rx, ry, large_arc_flag, sweep_flag, x, y, angle) {
    this._last = {x: x, y: y};
    return this.attr({path: this.attrs.path + ["a", "A"][+this.isAbsolute] + [parseFloat(rx), parseFloat(ry), +angle, large_arc_flag, sweep_flag, parseFloat(x), parseFloat(y)].join(" ")});
};
Raphael.el.curveTo = function () {
    var args = Array.prototype.splice.call(arguments, 0, arguments.length),
        d = [0, 0, 0, 0, "s", 0, "c"][args.length] || "";
    this.isAbsolute && (d = d.toUpperCase());
    this._last = {x: args[args.length - 2], y: args[args.length - 1]};
    return this.attr({path: this.attrs.path + d + args});
};
Raphael.el.cplineTo = function (x, y, w) {
    this.attr({path: this.attrs.path + ["C", this._last.x + w, this._last.y, x - w, y, x, y]});
    this._last = {x: x, y: y};
    return this;
};
Raphael.el.qcurveTo = function () {
    var d = [0, 1, "t", 3, "q"][arguments.length],
        args = Array.prototype.splice.call(arguments, 0, arguments.length);
    if (this.isAbsolute) {
        d = d.toUpperCase();
    }
    this._last = {x: args[args.length - 2], y: args[args.length - 1]};
    return this.attr({path: this.attrs.path + d + args});
};
Raphael.el.addRoundedCorner = function (r, dir) {
    var rollback = this.isAbsolute;
    rollback && this.relatively();
    this._last = {x: r * (!!(dir.indexOf("r") + 1) * 2 - 1), y: r * (!!(dir.indexOf("d") + 1) * 2 - 1)};
    this.arcTo(r, r, 0, {"lu": 1, "rd": 1, "ur": 1, "dl": 1}[dir] || 0, this._last.x, this._last.y);
    rollback && this.absolutely();
    return this;
};
Raphael.el.andClose = function () {
    return this.attr({path: this.attrs.path + "z"});
};
