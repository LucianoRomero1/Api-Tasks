<?php

namespace AppBundle\Controller;

use AppBundle\Handlers\DefaultHandler;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;


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
            $handler        = $this->get(DefaultHandler::class);

            $validatedParams= $handler->validateLoginParams($params);
            if(!empty($validatedParams)){
                //Si el array no vino vacío
                $jwt_auth   = $this->get(JwtAuth::class);
                $signup     = $handler->loginWithHash($jwt_auth, $validatedParams);
                
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
