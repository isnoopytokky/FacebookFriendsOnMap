 <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="X-UA-Compatible"
    content="IE=7; IE=EmulateIE9; IE=EmulateIE10;" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Facebook friends mapped!</title>
<meta name="description"
    content="Shows your Facebook friends' locations on map" />
<meta name="keywords" content="facebook, friends, map" />
<meta name=viewport
    content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<script type="text/javascript" charset="UTF-8"
    src="http://api.maps.nokia.com/2.2.4/jsl.js?with=all"></script>
<link type="text/css" rel="stylesheet" href="style.css" />
</head>
<body>
    <?php

    include("config.php");

    session_start();
 
    // App preferences
    // $app_id and $app_secret included from config.php

    $my_url = 'http://slehtonen.fi/projects/FB/return.php';

    $code = $_SESSION['code'];

    // auth user
    if(empty($code)) {
        $dialog_url = 'https://www.facebook.com/dialog/oauth?client_id='
        . $app_id . '&scope=friends_location&redirect_uri=' . urlencode($my_url) ;
        echo("<script>top.location.href='" . $dialog_url . "'</script>");
    }
    else {
        // get user access_token
        $token_url = 'https://graph.facebook.com/oauth/access_token?client_id='
                . $app_id . '&redirect_uri=' . urlencode($my_url)
                . '&client_secret=' . $app_secret
                . '&code=' . $code;
    
        // response is of the format "access_token=AAAC..."
        $access_token = substr(file_get_contents($token_url), 13);
    
        // run fql query
        $fql_query_url = 'https://graph.facebook.com/'
                . 'fql?q=SELECT+first_name,+last_name,+current_location+from+user+where+uid+IN+(SELECT+uid2+FROM+friend+WHERE+uid1+=me())+ORDER+BY+first_name'
                . '&access_token=' . $access_token;
        $fql_query_result = file_get_contents($fql_query_url);
        $fql_query_obj = json_decode($fql_query_result, true);
    
        /*
         * Do some cleaning for better performance.
         * Remove users who dont share their location
         * Save one city only once, but count the total occurances
         */
    
        $print_array= array();
        // Loop through the results
        foreach ($fql_query_obj as $key => $row) {
            foreach ($row as $key => $data) {
                // If location is known, save some data for later. Otherwise ignore
                if ($data['current_location']['longitude'] != null) {
    
                    // Use coordinates as identifier
                    $location = $data['current_location']['city'] . "," . $data['current_location']['country'];
        
                    if ($print_array[$location]['count'] == ""){
                          $print_array[$location]['count'] = 1;
                          $print_array[$location]['lat'] = $data['current_location']['latitude'];
                          $print_array[$location]['lon'] = $data['current_location']['longitude'];
                          $print_array[$location]['friends'] .= "<strong>" . $data['current_location']['city'] . "</strong><br />" . $data['first_name'] . " " . $data['last_name'] . "<br />";
                      }
                      else {
                          $print_array[$location]['count']++;
                          $print_array[$location]['friends'] .= $data['first_name'] . " " . $data['last_name'] . "<br />";
                      }
                  }
            }
        }
        $max = 0;
        $maxKey = "";
        foreach ($print_array as $key => $row) {
            if ($row['count'] > $max) {
                $max = $row['count'];
                $maxKey = $key;
            }
        }
    }
    ?>
    <div id="wrapper">
        <div id="mapContainer"></div>
        <div id="uiContainer"></div>
        <div id="over_map">
            <p align="left">See your Facebook friends on map!<br /><small>Shows friends who have shared information about their current location.<br />Locations are based on city coordinates provided by Facebook.</small></p>
            <p align="right"><span onClick="this.parentNode.parentNode.style.display = 'none';" id="close">[close]</span></p></div>
    </div>
    <script type="text/javascript" id="sorsa">

nokia.Settings.set("appId", "s9zXu2g9oGlokoQ9adD4"); 
nokia.Settings.set("authenticationToken", "o7ZYRMARvEx5B28PUbWQ3A");

// Get the DOM node to which we will append the map
var mapContainer = document.getElementById("mapContainer");

//We create a new instance of InfoBubbles bound to a variable so we can call it later on
var infoBubbles = new nokia.maps.map.component.InfoBubbles();

// Create a map inside the map container DOM node
var map = new nokia.maps.map.Display(mapContainer, {
    // Centers to the city with most friends
    center: [<?php echo $print_array[$maxKey]['lat'] . ",". $print_array[$maxKey]['lon']; ?>],
    zoomLevel: 6,
    // We add the behavior component to allow panning / zooming of the map
    components:[new nokia.maps.map.component.Behavior(),infoBubbles,new nokia.maps.map.component.ZoomBar()]
});

// Add event listener on mouse click or finger tap so we check
var TOUCH = nokia.maps.dom.Page.browser.touch, CLICK = TOUCH ? "tap" : "click";

<?php 

	$i = 0;

	foreach ($print_array as $key => $row) {

		echo "
  		var props = {
			text: \"".$row['count']."\",
			brush: {color: \"#F80\"}
		}
	    var coords = new nokia.maps.geo.Coordinate(".$row['lat'].",". $row['lon'].");\n
		var standardMarker$i = new nokia.maps.map.StandardMarker(coords,props);\n

		standardMarker$i.addListener(CLICK, function (evt) {
			infoBubbles.openBubble(\"".$row['friends']."\", standardMarker$i.coordinate);
		});
				
		map.objects.add(standardMarker$i);\n";
		$i++;
	}

	unset($_SESSION['code']);			
?>
    

        </script>
		<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-32366235-1', 'auto');
  ga('send', 'pageview');

</script>
</body>
</html>
