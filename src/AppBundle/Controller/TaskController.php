<?php

namespace AppBundle\Controller;

use AppBundle\Handlers\TaskHandler;
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
                $handler    = $this->get(TaskHandler::class);

                if($handler->validateTask($params, $identity)){
                    if($id == null){
                        $data = $handler->setTask($params, $identity);
                    }else{
                        $data = $handler->setTask($params, $identity, $id);
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

        return $helpers->json($data);
    }

    public function tasksAction(Request $request){
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
            $handler    = $this->get(TaskHandler::class);
            $data               = $handler->listTasks($jwt_auth, $token, $request);
        }

        return $helpers->json($data);
    }

    public function taskAction(Request $request, $id = null){
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
            $handler    = $this->get(TaskHandler::class);
            $data               = $handler->detailTask($jwt_auth, $token, $id); 
        }

        return $helpers->json($data);
    }

    public function searchAction(Request $request, $search = null){
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

            $handler    = $this->get(TaskHandler::class);
            $filter     = $handler->getFilter($request);
            $order      = $handler->getOrder($request);
            $dql        = $handler->getDql($identity, $search);
            
            //Set Filter and Order
            $dql        = $handler->setFilterAndOrder($dql, $filter, $order);

            $data       = $handler->searchTask($dql, $filter, $search);

        }

        return $helpers->json($data);
    }

    public function deleteAction(Request $request, $id = null){
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
            $handler = $this->get(TaskHandler::class);
            $data   = $handler->deleteTask($jwt_auth, $token, $id);
        }

        return $helpers->json($data);
    }   
}