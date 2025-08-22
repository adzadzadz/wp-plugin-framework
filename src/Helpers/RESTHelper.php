<?php 

namespace AdzWP\Helpers;

class RESTHelper {

  public $type = 'json';
  public $client;
  public $url;
  public $data = [];
  public $headers = [];
  public $result;
  public $options = [];
  public $method = 'GET';
  public $timeout = 30;
  public $verify_ssl = true;
  
  protected $curl;
  protected $response_headers = [];
  protected $http_code;
  protected $error;
  
  public function __construct($url = null)
  {
    if ($url) {
      $this->url = $url;
    }
    
    $this->headers = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json'
    ];
  }
  
  public function init()
  {
    return $this->execute();
  }
  
  protected function execute()
  {
    $this->curl = curl_init();
    
    $this->setCommonOptions();
    
    switch (strtoupper($this->method)) {
      case 'POST':
        curl_setopt($this->curl, CURLOPT_POST, true);
        if (!empty($this->data)) {
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->prepareData());
        }
        break;
        
      case 'PUT':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        if (!empty($this->data)) {
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->prepareData());
        }
        break;
        
      case 'DELETE':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($this->data)) {
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->prepareData());
        }
        break;
        
      case 'PATCH':
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if (!empty($this->data)) {
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->prepareData());
        }
        break;
        
      case 'GET':
      default:
        if (!empty($this->data)) {
          $separator = parse_url($this->url, PHP_URL_QUERY) ? '&' : '?';
          $this->url .= $separator . http_build_query($this->data);
        }
        break;
    }
    
    curl_setopt($this->curl, CURLOPT_URL, $this->url);
    
    $result = curl_exec($this->curl);
    $this->http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    
    if ($result === false) {
      $this->error = curl_error($this->curl);
      curl_close($this->curl);
      return $this->handleError();
    }
    
    curl_close($this->curl);
    
    $this->result = $result;
    $this->client = $this->parseResponse($result);
    
    return $this;
  }
  
  protected function setCommonOptions()
  {
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);
    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($this->curl, CURLOPT_MAXREDIRS, 3);
    
    curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, [$this, 'headerCallback']);
    
    if (!empty($this->headers)) {
      $headers = [];
      foreach ($this->headers as $key => $value) {
        $headers[] = "$key: $value";
      }
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
    }
    
    foreach ($this->options as $option => $value) {
      curl_setopt($this->curl, $option, $value);
    }
  }
  
  protected function headerCallback($curl, $header)
  {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    
    if (count($header) < 2) {
      return $len;
    }
    
    $this->response_headers[strtolower(trim($header[0]))] = trim($header[1]);
    
    return $len;
  }
  
  protected function prepareData()
  {
    if ($this->type === 'json') {
      return json_encode($this->data);
    }
    
    return http_build_query($this->data);
  }
  
  protected function parseResponse($response)
  {
    if ($this->type === 'json') {
      $decoded = json_decode($response, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }
    
    return $response;
  }
  
  protected function handleError()
  {
    if (function_exists('wp_die')) {
      wp_die("Connection Failure: " . $this->error);
    } else {
      throw new \Exception("Connection Failure: " . $this->error);
    }
  }

 public function setOption($option, $value)
 {
   $this->options[$option] = $value;
   return $this;
 }

 public function delete($url = null, $data = [])
 {
   if ($url) {
     $this->url = $url;
   }
   
   $this->method = 'DELETE';
   $this->data = $data;
   
   return $this->execute();
 }

 public function post($url = null, $data = [])
 {
   if ($url) {
     $this->url = $url;
   }
   
   $this->method = 'POST';
   $this->data = $data;
   
   return $this->execute();
 }

 public function put($url = null, $data = [])
 {
   if ($url) {
     $this->url = $url;
   }
   
   $this->method = 'PUT';
   $this->data = $data;
   
   return $this->execute();
 }

 public function patch($url = null, $data = [])
 {
   if ($url) {
     $this->url = $url;
   }
   
   $this->method = 'PATCH';
   $this->data = $data;
   
   return $this->execute();
 }



 public function get($url = null, $data = [])
 {
   if ($url) {
     $this->url = $url;
   }
   
   $this->method = 'GET';
   $this->data = $data;
   
   return $this->execute();
 }
 
 public function setHeader($key, $value)
 {
   $this->headers[$key] = $value;
   return $this;
 }
 
 public function setHeaders(array $headers)
 {
   $this->headers = array_merge($this->headers, $headers);
   return $this;
 }
 
 public function setAuth($username, $password, $type = CURLAUTH_BASIC)
 {
   $this->options[CURLOPT_HTTPAUTH] = $type;
   $this->options[CURLOPT_USERPWD] = "$username:$password";
   return $this;
 }
 
 public function setBearerToken($token)
 {
   $this->setHeader('Authorization', 'Bearer ' . $token);
   return $this;
 }
 
 public function getHttpCode()
 {
   return $this->http_code;
 }
 
 public function getResponseHeaders()
 {
   return $this->response_headers;
 }
 
 public function getResponseHeader($key)
 {
   return $this->response_headers[strtolower($key)] ?? null;
 }
 
 public function getError()
 {
   return $this->error;
 }
 
 public function isSuccess()
 {
   return $this->http_code >= 200 && $this->http_code < 300;
 }
 
 public function getResult()
 {
   return $this->client;
 }

}