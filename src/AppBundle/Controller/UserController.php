<?php

namespace AppBundle\Controller;

use AppBundle\Handlers\UserHandler;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;


class UserController extends Controller{

    public function registerAction(Request $request){
        $entityManager  = $this->getDoctrine()->getManager();
        $helpers    = $this->get(Helpers::class);

        //Agarro lo que viene por parametro en la request que se llama json y por defecto null
        $json       = $request->get('json', null);
        
        //Error 400 para errores del cliente, error 500 para errores del servidor
        $data       = array(
            'status' => 'error',
            'code'   => 400,
            'msg'    => 'User can not be created, params failed'
        );

        if($json != null){
            $params             = json_decode($json); 
            $handler            = $this->get(UserHandler::class);

            if($handler->validateUserParams($params)){
                //Si es true, seteo el user
                $data = $handler->setUser($params);
            }
        }

        return $helpers->json($data);
    }

    //Hago lo del token porque necesito validar que este logueado para editarse
    public function editAction(Request $request){
        $helpers    = $this->get(Helpers::class);
        //Validar que el token que llega es correcto
        $jwt_auth   = $this->get(JwtAuth::class);
        //Recibo el token llamado authorization y sino llega es null
        $token      = $request->get('authorization', null);
        $authCheck  = $jwt_auth->validateToken($token);

        $data       = array(
            'status' => 'error',
            'code'   => 400,
            'msg'    => 'Authorization not valid'
        );

        //Valida el token y si es correcto hago todo el edit
        if($authCheck){
            $entityManager  = $this->getDoctrine()->getManager();
            //Con un true devuelve el user decodificado
            $identity       = $jwt_auth->validateToken($token, true);

            //get al user logueado
            $user           = $entityManager->getRepository(User::class)->find($identity->sub);

            //Agarro lo que viene por parametro en la request que se llama json y por defecto null
            $json           = $request->get('json', null);
            
            if($json != null){
                $params     = json_decode($json); 
                $handler    = $this->get(UserHandler::class);

                if($handler->validateUserParams($params)){
                    //Si es true, seteo el user
                    $data = $handler->setUser($params, $identity, $user);
                }

            }
        }

        return $helpers->json($data);
    }

}