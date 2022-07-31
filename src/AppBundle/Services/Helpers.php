<?php

namespace AppBundle\Services;

class Helpers{

    private $manager;

    public function __construct($manager){
        $this->manager = $manager;
    }

    public function helloWorld(){
        return "Hello world from my services of Symfony";
    }

}