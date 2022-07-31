<?php

namespace AppBundle\Controller;

use AppBundle\Services\Helpers;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
    
    
    public function testAction(){
        $entityManager  = $this->getDoctrine()->getManager();
        $userRepo       = $entityManager->getRepository("BackendBundle:User");
        $users          = $userRepo->findAll();

        $helpers        = $this->get(Helpers::class);
        echo $helpers->helloWorld();
        die;

        return $this->json(array(
            "status"    => "success",
            "users"     => $users 
        ));

    }
}
