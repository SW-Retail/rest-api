<?php
/**
 * This is a simple PHP rest-api client for connecting with the SW-Retail REST api. The only dependency there is is cURL which needs to be installed (which mostly always is).
 * We have kept it as flat and as simple as possible to get you running fast and keep integration effort low. Works in all frameworks, and since it communicates using array's it is easy to map information back / forward to your application.
 * V2.0, 22-03-2025
 * To integrate this api client in your software:
 * Include this file
 * Instantiate a SWRestAPI object
 * Provide your cloud instance name and apikey for the API
 * and you're good to go!
 *
 * Example:
 *      $api=new SWRestAPI('your-cloud-instance','apikey');
 *      $api->getVersion();
 * Make sure you setup an apikey with rest-api permissions in the SW-Retail configuration
 *
 * The api endpoints are implemented as 'magic' functions. You can call the api endpoints by prefixing what you want to do (get / post / put or delete) and then use the endpoint name (can be in capital etc..)
 * So:
 *      $api->getArticle(10);     // this calls the endpoint article/10 with a get request
 *      $api->getArticle(-1,1)    // gets page 1 of all articles  article/-1/1 with a get requset
 *      $api->getArticle_Stock(10,2,1)  // this calls  article_stock/10/2/1   (get the stock in warehouse 1, position 2 on the sizeruler, for article 10) with a get request
 *
 *      $test=['article_id'=>10,'article_memo'=>'something']
 *      $api->putArticle($test);       // this will set the field memo of article_id 10 to 'something
 *
 *      $api->deleteArticle(10);      // delete the article with id 10
 *
 * There are more examples in this file at the bottom.
 *
 * Also we have included an extra class with a little more elaborate examples called mySWRestAPI which you can use as your starting point
 *
 * You can find all documentation about the REST api endpoints in the self-support center.
 *
 * Happy Coding!
 *
 */


/**
 * Class Logger
 * Very simple logger. Prints to stdout at this point.
 */
class Logger
{
    static function log($what,$prefix='')
    {
        if (is_array($what)) {
            $what = print_r($what, true);
            if ($prefix!='')
                $what = str_replace('\n', '\n'.$prefix, $what);
        }
        print($prefix.$what . '\n');
    }

    static function logError($what)
    {
        Logger::log($what,'ERROR:');
    }

    static function logCritical($what)
    {
        Logger::log($what,'CRITICAL:');
    }
}

/**
 * Class BasicComms
 * Contains the basic functionality for communicating with the rest api, authentication and transport of data
 */
class BasicComms
{

    var $baseURL;
    var $apikey;    

    var $verbose = false;
    var $verifyPeer = true;
    var $lastError = false;
    var $lastHeaders;

    var $curl = false;

    var $errorCodes = [0 => 'No data found', 1 => 'Query error', 3 => 'No access to data', 4 => 'Unsupported', 5 => 'Parameter missing', 6 => 'Couldn\'t parse parameter', 7 => 'Parameter wrong type', 10 => 'Internal error', 11 => 'Data error', 12 => 'Not authorized', 13 => 'Excessive use'];

    /**
     * @desc make curl dump all information it has. this will dump a lot on your console / in your browsers
     * @param bool $active
     */
    public function setVerbose($active = true)
    {
        $this->verbose = $active;
    }

    /**
     * @desc switch of peer verification, disables the check if the certificate is valid. In case your hosting provider does not have the certificate of our SSL root
     * @param bool $active
     */
    public function setVerifyPeer($active = true)
    {
        $this->verifyPeer = $active;
    }

    /**
     * @desc setup the cloud url. Needs name of the instance
     * @param $instance
     */
    public function setCloud($instance, $apikey)
    {
        $this->baseURL = 'https://' . $instance . '.cloud.swretail.nl/swcloud/SWWService';
        $this->apikey = $apikey;        

        $this->curl = curl_init();
    }

    /**
     * @desc handle a rest-api error
     * @param $error_data array
     */
    protected function handleError($error_data)
    {
        // hmm something very bad happened
        if (!is_array($error_data)) {
            Logger::logError($error_data);
            return false;
        }

        $default = ['errorcode' => -1, 'errorstring' => 'unknownerror'];
        $error_data = array_merge($default, $error_data);

        // an unknown error code was returned
        if (!isset($this->errorCodes[$error_data['errorcode']])) {
            $this->lastError = $error_data;
            Logger::logError($error_data);
            return false;
        }

        // map the errornumber to text
        $error_data['moreinfo'] = $this->errorCodes[$error_data['errorcode']];
        $this->lastError = $error_data;

        Logger::logError($error_data);

        return false;
    }

    /**
     * @desc Basic setup for cURL. Make sure it is installed
     * @return bool
     */
    public function setupCurl()
    {
        if (!function_exists('curl_version'))
        {
            Logger::logCritical('cURL is not installed for you PHP installation. Make sure this is done!');
            return false;
        }


        curl_reset($this->curl);
        curl_setopt($this->curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        
        $headers = [
            'X-Auth: ' . $this->apikey,
            'Content-Type: application/json' // Add other headers if needed
        ];
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        if ($this->verbose)
            curl_setopt($this->curl, CURLOPT_VERBOSE, 1);
        else
            curl_setopt($this->curl, CURLOPT_VERBOSE, 0);

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
        // your php stack needs to know the SW-Retail CA (an updated system should). If it doesn't, install the latest set of certificates on your system. If you can't set this parameter to 0 (this does impose a security risk, man in the middle attack)
        if ($this->verifyPeer)
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 1);
        else
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 1);

        // some timeouts
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);

        return true;
    }

    /**
     * @desc get something from the endpoint
     * @param $endpoint
     * @return a decoded array with the response, or...., false in case of an error
     */
    public function doGet($endpoint)
    {
        $this->lastError = false;

        if (!$this->setupCurl())
            return [];

        $result=curl_setopt($this->curl, CURLOPT_URL, $this->baseURL . '/' . $endpoint);
        $response = curl_exec($this->curl);
        if (curl_errno($this->curl))
        {
            $error_msg=curl_error($this->curl);
            $this->handleError($error_msg);
            return [];
        }

        $responsecode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->lastHeaders = curl_getinfo($this->curl);

        $response_arr = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $this->handleError($response);
            return $response_arr;
        }

        if (isset($response_arr['errorcode'])) {
            $this->lastError=$response_arr;
            if ($response_arr['errorcode'] !=0) {
                $this->handleError($response_arr);
                return $response_arr;
            }
        }

        return $response_arr;
    }

    /**
     * @desc get something from the endpoint
     * @param $endpoint
     * @return a decoded array with the response, or...., false in case of an error
     */
    public function doDelete($endpoint)
    {
        $this->lastError = false;

        if (!$this->setupCurl())
            return [];

        $result=curl_setopt($this->curl, CURLOPT_URL, $this->baseURL . '/' . $endpoint);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($this->curl);
        if (curl_errno($this->curl))
        {
            $error_msg=curl_error($this->curl);
            $this->handleError($error_msg);
            return [];
        }

        $responsecode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->lastHeaders = curl_getinfo($this->curl);

        $response_arr = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $this->handleError($response);
            return $response_arr;
        }

        if (isset($response_arr['errorcode'])) {
            $this->lastError=$response_arr;
            if ($response_arr['errorcode'] !=0) {
                $this->handleError($response_arr);
                return $response_arr;
            }
        }

        return $response_arr;
    }

    /**
     * @desc Post something to the endpoint
     * @param $endpoint
     * @param $data
     * @return a decoded array with the response, or...., false in case of an error
     */
    public function doPostPut($post,$endpoint,$data)
    {
        $this->lastError = false;

        if (!$this->setupCurl())
            return [];

        $result=curl_setopt($this->curl, CURLOPT_URL, $this->baseURL . '/' . $endpoint);

        if ($post)
            curl_setopt($this->curl,CURLOPT_POST,true);
        else
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($this->curl,CURLOPT_POSTFIELDS,json_encode($data));

        $response = curl_exec($this->curl);
        if (curl_errno($this->curl))
        {
            $error_msg=curl_error($this->curl);
            $this->handleError($error_msg);
            return [];
        }

        $responsecode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->lastHeaders = curl_getinfo($this->curl);

        $response_arr = json_decode($response, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            $this->handleError($response);
            return $response_arr;
        }

        if (isset($response_arr['errorcode'])) {
            $this->lastError=$response_arr;
            if ($response_arr['errorcode'] !=0) {
                $this->handleError($response_arr);
                return $response_arr;
            }
        }

        return $response_arr;
    }
}

/**
 * Class SWRestApi
 * Implements the SW-Retail REST api.
 * Uses magic functions to call the endpoints
 */
class SWRestAPI {

    var $basicComms=null;

    /**
     * SWRestApi constructor.
     * @param $instance
     * @param $apikey     
     */
    function __construct ($instance,$apikey)
    {
        $this->BC=new BasicComms();
        $this->BC->setCloud($instance,$apikey);
    }

    /**
     * @desc when connections fail or whatever, call this function to see the certificate and verify your hosting knowns our signing party
     */
    function unsafeMode()
    {
        $this->BC->setVerifyPeer(false);
    }

    /**
     * @desc execute a get request
     * @param $method
     * @param $arguments
     * @return a
     */
    function doGet($method,$arguments)
    {
        $args=implode('/',$arguments);
        $method= strtolower(substr($method,3,strlen($method)));
        $method=$method.'/'.$args;
        return $this->BC->doGet($method);
    }

    /**
     * @desc execute a post request
     * @param $method
     * @param $arguments
     * @return a
     */
    function doPost($method,$arguments)
    {
        $method= strtolower(substr($method,4,strlen($method)));
        return $this->BC->doPostPut(true,$method,$arguments[0]);
    }

    /**
     * @desc execute a put request
     * @param $method
     * @param $arguments
     * @return a
     */
    function doPut($method,$arguments)
    {
        $method= strtolower(substr($method,3,strlen($method)));
        return $this->BC->doPostPut(false,$method,$arguments[0]);
    }

    /**
     * @desc execute a delete request
     * @param $method
     * @param $arguments
     */
    function doDelete($method,$arguments)
    {
        $method= strtolower(substr($method,6,strlen($method)));
        $args=implode('/',$arguments);
        $method=$method.'/'.$args;
        return $this->BC->doDelete($method);
    }

    /**
     * @desc Generic function call override for some magic functions
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method,$arguments) {
        if(method_exists($this, $method)) {
            return call_user_func_array(array($this,$method),$arguments);
        }
        if (substr($method,0,3)=='get')
            return $this->doGet($method,$arguments);
        if (substr($method,0,4)=='post')
            return $this->doPost($method,$arguments);
        if (substr($method,0,3)=='put')
            return $this->doPut($method,$arguments);
        if (substr($method,0,6)=='delete')
            return $this->doDelete($method,$arguments);

        Logger::logCritical('You must call the endpoints prefixed with put, get, post or delete');
    }

}

/**
 * Class mySWRestAPI
 * You can make this your own class and do your thing. There are some examples here for a little more complex use scenarios
 */
class mySWRestAPI  extends SWRestAPI {

    /**
     * @desc example for uploading an image file
     * @param $article_id
     * @param $imagefile
     */
    function uploadArticleImage($article_id,$imagefile,$description="")
    {
        $img=file_get_contents($imagefile);
        $img=base64_encode($img);
        $data=["image"=>$img,"image_description"=>$description,"article_id"=>$article_id];
        $this->postArticle_image($data);
    }

    /**
     * @desc returns null for the article_id when barcode not found
     * @param $barcode
     * @return array
     */
    function barcodeLookup($barcode)
    {
        $article=$this->getBarcode_Lookup($barcode);
        $def=["article_id"=>null,"position"=>null];
        return array_merge($def,$article);
    }

    /**
     * @desc get the article_id from a barcode
     * @param $barcode
     * @return mixed
     */
    function articleIDfromBarcode($barcode)
    {
        $article=$this->barcodeLookup($barcode);
        return $article["article_id"];
    }

}
