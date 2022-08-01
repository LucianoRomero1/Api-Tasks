<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
            'data'      => 'Send json via post'
        );

        if($json != null){
            //Hacer el login

            //Crear un objeto con la data recibida en JSON por POST
            $params         = json_decode($json);

            //Valido con ternarios que la data no sea null
            $email          = (isset($params->email)) ? $params->email : null;
            $password       = (isset($params->password)) ? $params->password : null;

            //Validar el email con Assert y Validator
            $emailConstraint= new Assert\Email();
            $emailConstraint->message = "This email is not valid";
            $validateEmail  = $this->get('validator')->validate($email, $emailConstraint);

            //count = 0 significa que todo estuvo bien
            if(count($validateEmail) == 0 && $password != null){
                
                $data       = array(
                    'status'    => 'success',
                    'data'      => 'Login success'
                );
            }else{

                $data       = array(
                    'status'    => 'error',
                    'data'      => 'Email or password incorrect'
                );
            }

            
        }

        return $helpers->json($data);
    }
    
    
    public function testAction(){
        $entityManager  = $this->getDoctrine()->getManager();
        $userRepo       = $entityManager->getRepository("BackendBundle:User");
        $users          = $userRepo->findAll();
        $taskRepo       = $entityManager->getRepository("BackendBundle:Task");
        $tasks          = $taskRepo->findAll();

        $helpers        = $this->get(Helpers::class);

        $array_response = array(
            "status"    => "success",
            "data"      => $tasks
        );

        return $helpers->json($array_response);
        // echo $helpers->json($users);
        // die;

        // return $this->json(array(
        //     "status"    => "success",
        //     "users"     => $users 
        // ));

    }
}
