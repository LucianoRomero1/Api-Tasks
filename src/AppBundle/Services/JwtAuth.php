<?php

namespace AppBundle\Services;

use Firebase\JWT\JWT;

class JwtAuth {

    private $manager;
    private $key;

    public function __construct($manager){
        $this->manager  = $manager;
        $this->key      = "im*the*secret*key*123*";
    }

    public function signup($email, $password, $getHash = null){

        $data   = array();

        $user   = $this->manager->getRepository("BackendBundle:User")->findOneBy([
            "email"=>$email, "password"=>$password
        ]);

        if(is_object($user)){

            //Generar jwt
            //iat es un indice asociado a la fecha creada
            //exp es la fecha de expiración del token sumado una semana, es decir se caduca cada semana
            $token  = array(
                'sub'       => $user->getId(),
                'email'     => $user->getEmail(),
                'name'      => $user->getName(),
                'surname'   => $user->getSurname(),
                'iat'       => time(),
                'exp'       => time() + (7 * 24 * 60 * 60)
            );

            //Token tiene toda la data, la key es la clave secreta, algoritmo de codificacion, 
            $jwt    = JWT::encode($token, $this->key, "HS256");
        
            //El gethash sirve para ver si queremos la data del usuario logueado, sino devuelve el token
            if($getHash == null){
                $data = $jwt;
            }else{
                //Quiero la información decodificada
                $decoded    = JWT::decode($jwt, $this->key, array("HS256"));
                $data       = $decoded;
            }

        }else{
            $data = array(
                'status'    => 'error',
                'data'      => 'Login failed'
            );
        }

        return $data;
    }

    public function validateToken($jwt, $getIdentity = false){

        $auth = false;

        try {
            $decoded= JWT::decode($jwt, $this->key, array("HS256"));
        } catch (\Throwable $th) {
            $auth = false;
        }

        //Si existe, es un objeto, es decir se decodifico bien y el ID está definido. Sub es el ID del user
        if(isset($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if(!$getIdentity){
            //Devuelve true o false para validar el token
            return $auth;
        }else{
            //Devuelve los datos del user logueado
            return $decoded;
        }
    }
}