<?php

namespace AppBundle\Handlers;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class DefaultHandler extends Controller{
    private $manager;

    public function __construct($manager){
        $this->manager = $manager;
    }

    public function validateLoginParams($params){

        $validator      = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();

        //Valido con ternarios que la data no sea null
        $email          = (isset($params->email)) ? $params->email : null;
        $password       = (isset($params->password)) ? $params->password : null;
        $getHash        = (isset($params->getHash)) ? $params->getHash : null;

        //Validar el email con Assert y Validator
        $emailConstraint= new Assert\Email();
        $emailConstraint->message = "This email is not valid";
        $validateEmail  = $validator->validate($email, $emailConstraint);

        //Cifrar password
        $pwd            =  hash('sha256', $password);

        $validatedParams= array();
        
        if($email != null && count($validateEmail) == 0 && $password != null){
            //Si no hay errores validando los parámetros retorno los parámetros validados
            array_push($validatedParams, $email, $pwd, $getHash); 
            return $validatedParams;
        }else{
            //Lo devuelvo vacío
            return $validatedParams;
        }
    }

    //Valida si se le envía el hash como parámetro o no
    public function loginWithHash($jwt_auth, $params){
        $email      = $params[0];
        //$pwd es la password hasheada
        $pwd        = $params[1];
        $getHash    = $params[2];

        //Sino especifico el hash, devuelvo el token, sino devuelvo la data del user logueado
        if($getHash == null || !$getHash){
            $signup     = $jwt_auth->signup($email, $pwd);
        }else{
            $signup     = $jwt_auth->signup($email, $pwd, true);
        }

        return $signup;
    }
}