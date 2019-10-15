<?php
/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */

class CRM_CMS_Rest {

  // echo CRM_Core_BAO_Setting::getItem('Drupal CMS Api','drupal_cms_url');
  var $url;

  /**
   * CRM_CMS_Rest constructor.
   *
   * @param $url
   */
  public function __construct($url) {
    $this->url = $url;
  }

  public function post($path,$message){
    $data_string = json_encode($message);
    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );
    $result = curl_exec($ch);
  }

  public function get($path){

    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
      )
    );
    $result = curl_exec($ch);
    return json_decode($result);
  }

}
