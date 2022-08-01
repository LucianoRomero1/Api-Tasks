<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;


class UserController extends Controller{

    public function registerAction(Request $request){
        $helpers    = $this->get(Helpers::class);

        //Agarro lo que viene por parametro en la request que se llama json y por defecto null
        $json       = $request->get('json', null);
        $params     = json_decode($json); 

        //Error 400 para errores del cliente, error 500 para errores del servidor
        $data       = array(
            'status' => 'error',
            'code'   => 400,
            'msg'    => 'User can not be created'
        );

        if($json != null){
            $createdAt          = new \DateTime("now");
            $role               = 'user';
            $email              = (isset($params->email) ? $params->email : null);
            $name               = (isset($params->name) ? $params->name : null);
            $surname            = (isset($params->surname) ? $params->surname : null);
            $password           = (isset($params->password) ? $params->password : null);

            $emailConstraint    = new Assert\Email();
            $emailConstraint->message = "This email is not valid";
            $validateEmail      = $this->get('validator')->validate($email, $emailConstraint);

            if($email != null && count($validateEmail) == 0 && $password != null && $name != null && $surname != null){
                
                $entityManager  = $this->getDoctrine()->getManager();
                $user           = $entityManager->getRepository(User::class)->findOneBy([
                    "email" => $email
                ]);

                if(is_null($user)){
                    $user = new User();
                    $user->setCreatedAt($createdAt);
                    $user->setRole($role);
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);
                    //La password hay q hashearla

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $data       = array(
                        'status' => 'success',
                        'code'   => 200,
                        'msg'    => 'User created',
                        'data'   => $user
                    );
                }else{
                    $data       = array(
                        'status' => 'error',
                        'code'   => 400,
                        'msg'    => 'The user with this email already exists'
                    );
                }
            }
        }

        return $helpers->json($data);
    }

}