<?php

/**
 * @author Klaas Eikelboom  <klaas@kainuk.it>
 * @date 15-Oct-2019
 * @license AGPL-3.0
 */

class CRM_CMS_Rest {

  // echo CRM_Core_BAO_Setting::getItem('Drupal CMS Api','drupal_cms_url');
  var $url;
  var $token;
  var $basicauth;

  /**
   * CRM_CMS_Rest constructor.
   */
  public function __construct() {
    $cmsSettings = CRM_Core_BAO_Setting::getItem('Drupal CMS Api');
    $this->url=$cmsSettings['drupal_cms_url'];
    $this->token=$cmsSettings['drupal_cms_authtoken'];
    $this->basicauth=$cmsSettings['drupal_cms_basicauth'];
  }

  public function post($path){

    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'authorization: Basic '.$this->basicauth,
        'ApiToken: '.$this->token,
        ));
    $result = curl_exec($ch);
    $this->checkResponse($path, $ch);
    return json_decode($result,true);
  }

  public function put($path,$message){

    $data_string = json_encode($message);
    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string),
      'authorization: Basic '.$this->basicauth,
      'ApiToken: '.$this->token,
    ));
    $result =  curl_exec($ch);
    return json_decode($result,true);

  }

  public function get($path){

    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'authorization: Basic '.$this->basicauth,
        'ApiToken: '.$this->token,
      )
    );

    $result = curl_exec($ch);
    $this->checkResponse($path, $ch);
    return json_decode($result,true);
  }

  public function getAll($entity){
      return $this->get("/api/v1/{$entity}?page_index=0&page_size=1000000");
  }

  public function getBlob($path, $file, $image = false){
      $ch = curl_init($this->url . $path);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'ApiToken: '.$this->token,
              'authorization: Basic '.$this->basicauth,
          )
      );
      $result = curl_exec($ch);
      if($image) {
          $blob = json_decode($result, true)['Content'];
          $start = strpos($blob, ',');
          file_put_contents($file, base64_decode(substr($blob,$start+1)));
      } else {
          file_put_contents($file,$result);
      }

      $this->checkResponse($path, $ch);
      return $file;
  }

  public function create($entity,$fields) {
    $result = $this->post("/api/v1/{$entity}/");
    $id=$result['Id'];
    $fields['Id'] = $id;
    return $this->put("/api/v1/{$entity}/",$fields);
  }

  public function update($entity,$id,$fields) {
        $fields['Id'] = $id;
        return $this->put("/api/v1/{$entity}/",$fields);
  }

  public function delete($entity,$id){
      $ch = curl_init($this->url . "/api/v1/{$entity}/{$id}");
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'ApiToken: '.$this->token,
              'authorization: Basic '.$this->basicauth,
          )
      );
      $result = curl_exec($ch);
      $this->checkResponse($this->url . "/api/v1/{$entity}/{$id}", $ch);
  }

    /**
     * @param $path
     * @param $ch
     * @throws Exception
     */
    public function checkResponse($path, $ch)
    {
        $httpResponse = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpResponse != 200 && $httpResponse != 401) {
            throw new Exception('REST Call ' . $this->url . $path . 'does not return a 200 https response ');
        }
    }
}
