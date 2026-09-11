<?php

/**
 * Twitter-API-PHP : Simple PHP wrapper for the v1.1 API
 * 
 * PHP version 5.3+ / 7.x / 8.x compatible
 * 
 * @category Awesomeness
 * @package  Twitter-API-PHP
 * @author   James Mallison <me@j7mbo.co.uk>
 * @license  MIT License
 * @link     http://github.com/j7mbo/twitter-api-php
 */
#[\AllowDynamicProperties]
class TwitterAPIExchange
{
    private $oauth_access_token;
    private $oauth_access_token_secret;
    private $consumer_key;
    private $consumer_secret;
    private $postfields;
    private $getfield;
    protected $oauth;
    public $url;

    /**
     * Create the API access object. Requires an array of settings:
     * oauth access token, oauth access token secret, consumer key, consumer secret
     * 
     * @param array $settings
     * @throws Exception
     */
    public function __construct(array $settings)
    {
        if (!extension_loaded('curl')) 
        {
            throw new Exception('You need to install cURL, see: https://curl.se/docs/install.html');
        }
        
        if (!isset($settings['oauth_access_token'])
            || !isset($settings['oauth_access_token_secret'])
            || !isset($settings['consumer_key'])
            || !isset($settings['consumer_secret']))
        {
            throw new Exception('Make sure you are passing in the correct parameters');
        }

        $this->oauth_access_token        = (string) $settings['oauth_access_token'];
        $this->oauth_access_token_secret = (string) $settings['oauth_access_token_secret'];
        $this->consumer_key              = (string) $settings['consumer_key'];
        $this->consumer_secret           = (string) $settings['consumer_secret'];
    }
    
    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     * 
     * @param array $array Array of parameters to send to API
     * @return TwitterAPIExchange
     * @throws Exception
     */
    public function setPostfields(array $array)
    {
        if (!is_null($this->getGetfield())) 
        { 
            throw new Exception('You can only choose get OR post fields.'); 
        }
        
        if (isset($array['status']) && is_string($array['status']) && str_starts_with($array['status'], '@'))
        {
            $array['status'] = sprintf("\0%s", $array['status']);
        }
        
        $this->postfields = $array;
        
        return $this;
    }
    
    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     * 
     * @param string $string Get key and value pairs as string
     * @return TwitterAPIExchange
     * @throws Exception
     */
    public function setGetfield($string)
    {
        if (!is_null($this->getPostfields())) 
        { 
            throw new Exception('You can only choose get OR post fields.'); 
        }
        
        $search  = array('#', ',', '+', ':');
        $replace = array('%23', '%2C', '%2B', '%3A');
        $string  = str_replace($search, $replace, (string) $string);  
        
        $this->getfield = $string;
        
        return $this;
    }
    
    /**
     * Get getfield string
     * 
     * @return string|null
     */
    public function getGetfield()
    {
        return $this->getfield;
    }
    
    /**
     * Get postfields array
     * 
     * @return array|null
     */
    public function getPostfields()
    {
        return $this->postfields;
    }
    
    /**
     * Build the Oauth object using params set in construct and additionals
     * 
     * @param string $url The API url to use.
     * @param string $requestMethod Either POST or GET
     * @return TwitterAPIExchange
     * @throws Exception
     */
    public function buildOauth($url, $requestMethod)
    {
        $method = strtoupper(trim((string) $requestMethod));
        if (!in_array($method, array('POST', 'GET'), true))
        {
            throw new Exception('Request method must be either POST or GET');
        }
        
        $consumer_key              = $this->consumer_key;
        $consumer_secret           = $this->consumer_secret;
        $oauth_access_token        = $this->oauth_access_token;
        $oauth_access_token_secret = $this->oauth_access_token_secret;
        
        $oauth = array( 
            'oauth_consumer_key'     => $consumer_key,
            'oauth_nonce'            => (string) time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token'            => $oauth_access_token,
            'oauth_timestamp'        => (string) time(),
            'oauth_version'          => '1.0',
        );
        
        $getfield = $this->getGetfield();
        
        if (!is_null($getfield))
        {
            $cleaned_get = ltrim($getfield, '?');
            $getfields   = array_filter(explode('&', $cleaned_get));

            foreach ($getfields as $g)
            {
                $split = explode('=', $g, 2);
                if (isset($split[0]) && '' !== $split[0])
                {
                    $oauth[$split[0]] = isset($split[1]) ? $split[1] : '';
                }
            }
        }
        
        $base_info                = $this->buildBaseString($url, $method, $oauth);
        $composite_key            = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature          = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;
        
        $this->url   = $url;
        $this->oauth = $oauth;
        
        return $this;
    }
    
    /**
     * Perform the actual data retrieval from the API
     * 
     * @param boolean $return If true, returns data.
     * @return string|false json data or false on error
     * @throws Exception
     */
    public function performRequest($return = true)
    {
        if (!is_bool($return)) 
        { 
            throw new Exception('performRequest parameter must be true or false'); 
        }
        
        $header     = array($this->buildAuthorizationHeader($this->oauth), 'Expect:');
        $getfield   = $this->getGetfield();
        $postfields = $this->getPostfields();

        $options = array( 
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_HEADER         => false,
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        );

        if (!is_null($postfields))
        {
            $options[CURLOPT_POSTFIELDS] = $postfields;
        }
        else
        {
            if (!empty($getfield))
            {
                $options[CURLOPT_URL] .= $getfield;
            }
        }

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);

        if (false === $json)
        {
            $curl_error = curl_error($feed);
            curl_close($feed);
            return json_encode(array('error' => $curl_error));
        }

        curl_close($feed);

        if ($return) { return $json; }
    }
    
    /**
     * Private method to generate the base string used by cURL
     * 
     * @param string $baseURI
     * @param string $method
     * @param array $params
     * @return string
     */
    private function buildBaseString($baseURI, $method, $params) 
    {
        $return = array();
        ksort($params);
        
        foreach ($params as $key => $value)
        {
            $return[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        
        return strtoupper($method) . '&' . rawurlencode((string) $baseURI) . '&' . rawurlencode(implode('&', $return)); 
    }
    
    /**
     * Private method to generate authorization header used by cURL
     * 
     * @param array $oauth Array of oauth data generated by buildOauth()
     * @return string Header used by cURL for request
     */    
    private function buildAuthorizationHeader($oauth) 
    {
        $return = 'Authorization: OAuth ';
        $values = array();
        
        foreach ((array) $oauth as $key => $value)
        {
            $values[] = rawurlencode((string) $key) . '="' . rawurlencode((string) $value) . '"';
        }
        
        $return .= implode(', ', $values);
        return $return;
    }
}
