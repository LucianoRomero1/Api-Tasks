<?php

namespace AppBundle\Handlers;

use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class UserHandler extends Controller{
    private $manager;

    public function __construct($manager){
        $this->manager = $manager;
    }

    public function validateUserParams($params){
        $validator      = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();

        $email              = (isset($params->email) ? $params->email : null);
        $name               = (isset($params->name) ? $params->name : null);
        $surname            = (isset($params->surname) ? $params->surname : null);
        $password           = (isset($params->password) ? $params->password : null);

        $emailConstraint    = new Assert\Email();
        $emailConstraint->message = "This email is not valid";
        $validateEmail      = $validator->validate($email, $emailConstraint);

        if($email != null && count($validateEmail) == 0 && $password != null && $name != null && $surname != null){
            //Parámetros válidos
            return true;
        }else{
            //Hay parámetros inválidos
            return false;
        }
    }

    public function setUser($params, $identity = null, $user = null){

        $entityManager = $this->manager;

        $data           = array(
            'status' => 'error',
            'code'   => 400,
            'msg'    => 'The user with this email already exists'
        );

        $isset_user     = $entityManager->getRepository(User::class)->findBy(["email" => $params->email]);
        //Pongo el isset para que valide si existe el identity
        if(count($isset_user) == 0 || isset($identity->email) == $params->email){
            //Si es null es porque viene de un nuevo registro
            if($user == null){
                $user = $this->newUser($params);
                $data       = array(
                    'status' => 'success',
                    'code'   => 200,
                    'msg'    => 'User created',
                    'data'   => $user
                );
            }else{
                //Viene del edit
                $user = $this->editUser($user, $params);
                $data       = array(
                    'status' => 'success',
                    'code'   => 200,
                    'msg'    => 'User updated',
                    'data'   => $user
                );             
            }

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $data;

    }

    public function newUser($params){
        $createdAt      = new \DateTime("now");
        $role           = 'user';

        $user = new User();
        $user->setCreatedAt($createdAt);
        $user->setRole($role);
        $user->setEmail($params->email);
        $user->setName($params->name);
        $user->setSurname($params->surname);
        
        //cifrar password, sha256 es un algoritmo de encriptacion
        $pwd = hash('sha256', $params->password);
        //pwd es la pw ya hasheada
        $user->setPassword($pwd);

        return $user;
    }

    public function editUser($user, $params){
        $role = 'user';
        $user->setRole($role);
        $user->setEmail($params->email);
        $user->setName($params->name);
        $user->setSurname($params->surname);

        $pwd = hash('sha256', $params->password);
        $user->setPassword($pwd);

        return $user;
    }
}