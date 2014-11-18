<?php

	session_start();
	
	$_SESSION['code'] = $_REQUEST["code"];
	$error = $_REQUEST["error"];
	
	if (!empty($error)) {
		header("location: http://slehtonen.fi/projects/FB/error.php");
	}
	else {
		header("location: http://slehtonen.fi/projects/FB/");
	}
	
?>
