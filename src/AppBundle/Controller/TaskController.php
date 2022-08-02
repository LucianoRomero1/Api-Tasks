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

        $data       = array(
            'status' => 'error',
            'code'   => 400,
            'msg'    => 'Authorization not valid'
        );

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
        }
        ####

        return $helpers->json($data);
    }
}