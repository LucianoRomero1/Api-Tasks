<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
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
                
                $user = new User();
                $user->setCreatedAt($createdAt);
                $user->setRole($role);
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);
                
                //cifrar password, sha256 es un algoritmo de encriptacion
                $pwd = hash('sha256', $password);
                //pwd es la pw ya hasheada
                $user->setPassword($pwd);

                $isset_user     = $entityManager->getRepository(User::class)->findOneBy([
                    "email" => $email
                ]);

                if(count($isset_user) == 0){
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

    //Hago lo del token porque necesito validar que este logueado para editarse
    public function editAction(Request $request){
        $helpers    = $this->get(Helpers::class);

        //Validar que el token que llega es correcto
        $jwt_auth   = $this->get(JwtAuth::class);

        //Recibo el token llamado authorization y sino llega es null
        $token      = $request->get('authorization', null);
        $authCheck  = $jwt_auth->validateToken($token);

        //Valida el token y si es correcto hago todo el edit
        if($authCheck){

            $entityManager  = $this->getDoctrine()->getManager();

            //Conseguir los datos del user logueado
            //Al mandarle un true, nos devuelve un objeto osea el user decodificado
            $identity       = $authCheck  = $jwt_auth->validateToken($token, true);

            //get al user logueado
            $user           = $entityManager->getRepository(User::class)->find($identity->sub);

            //Agarro lo que viene por parametro en la request que se llama json y por defecto null
            $json       = $request->get('json', null);
            $params     = json_decode($json); 

            //Error 400 para errores del cliente, error 500 para errores del servidor
            $data       = array(
                'status' => 'error',
                'code'   => 400,
                'msg'    => 'User can not be updated'
            );

            if($json != null){
                //$createdAt          = new \DateTime("now");
                $role               = 'user';
                $email              = (isset($params->email) ? $params->email : null);
                $name               = (isset($params->name) ? $params->name : null);
                $surname            = (isset($params->surname) ? $params->surname : null);
                $password           = (isset($params->password) ? $params->password : null);
    
                $emailConstraint    = new Assert\Email();
                $emailConstraint->message = "This email is not valid";
                $validateEmail      = $this->get('validator')->validate($email, $emailConstraint);
                
                //Saco la password de acá porque va a venir vacia al estar hasheada
                if($email != null && count($validateEmail) == 0 && $name != null && $surname != null){
                    
                    //$user->setCreatedAt($createdAt);
                    $user->setRole($role);
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);

                    if($password != null){
                        //cifrar password, sha256 es un algoritmo de encriptacion
                        $pwd = hash('sha256', $password);
                        //pwd es la pw ya hasheada
                        $user->setPassword($pwd);
                    }
                    
                

                    $isset_user     = $entityManager->getRepository(User::class)->findOneBy([
                        "email" => $email
                    ]);
    
                    if(count($isset_user) == 0 || $identity->email == $email){
                        $entityManager->persist($user);
                        $entityManager->flush();
    
                        $data       = array(
                            'status' => 'success',
                            'code'   => 200,
                            'msg'    => 'User updated',
                            'data'   => $user
                        );
                    }else{
                        $data       = array(
                            'status' => 'error',
                            'code'   => 400,
                            'msg'    => 'The user can not be updated, this email already has been registered'
                        );
                    }
                }
            }
        }else{
            //El token no es correcto osea usuario no logueado
            $data       = array(
                'status' => 'error',
                'code'   => 400,
                'msg'    => 'Authorization not valid'
            );
        }

        

       

        return $helpers->json($data);
    }

}