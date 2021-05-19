<?php
error_reporting(0);

require_once dirname(dirname(dirname(__FILE__))) . "/api/utilitario.php";
require_once dirname(dirname(dirname(__FILE__))) . "/api/edb.php";


class Drivermq {
	static public function exec(  $post_data   ){
		$ROOT = dirname(dirname(dirname(__FILE__)));
		$now = DateTime::createFromFormat('U.u', microtime(true));
		switch ($post_data['action'] ){
			case 'register':
				//
				$path_work = dirname(dirname(dirname(__FILE__))) . "/data/mq/" . $post_data['work'] . ".json";
				$work = Json::FromFile($path_work);
				if($work != null) {
					$fid = strval($now->format("Y-m-d H:i:s.u"));
					if($post_data['fid'] != ""){
						$fid = $post_data['fid'];
					}
					$t  = $now->format("Y-m-d H:i:s.u");
					$buffer = array("fid" => $fid, "noid" => $post_data['noid'], "task" => $work->tasks[0], "workgroup" => $post_data['group'], "work" => $post_data['work'],
					"script" =>  $work->scripts[0],  "request" => [] , "time" => $t, "wait_time" => $t);
					array_push($buffer['request'], $post_data['request'] );
					$path_to_dir = dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/" . $work->tasks[0];
					$path_to_all = dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/no_task_all";
					
					if (!file_exists($path_to_all)) {
						mkdir($path_to_all, 0777, true);
					}
					if (!file_exists($path_to_all . "/" . $fid)) {
						if( JsonV2::WriteFile($path_to_dir, $fid, $buffer) == true) {
							link($path_to_dir . "/" . $fid, $path_to_all . "/" . $fid);
							return  array( "fid" => $fid, 'time' => $t );
						} else {
							throw new Exception('Falha ao salvar a atividade na MQ');
						}
					} else {
						return  array( "fid" => $fid, 'time' => $t );
					}
				} else {
					throw new Exception('Não existe trabalho!');
				}
				break;
			case 'which':
				$retorno = array();
				
				$works = scandir($ROOT . "/data/mq/");
				for($i = 0; $i < count($works); $i++){
					$work = Json::FromFile($ROOT . "/data/mq/" . $works[$i]);
					if($work == null) continue;

					$buffer = array("work" => $work->name, "tasks" => [], "group" => "");
					for($j = 0; $j < count($work->tasks) - 1; $j++){
						for($k = 0; $k < count($post_data['groups']); $k++){
							$diretorio_tarefa = $ROOT . "/tmp/mq/" . $post_data['groups'][$k] . "/" . $work->name  . "/" . $work->tasks[$j];
							if( file_exists(  $diretorio_tarefa ) ){
								if(count(scandir($diretorio_tarefa)) > 2 ) {
									$buffer["group"] = $post_data['groups'][$k];
									array_push($buffer["tasks"],$work->tasks[$j] );
								}
							}
						}
					}
					if(count($buffer["tasks"]) > 0) {
						array_push($retorno, $buffer);
					}
				}
				
				return $retorno;
				break;
			case 'next':
				$path_to_task = dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/" . $post_data['task'] . "/";
				if ($handle = opendir($path_to_task)) {
					try{
						while (false !== ($entry = readdir($handle))) {
							$path_to_file = $path_to_task .  $entry;
							$buffer = JsonV2::FromFile( $path_to_file );
							if($buffer != null){
								$path_work = dirname(dirname(dirname(__FILE__))) . "/data/mq/" . $post_data['work'] . ".json";
								$work = Json::FromFile($path_work);
								unlink($path_to_task . $entry);
								$task_index = array_search($post_data['task'], $work->tasks);
								$buffer->script = $work->scripts[$task_index];
								return $buffer;
							}
						}
					} catch (Exception $e) {
						error_log( 'Exceção capturada: ',  $e->getMessage(), 0);
						throw $e;
					}
					finally {
						closedir($handle);
					}
				} 
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
				$path_to_work = dirname(dirname(dirname(__FILE__))) . "/data/mq/" . $post_data['work'] . ".json";
				$work = JsonV2::FromFile( $path_to_work );
				if($work != null) {
					$task_index = array_search($post_data['task'], $work->tasks) + 1;
					
					$path_to_file 			= dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/no_task_all/".  $post_data['fid'];
					$path_to_new_directory 	= dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/" . $work->tasks[$task_index] . "/";
					$buffer = JsonV2::FromFile( $path_to_file );
					if($buffer == null){
						throw new Exception('Não existe o arquivo:  ' . $post_data['fid']);
					}
					array_push($buffer->request, $post_data['response']);
					
					if(!file_exists($path_to_new_directory)){
						mkdir($path_to_new_directory, 0777, true);
					}
					if ( link(  $path_to_file, $path_to_new_directory . $post_data['fid'] ) == 0) {
						return true;
					} else {
						throw new Exception('Não foi possível salver o trabalho no próximo task');
					}
				} else {
					// work nao encontrado...
				}
			break;
			case 'error':
				$path_to_work = dirname(dirname(dirname(__FILE__))) . "/data/mq/" . $post_data['work'] . ".json";
				$work = JsonV2::FromFile( $path_to_work );
				if($work != null) {
					$path_to_file 			= dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/" . $post_data['task'] . "/".  $post_data['fid'];
					$path_to_new_directory 	= dirname(dirname(dirname(__FILE__))) . "/tmp/mq/". $post_data['group'] . "/" . $post_data['work'] . "/no_task_fail/" . $post_data['task'] . "/";
					$buffer = JsonV2::FromFile( $path_to_file );
					if($buffer == null){
						throw new Exception('Não existe o arquivo:  ' . $post_data['fid']);
					}
					$buffer->output = $post_data['output'];
					$buffer->code = $post_data['code'];
					if(!file_exists($path_to_new_directory)){
						mkdir($path_to_new_directory, 0777, true);
					}
					if ( link($path_to_file, $path_to_new_directory .  $post_data['fid'] ) == 0) {
						return true;
					} else {
						throw new Exception('Não foi possível salver o trabalho no próximo task');
					}
				} else {
					// work nao encontrado...
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