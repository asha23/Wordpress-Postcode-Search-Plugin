<?php

$main_api = get_option('map_option_1');

class ipsWrapper { 

    //	Your ipstack API key 
	//	Available at https://ipstack.com/product 
	// 	This has only 10,000 requests per month. (Should be enough)

	// Get the Key from the options page

	public $api_key;

	public function __construct() {
		$this->api_key = get_option('map_option_1');
	}

    //API endpoints 
    private $endPoint = array( 
        'api' => 'api.ipstack.com/', 
        'check' => 'api.ipstack.com/check' 
    ); 

    //use secure socket layer 
    public $useSSL = false; 

    //current endpoint to use 
    public $useEndPoint = 'api'; 

    //API key/value pair params 
    public $params = array(); 

    //holds the error code, if any 
    public $errorCode; 

    //holds the error text, if any 
    public $errorText; 

    //response object 
    public $response; 

    //JSON response from API 
	public $responseAPI; 
	
	//public $main_api_key = $main_api;

    /* 
    method: getResponse 
    usage: getResponse(void); 
    params: none 

    This method will build the reqeust and get the response from the API 

    returns: null 
    */ 
    public function getResponse(){ 

        $request = $this->buildRequest(); 

        $this->responseAPI = file_get_contents($request); 

        $this->response = json_decode($this->responseAPI); 

        if( !empty($this->response->error->code) ){ 

            $this->errorCode = $this->response->error->code; 
            $this->errorText = $this->response->error->info; 

        } 

    } 

    /* 
    method: getResponseCurl 
    usage: getResponseCurl([string useEndPoint='']); 
    params: useEndPoint = end point to use for this request 

    This method will get an API response using curl 

    returns: null 
    */ 
    public function getResponseCurl($useEndPoint=''){ 

        $request = $this->buildRequest($useEndPoint); 

        $curl = curl_init($request); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
        $this->responseAPI = curl_exec($curl); 
        curl_close($curl); 

        $this->response = json_decode($this->responseAPI); 

        if( !empty($this->response->error->code) ){ 

            $this->errorCode = $this->response->error->code; 
            $this->errorText = $this->response->error->info; 

        } 

    } 

    /* 
    method: buildRequest 
    usage: buildRequest([string useEndPoint='']) 
    params: useEndPoint = end point to use for this request 

    This method will build the api request url. 

    returns: api request url 
    */ 
    public function buildRequest($useEndPoint=''){ 

		$this->apiKey = $this->api_key;
        $prot = ( $this->useSSL ) ? 'https://' : 'http://'; 

        $useEndPoint = ( empty($useEndPoint) ) ? $this->useEndPoint : $useEndPoint; 

        $request = ( $useEndPoint == 'check' ) ? $prot.$this->endPoint['check'].'?access_key='.$this->apiKey : $prot.$this->endPoint[$useEndPoint].$this->ipnum.'?access_key='.$this->apiKey; 
		// var_dump($request);

        foreach( $this->params as $key=>$value ){ 

            $request .= '&'.$key.'='.urlencode($value); 

        } 

        return $request; 

    } 

    /* 
    method: setParam 
    usage: setParam(string key, string value); 
    params: key = key of the params key/value pair 
             value = value of the params key/value pair 

    add or change the params key/value pair specified. 

    returns: null 
    */ 
    public function setParam($key,$value){ 

        $this->params[$key] = $value; 

    } 

    /* 
    method: resetParam 
    usage: resetParam(void); 
    params: none 

    resets all stored parameters. 

    returns: null 
    */ 
    public function resetParams(){ 

        $this->params = array(); 

    } 

    /* 
    method: setEndPoint 
    usage: setEndPoint(string useEndPoint); 
    params: useEndPoint = end point to use for request 

    Sets the end point to use for request. 

    returns: null 
    */ 
    public function setEndPoint($useEndPoint){ 

        if( array_key_exists($useEndPoint,$this->endPoint) ){ 

            $this->useEndPoint = $useEndPoint; 

        } else { 

            throw new Exception($useEndPoint.' is not a valid end point'); 
        } 
    } 
} 