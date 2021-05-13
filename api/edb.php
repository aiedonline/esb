<?php

require_once __DIR__  . '/json.php';

class Database {

    public static function Write($entity, $keys, $values, $hash, $cache=false, $user=null){
        if($user == null){
            error_log('Falta usuário para leitura da entidade: ' . $entity, 0);
        }
        $data_send = [];
        $query = array();
        for($i = 0; $i < count($keys); $i++){
            $query[$keys[$i]] = $values[$i];
        }
        
        array_push($data_send, array( "entity" => $entity, "data" =>  $query, "hash" => $hash  ));
        $buffer = Database::Execute("/aied/esb", "write", $data_send, $cache, $user);
        
        if($buffer != null){
            return $buffer['rows'];
        } else {
            return null;
        }
    }

    public static function Data($entity, $keys, $values, $oper=None, $cache=false, $user=null){
        if($user == null){
            error_log('Falta usuário para leitura da entidade: ' . $entity, 0);
        }

        if($oper == None){
            $oper = [];
            for($i = 0; $i < count($keys); $i++){
                array_push($oper, '=');
            }
        }

        $data_send = [];
        $query = array();
        for($i = 0; $i < count($keys); $i++){
            $query[$keys[$i]] = $values[$i];
        }

        array_push($data_send, array( "entity" => $entity, "data" =>  $query, "operation" => $oper  ));
        $buffer = Database::Execute("/aied/esb", "read", $data_send, $cache, $user);

        if($buffer != null){
            return $buffer['rows'];
        } else {
            return null;
        }
    }
    public static function Execute($domain, $action, $data, $cache, $user ){
        try{
            $CONFIG = Json::FromFile(   dirname(__DIR__, 1) . "/data/config.json");
            
            // The data to send to the API
            $postData = array(
                'token' => '1f56729b-5e63-4685-b2d6-a0890217afe4',
                'domain' => $domain,
                'action' => $action,
                'envelop' => $data,
                'cache' => $cache,
                'user'  => $user
            );

            // Create the context for the request
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode($postData)
                )
            ));

            // Send the request
            $response = file_get_contents($CONFIG->edb . "execute.php", FALSE, $context);
            //error_log('Resposta do EXECUTE: ' .  $response, 0);
            // Check for errors
            if($response === FALSE){
                die('Error');
            }

            try{ // try para tratar só a conversao do json, alguns serviços podem retornar um texto para o genexus
                $buffer_js = json_decode($response, TRUE);
                //if($buffer_js['status'] == 1){
                //    return $buffer_js['data'];
                //}else{
                return $buffer_js;
                //}
            }catch (\Exception $e) {
                error_log($e->getMessage(), 0);
                return $response;
            }

        }catch (\Exception $e) {
            error_log($e->getMessage(), 0);
            return $e;
        }
    }
}


?>