<?php 

namespace AdzHive\helpers;

class RESTHelper extends \AdzHive\Adz {

  public $type = 'json';

  public $client;

  public $url;

  public $data;

  public $result;

  public $options = [];
  
  public function init()
  {
    $curl = \curl_init();
 
    if ($this->data)
      $url = sprintf("%s?%s", $this->url, http_build_query($this->data));
 
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      //  'APIKEY: 111111111111111111111',
       'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
 
    $result = curl_exec($curl);
    if(!$result) 
      die("Connection Failure");
    curl_close($curl);
    $this->client = $result;
  }

 public function option()
 {

 }

 public function delete()
 {

 }

 public function post()
 {

 }

 public function put()
 {
   
 }

 public function update()
 {

 }



}