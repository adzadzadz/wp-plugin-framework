<?php 

namespace AdzHive\helpers;

class RESTApiHelper extends \AdzHive\Adz {

  public static function getREST(String $url, Array $data = []) {
    $curl = \curl_init();
 
    if ($data)
      $url = sprintf("%s?%s", $url, http_build_query($data));
 
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
    return $result;
 }

}