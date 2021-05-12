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


switch ($post_data['action'] ){
	case 'register':
		// ... processa e retorna um FID (fila id) .../
		$fid = guid();
		$t  = $now->format("Y-m-d H:i:s.u");
		Database::Write('task', ['fid', 'noid', 'task', 'workgroup', 'work', 'request', 'time', 'wait_time'], 
			[$fid, $post_data['noid'], $post_data['task'], $post_data['group'], $post_data['work'], $post_data['request'], $t, $t], '');
		echo Utilitario::SaidaPadrao(true, array( "fid" => $fid, 'time' => $t ), "task");
	break;
	case 'do':
		$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
		// Pega o primeiro elemento da atividade
		if(count($dados) > 0){
			$dados = $dados[0];
			// retorno do cliente
			echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
			$now = $now->modify('+5 minutes');
			Database::Write('task', ['fid', 'wait_time'], [$dados['data']['fid'], $now->format("Y-m-d H:i:s.u")], '');
		}
	break;
	case 'next':
		$fields = [];
		$values = [];
		$oper = [];

		if($post_data['task'] != ''){
			array_push($fields, 'task');
			array_push($values, $post_data['task']);
			array_push($oper, '=');
		}

		if($post_data['group'] != ''){
			array_push($fields, 'workgroup');
			array_push($values, $post_data['group']);
			array_push($oper, '=');
		}

		if($post_data['work'] != ''){
			array_push($fields, 'work');
			array_push($values, $post_data['work']);
			array_push($oper, '=');
		}

		array_push($fields, 'wait_time');
		array_push($values, $now->format("Y-m-d H:i:s.u"));
		array_push($oper, '<=');

		// Pega a próxima atividade
		$dados = Database::Data('task', $fields, $values, $oper);
		error_log('Retorno do EDB: ' . json_encode($dados), 0);
		// Pega o primeiro elemento da atividade
		if(count($dados) > 0){
			$dados = $dados[0];

			// retorno do cliente
			echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
			$now = $now->modify('+5 minutes');
			$fields_alteracao  = ['fid', 'wait_time'];
			$values_alteracao = [$dados['data']['fid'], $now->format("Y-m-d H:i:s.u")];

			if(array_key_exists("to_task", $post_data)){
				array_push($fields_alteracao, 'task');
				array_push($values_alteracao, $post_data['to_task']);
			}

			Database::Write('task', $fields_alteracao, $values_alteracao, '');
		}
	break;
	case 'status':
		// .. com FID pesquisa onde está .../
		$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
		if(count($dados) > 0){
			$dados = $dados[0];

			echo Utilitario::SaidaPadrao(true, array( "fid" => $dados['data']['fid'], "task" => $dados['data']['task'], "wait_time" => $dados['data']['wait_time'])  , "entry");
		}
	break;
	case 'get':
		// com FID pega o que tá na posicao .../
		$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
		if(count($dados) > 0){
			$dados = $dados[0];

			echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
		}
	break;
	case 'response':
		// return, fid, task
		$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
		if(count($dados) > 0){
			$dados = $dados[0];

			echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
			Database::Write('task', ['fid', 'wait_time', 'task', 'request', 'response'], 
				[$dados['data']['fid'], $now->format("Y-m-d H:i:s.u"), $post_data['task'], $post_data['response'], $post_data['response'] ], '');
		}

	break;

}

