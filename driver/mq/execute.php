<?php
require_once dirname(dirname(dirname(__FILE__))) . "/api/utilitario.php";
//require_once dirname(dirname(dirname(__FILE__))) . "/api/json_v2.php";
require_once dirname(dirname(dirname(__FILE__))) . "/api/edb.php";

// teste


class Drivermq {
	static public function exec(  $post_data   ){
		$ROOT = dirname(dirname(dirname(__FILE__)));
		$now = DateTime::createFromFormat('U.u', microtime(true));
		switch ($post_data['action'] ){
			case 'register':
				$path_work = dirname(dirname(dirname(__FILE__))) . "/data/mq/" . $post_data['work'] . ".json";
				$work = Json::FromFile($path_work);
				if($work != null) {
					$fid = $now->format("Y-m-d H:i:s.u"); //microtime(true) * 1000000;
					$t  = $now->format("Y-m-d H:i:s.u");
					$buffer = array("fid" => $fid, "noid" => $post_data['noid'], "task" => $work->tasks[0], "workgroup" => $post_data['group'], "work" => $post_data['work'],
					"script" =>  $work->scripts[0], "request" => $post_data['request'], "time" => $t, "wait_time" => $t);
					if( JsonV2::WriteFile(dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/" . $work->tasks[0], strval($fid), $buffer) == true) {
						return  array( "fid" => $fid, 'time' => $t );
					} else {
						throw new Exception('Falha ao salvar a atividade na MQ');
					}
				} else {
					throw new Exception('Não existe trabalho!');
				}
				break;
			case 'do':
				//$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
				// Pega o primeiro elemento da atividade
				//if(count($dados) > 0){
				//	$dados = $dados[0];
				//	// retorno do cliente
				//	echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
				//	$now = $now->modify('+5 minutes');
				//	Database::Write('task', ['fid', 'wait_time'], [$dados['data']['fid'], $now->format("Y-m-d H:i:s.u")], '');
				//}
			break;
			case 'which':
				$retorno = [];
				
				$works = scandir($ROOT . "/data/mq/");
				
				for($i = 0; $i < count($works); $i++){
					error_log( $ROOT . "/data/mq/" . $works[$i], 0);
					$work = Json::FromFile($ROOT . "/data/mq/" . $works[$i]);
					if($work == null) continue;

					$buffer = array("work" => $work->name, "tasks" => [], "group" => "");
					for($j = 0; $j < count($work->tasks) - 1; $j++){
						for($k = 0; $k < count($post_data['groups']); $k++){
							error_log($ROOT . "/tmp/mq/" . $post_data['groups'][$k] . "/" . $work->tasks[$j], 0);
							if( file_exists($ROOT . "/tmp/mq/" . $post_data['groups'][$k] . "/" . $work->tasks[$j] ) == true){
								if(count(scandir($ROOT . "/tmp/mq/" . $post_data['groups'][$k] . "/" . $work->tasks[$j] )) > 0 ) {
									$buffer["group"] = $post_data['groups'][$k];
									$buffer["tasks"].append($work->tasks[$j]);
								}
								
							}
						}
					}
					if(count($buffer["tasks"]) > 0){
						$retorno.append($buffer);
					}
				}
				
				return $retorno;
				break;
			case 'next':
				


				/*if ($handle = opendir(dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['task'])) {
					try{
						while (false !== ($entry = readdir($handle))) {
							$path_to_file = dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['task'] . "/".  $entry;
							$buffer = JsonV2::FromFile( $path_to_file );
							if($buffer != null){
								if( date("Y-m-d H:i:s.u", $buffer->wait_time) < $now ){
									// achou.... já trava uma data
									$now->modify('+5 minutes');
									$buffer->wait_time = $now->format("Y-m-d H:i:s.u");
									JsonV2::WriteFile(dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['task'], $entry, $buffer);
									return $buffer;
								}
							}
						}
					} catch (Exception $e) {
						error_log( 'Exceção capturada: ',  $e->getMessage(), 0);
						throw $e;
					}
					finally {
						closedir($handle);
						error_log("Diretório fechado");
					}
				} */
			break;
			case 'status':
				// .. com FID pesquisa onde está .../
				//$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
				//if(count($dados) > 0){
				//	$dados = $dados[0];
				//	echo Utilitario::SaidaPadrao(true, array( "fid" => $dados['data']['fid'], "task" => $dados['data']['task'], "wait_time" => $dados['data']['wait_time'])  , "entry");
				//}
			break;
			case 'get':
				// com FID pega o que tá na posicao .../
				//$dados = Database::Data('task', ['fid'], [$post_data['fid']]);
				//if(count($dados) > 0){
				//	$dados = $dados[0];
				//	echo Utilitario::SaidaPadrao(true, $dados['data'], "entry");
				//}
			break;
			case 'response':
				$path_to_file = dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['task_from'] . "/".  $post_data['fid'];
				$buffer = JsonV2::FromFile( $path_to_file );
				error_log( $path_to_file , 0);
				if($buffer == null){
					throw new Exception('Não existe o arquivo:  ' . $post_data['fid']);
				}
				$buffer->request = $post_data['request'];
				$buffer->response = $post_data['response'];
				if ( JsonV2::WriteFile(dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['task_to'], $post_data['fid'], $buffer) == true) {
					unlink($path_to_file);
					return true;
				} else {
					throw new Exception('Não foi possível salver o trabalho no próximo task');
				}
			break;
		}

	}
}

/**
 * 
 * 
 * 
 */