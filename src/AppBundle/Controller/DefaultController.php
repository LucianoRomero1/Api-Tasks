<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }

    public function loginAction(Request $request){
        $helpers    = $this->get(Helpers::class);

        //recibir un json por POST
        //Pongo null por defecto sino trae nada
        $json       = $request->get('json', null);

        //Array a devolver por defecto
        $data       = array(
            'status'    => 'error',
            'data'      => 'Parameters do not exist'
        );

        if($json != null){
            //Hacer el login

            //Crear un objeto con la data recibida en JSON por POST
            $params         = json_decode($json);

            //Valido con ternarios que la data no sea null
            $email          = (isset($params->email)) ? $params->email : null;
            $password       = (isset($params->password)) ? $params->password : null;
            $getHash        = (isset($params->getHash)) ? $params->getHash : null;

            //Validar el email con Assert y Validator
            $emailConstraint= new Assert\Email();
            $emailConstraint->message = "This email is not valid";
            $validateEmail  = $this->get('validator')->validate($email, $emailConstraint);

            //Cifrar password
            $pwd            =  hash('sha256', $password);

            //count = 0 significa que todo estuvo bien
            if($email != null && count($validateEmail) == 0 && $password != null){

                $jwt_auth   = $this->get(JwtAuth::class);

                //Sino especifico el hash, devuelvo el token, sino devuelvo la data del user logueado
                if($getHash == null || !$getHash){
                    $signup     = $jwt_auth->signup($email, $pwd);
                }else{
                    $signup     = $jwt_auth->signup($email, $pwd, true);
                }
                
                
                return $this->json($signup);
            }else{

                $data       = array(
                    'status'    => 'error',
                    'data'      => 'Email or password incorrect'
                );
            }

            
        }

        return $helpers->json($data);
    }
    
    
    public function testAction(Request $request){

        $token          = $request->get("authorization", null);
        $helpers        = $this->get(Helpers::class);
        $jwt_auth       = $this->get(JwtAuth::class);

        //Ademas de recibir el token, hay que comprobar que el token sea valido
        if($token && $jwt_auth->validateToken($token)){
            $entityManager  = $this->getDoctrine()->getManager();
            $userRepo       = $entityManager->getRepository("BackendBundle:User");
            $users          = $userRepo->findAll();
    
            $array_response = array(
                'status'    => 'success',
                'data'      => $users
            );
        }else{
            $array_response = array(
                'status'    => 'error',
                'code'      => 400,
                'data'      => 'Authorization not valid'
            );
        }

        return $helpers->json($array_response);
    }
}
