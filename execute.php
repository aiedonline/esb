<?php
require_once __DIR__ . "/api/utilitario.php";
require_once __DIR__ . "/api/json.php";
require_once __DIR__ . "/api/edb.php";


//error_reporting(0);
error_reporting(E_ALL);


// teste
$now = DateTime::createFromFormat('U.u', microtime(true));

// DADOS QUE VEM DO JSON POST + url
$part = explode("/", $_SERVER["REQUEST_URI"]);
$post_data = json_decode(file_get_contents('php://input'), true);

error_log("\n\n\n\n", 0);
error_log(json_encode($post_data), 0);

require_once __DIR__ . "/driver/". $post_data["driver"] ."/execute.php";
$ex = new DriverEDB();

//$retorno = $ex::exec( $repository, $post_data["envelop"], $post_data["cache"], $session_user, $post_data["token"]);

echo json_encode(array("status" => true, "rows" => $retorno));



