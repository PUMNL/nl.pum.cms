<?php

class CRM_CMS_MailError
{
    private static $_instance = null;

    public function setError($error){
       $this::$_instance=$error;
    }

    public function getError(){
        return $this::$_instance;
    }
}