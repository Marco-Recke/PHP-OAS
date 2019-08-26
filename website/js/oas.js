

// bootstrap-table function - shows stats icon in every row
function operateFormatter(value, row, index) {
        return [
            '<a class="showGraph" href="javascript:void(0)" title="Graph">',
                '<i class="glyphicon glyphicon-stats"></i>',
            '</a>'
        ].join('');
}

//  logout via javascript
function logout() {
	var xmlhttp;
  	if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
  	}
	// code for IE
	else if (window.ActiveXObject) {
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	if (window.ActiveXObject) {
		// IE clear HTTP Authentication
		document.execCommand("ClearAuthenticationCache");
		window.location.href="index.php";
	} else {
	  xmlhttp.open("GET", "index.php", true, "logout", "logout");
	  xmlhttp.send("");
	  xmlhttp.onreadystatechange = function() {
	      if (xmlhttp.readyState == 4) {window.location.href="index.php";}
	  };
	}
}

// necessary for responsive behaviour on every page
$(document).ready(function() {
  $('[data-toggle=offcanvas]').click(function() {
    $('.row-offcanvas').toggleClass('active');
  });
});