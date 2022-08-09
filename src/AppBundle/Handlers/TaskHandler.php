<?php

namespace AppBundle\Handlers;

use BackendBundle\Entity\Task;
use BackendBundle\Entity\User;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TaskHandler extends Controller{
    private $manager;
    private $paginator;

    public function __construct($manager, $paginator){
        $this->manager = $manager;
        $this->paginator = $paginator;
    }

    public function validateTask($params, $identity){
       
        //Identity sub es el id del user
        $userId     = (isset($identity->sub) ? $identity->sub : null);
        $title      = (isset($params->title) ? $params->title : null);
        $description= (isset($params->description) ? $params->description : null);
        $status     = (isset($params->status) ? $params->status : null);

        if($userId != null && $title != null && $description != null && $status != null){
            return true;
        }else{
            return false;
        }

    }

    public function setTask($params, $identity, $id = null){
        $em     = $this->manager;

        $user   = $em->getRepository(User::class)->find($identity->sub);

        if($id == null){
            $data = $this->newTask($params, $user, $em);
        }else{
            $data = $this->editTask($params, $id, $identity, $em);  
        }

        return $data;
    }

    public function newTask($params, $user, $em){
        $createdAt  = new \DateTime("now");
        $updatedAt  = new \DateTime("now");

        $task   = new Task();
        $task->setUser($user);
        $task->setTitle($params->title);
        $task->setDescription($params->description);
        $task->setStatus($params->status);
        $task->setCreatedAt($createdAt);
        $task->setUpdatedAt($updatedAt);

        $em->persist($task);
        $em->flush();

        $data       = array(
            'status' => 'success',
            'code'   => 200,
            'msg'    => 'Task has been created',
            'data'   => $task
        );

        return $data;
    }

    public function editTask($params, $id, $identity, $em){
        $task = $em->getRepository(Task::class)->find($id);
        if($task != null){
            if(isset($identity->sub) && $identity->sub == $task->getUser()->getId()){
                $updatedAt  = new \DateTime("now");

                $task->setTitle($params->title);
                $task->setDescription($params->description);
                $task->setStatus($params->status);
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

        return $data;
    }

    public function listTasks($jwt_auth, $token, $request){
        $identity           = $jwt_auth->validateToken($token, true);

        $em                 = $this->manager;

        $dql                = "SELECT t FROM BackendBundle:Task t WHERE t.user = $identity->sub ORDER BY t.status DESC";
        $query              = $em->createQuery($dql);

        //Junta los         parámetros GET de la URL
        $page               = $request->query->getInt('page', 1);
 
        $paginator          = $this->paginator;
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

        return $data;
    }

    public function detailTask($jwt_auth, $token, $id){
        $identity   = $jwt_auth->validateToken($token, true);

        $em         = $this->manager;
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

        return $data;
    }

    public function searchTask($dql, $filter, $search){
        $em     = $this->manager;
        $query  = $em->createQuery($dql);

        if(!empty($filter)){
            $query->setParameter('filter', $filter);
        }
        
        if(!empty($search)){
            //Los % son para que te coincida con los substring dentro de la palabra
            $query->setParameter('search', "%$search%");
        }

        $tasks = $query->getResult();

        $data       = array(
            'status' => 'success',
            'code'   => 200,
            'data'    => $tasks
        );

        return $data;
    }

    public function getFilter($request){
        $filter     = $request->get('filter', null);
        if(!empty($filter)){
            if($filter == 1){
                $filter = 'new';
            }elseif($filter == 2){
                $filter = 'to do';
            }else{
                $filter = 'done';
            }
        } 

        return $filter;
    }

    public function getOrder($request){
        $order      = $request->get('order', null);
        if(empty($order) || $order == 2){
            $order  = 'DESC';
        }else{
            $order  = 'ASC';
        }

        return $order;
    }

    public function setFilterAndOrder($dql, $filter, $order){
        //Set filter
        if($filter != null){
            //El .= es para concatenarle a lo que ya estaba en el string
            $dql.= "AND t.status = :filter";
        }

        //Set order
        $dql.= " ORDER BY t.id $order";

        return $dql;
    }

    public function getDql($identity, $search){
        //Busqueda
         if($search != null){
            $dql    = "SELECT t FROM BackendBundle:Task t "
                    . "WHERE t.user = $identity->sub AND "
                    . "(t.title LIKE :search OR t.description LIKE :search)";
        }else{
            $dql    = "SELECT t FROM BackendBundle:Task t WHERE t.user = $identity->sub";
        }

        return $dql;
    }

    public function deleteTask($jwt_auth, $token, $id){
        $identity   = $jwt_auth->validateToken($token, true);

        $em         = $this->manager;
        $task       = $em->getRepository(Task::class)->find($id);
        //Solo mostrarle las tareas al dueño de las tareas
        if($task && is_object($task) && $identity->sub == $task->getUser()->getId()){

            $em->remove($task);
            $em->flush();

            $data       = array(
                'status' => 'success',
                'code'   => 200,
                'msg'    => 'Task deleted successfully',
                'data'   => $task
            );
        }else{
            $data       = array(
                'status' => 'error',
                'code'   => 404,
                'msg'    => 'Task not found'
            );
        }

        return $data;
    }
}