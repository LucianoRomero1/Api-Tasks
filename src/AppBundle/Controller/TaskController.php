<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;
use BackendBundle\Entity\Task;
use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class TaskController extends Controller{

    public function newAction(Request $request, $id = null){
        #### Con esto valida que esté logueado basicamente
        $helpers    = $this->get(Helpers::class);
        $jwt_auth   = $this->get(JwtAuth::class);       
        
        $token      = $request->get('authorization', null);
        $authCheck  = $jwt_auth->validateToken($token);

        if($authCheck){
            $identity   = $jwt_auth->validateToken($token, true);
            $json       = $request->get('json', null);

            if($json != null){
                $params     = json_decode($json);

                $createdAt  = new \DateTime("now");
                $updatedAt  = new \DateTime("now");

                //Identity sub es el id del user
                $userId     = (isset($identity->sub) ? $identity->sub : null);
                $title      = (isset($params->title) ? $params->title : null);
                $description= (isset($params->description) ? $params->description : null);
                $status     = (isset($params->status) ? $params->status : null);

                if($userId != null && $title != null && $description != null && $status != null){
                    $em     = $this->getDoctrine()->getManager();
                    $user   = $em->getRepository(User::class)->find($userId);

                    if($id == null){
                        $task   = new Task();
                        $task->setUser($user);
                        $task->setTitle($title);
                        $task->setDescription($description);
                        $task->setStatus($status);
                        $task->setCreatedAt($createdAt);
                        $task->setUpdatedAt($updatedAt);

                        $data       = array(
                            'status' => 'success',
                            'code'   => 200,
                            'msg'    => 'Task has been created',
                            'data'   => $task
                        );

                        $em->persist($task);
                        $em->flush();
                    }else{
                        $task = $em->getRepository(Task::class)->find($id);
                        if($task != null){
                            if(isset($identity->sub) && $identity->sub == $task->getUser()->getId()){
                                $task->setTitle($title);
                                $task->setDescription($description);
                                $task->setStatus($status);
                                $task->setUpdatedAt($updatedAt);

                                $data       = array(
                                    'status' => 'success',
                                    'code'   => 200,
                                    'msg'    => 'Task has been updated',
                                    'data'   => $task
                                );
                                
                                $em->persist($task);
                                $em->flush();
                            }else{
                                $data   = array(
                                    'status' => 'error',
                                    'code'   => 400,
                                    'msg'    => 'Task not updated, you not owner'
                                );
                            }
                        }else{
                            $data   = array(
                                'status' => 'error',
                                'code'   => 400,
                                'msg'    => 'Task does not exist'
                            );
                        } 
                    }
                }else{
                    $data   = array(
                        'status' => 'error',
                        'code'   => 400,
                        'msg'    => 'Task not created, validation failed'
                    );
                }
            }else{
                $data       = array(
                    'status' => 'error',
                    'code'   => 400,
                    'msg'    => 'Task can not be created, params failed'
                );
            }
        }else{
            $data       = array(
                'status' => 'error',
                'code'   => 400,
                'msg'    => 'Authorization not valid'
            );
        }
        ####

        return $helpers->json($data);
    }

    public function tasksAction(Request $request){
        $helpers    = $this->get(Helpers::class);
        $jwt_auth   = $this->get(JwtAuth::class);       
        
        $token      = $request->get('authorization', null);
        $authCheck  = $jwt_auth->validateToken($token);

        if($authCheck){
            $identity           = $jwt_auth->validateToken($token, true);

            $em                 = $this->getDoctrine()->getManager();

            $dql                = "SELECT t FROM BackendBundle:Task t ORDER BY t.id DESC";
            $query              = $em->createQuery($dql);

            //Junta los         parámetros GET de la URL
            $page               = $request->query->getInt('page', 1);
            $paginator          = $this->get('knp_paginator');
            $items_per_page     = 10;

            $pagination         = $paginator->paginate($query, $page, $items_per_page);
            $total_items_count  = $pagination->getTotalItemCount();

            $data               = array(
                'status'                    => 'success',
                'code'                      => 200,
                'msg'                       => 'Ok',
                'total_items_count'         => $total_items_count,
                'actual_page'               => $page,
                'items_per_page'            => $items_per_page,
                //ceil para redondear. divide el total de elementos por el total por pagina
                'total_pages'               => ceil($total_items_count / $items_per_page),
                'data'                      => $pagination
            );
        }else{
            $data       = array(
                'status' => 'error',
                'code'   => 400,
                'msg'    => 'Authorization not valid'
            );
        }

        return $helpers->json($data);
    }

    public function taskAction(Request $request, $id = null){
        $helpers    = $this->get(Helpers::class);
        $jwt_auth   = $this->get(JwtAuth::class);

        $token      = $request->get('authorization', null);
        $authCheck  = $jwt_auth->validateToken($token);

        if($authCheck){
            $identity   = $jwt_auth->validateToken($token, true);

            $em         = $this->getDoctrine()->getManager();
            $task       = $em->getRepository(Task::class)->find($id);
            //Solo mostrarle las tareas al dueño de las tareas
            if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){

                $data       = array(
                    'status' => 'success',
                    'code'   => 200,
                    'data'   => $task
                );
            }else{
                $data       = array(
                    'status' => 'error',
                    'code'   => 404,
                    'msg'    => 'Task not found'
                );
            }

        }else{
            $data       = array(
                'status' => 'error',
                'code'   => 400,
                'msg'    => 'Authorization not valid'
            );
        }

        return $helpers->json($data);
    }
}