<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 06-Dec-2019
 * @license  AGPL-3.0
 */

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