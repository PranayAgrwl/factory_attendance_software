<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata'); // comment

	require_once 'app/controllers/Controller.php';

	$request = $_SERVER['REQUEST_URI'];
	$request = str_replace('/kptex', '', $request);

	// if (substr($request, 0, 1) == '/') {
	// 	$request = substr($request, 1);
	// }

	$route = strtok($request, '?');
	// Clean up leading/trailing slashes (e.g., converts '/attendance' to 'attendance')
	$route = trim($route, '/');


	$Controller = new Controller ();
	
	switch ($route) {
		case 'login' :
			$Controller->login();
			break;
		case 'update_location' : 
            $Controller->update_location();
            break;
		case '' :
			$Controller->index();
			break;
		case 'index' :
			$Controller->index();
			break;
		case 'master' :
			$Controller->master();
			break;
		case 'attendance' :
			$Controller->attendance();
			break;
		case 'monthly_report' :
			$Controller->monthly_report();
			break;
		case 'logout' :
			$Controller->logout();
			break;
		default :
			http_response_code ( 404 );
			$Controller->error404();
			break;
	}
	
?>
