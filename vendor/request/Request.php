<?php
/* cURL helper class
 * Author:  Johan P. <johan@asbra.net>
 * Created: 2011-02-19
 * Updated: 2011-07-27
 * Updated: 2011-09-10
 * Updated: 2011-10-04
 * Updated: 2012-07-21
 * Updated: 2013-01-15
 * Updated: 2013-02-04
 */

class Request
{
    private $useragent; // user agent string
    private $handle;    // handle to the cURL object
    private $cookies;   // boolean value whether to use/store cookies or not
    private $redirs;    // boolean value whether to follow redirects or not
    private $xhr;        // boolean value whether to use XHR (XMLHttpRequest) or not

    public $cookiejar;  // filename of the cookie jar
    public $data;       // last data returned from a cURL transfer
    public $code;       // the last HTTP code returned
    public $url;        // URL of the page we are currently at
    public $info;       // information about the last cURL transfer

    private $proxy;     // proxy adress
    private $proxypwd;  // proxy password

    /* $redirs    Should we follow redirects or not
     * $cookies   Use cookies or not
     * $useragent The UserAgent string to send, try to keep this updated
     */
    function __construct($redirs = true, $cookies = true, $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.57 Safari/537.17')
    {
        $this->useragent = $useragent;
        $this->handle    = null;
        $this->cookies   = $cookies;
        $this->redirs    = $redirs;
        $this->cookiejar = 'cookies.txt';
        $this->proxy     = '';
        $this->proxypwd  = '';
        $this->xhr       = false;
    }

    /* Public so we can set a proxy before making a request
     * when switching proxy, remember to clear cookies by cleanup()
     */
    public function set_proxy($proxy, $auth = '')
    {
        $this->proxy    = $proxy;
        $this->proxypwd = $auth;
    }

    /* Wrapper for curl_setopt
     */
    private function setopt($url, $referer)
    {
        // Set request URL
        curl_setopt($this->handle, CURLOPT_URL, $url);

        // Remove any current HTTP headers
        curl_setopt($this->handle, CURLOPT_HEADER, 0);

        // Follow redirects, or not
        if ($this->redirs)
        {
            curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->handle, CURLOPT_MAXREDIRS, 10);
        }
        else
        {
            curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($this->handle, CURLOPT_MAXREDIRS, 0);
        }

        // Return data from transfer
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);

        // Set UserAgent string
        curl_setopt($this->handle, CURLOPT_USERAGENT, $this->useragent);

        // If it's a HTTPS (SSL) URL, disable verification
        if (substr($url, 4, 1) == 's')
        {
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set cookie jar
        if ($this->cookies)
        {
            curl_setopt($this->handle, CURLOPT_COOKIEJAR, $this->cookiejar);
            curl_setopt($this->handle, CURLOPT_COOKIEFILE, $this->cookiejar);
        }

        // If a proxy is set, use it
        if ($this->proxy != '')
        {
            curl_setopt($this->handle, CURLOPT_PROXY, $this->proxy);

            if ($this->proxypwd != '')
            {
                curl_setopt($this->handle, CURLOPT_PROXYUSERPWD, $this->proxypwd);
            }
        }

        // Set referrer if one is specified
        if ($referer != '')
        {
            curl_setopt($this->handle, CURLOPT_REFERER, $referer);
        }

        // XHR
        if ($this->xhr == true)
        {
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
        }
    }

    /* Make a POST request
     */
    function post($url, $data, $referer = '', $xhr = false)
    {
        $this->handle = curl_init();

        $fields_string = '';
        if (is_array($data))
        {
            foreach ($data as $key => $value)
            {
                $fields_string .= $key.'='.$value.'&';
            }
            rtrim($fields_string, '&');
        }
        else
        {
            $fields_string = $data;
        }

        $this->xhr = $xhr;
        $this->setopt($url, $referer);

        curl_setopt($this->handle, CURLOPT_POST, 1);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, $fields_string);

        $this->data = curl_exec($this->handle);
        $this->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
        $this->info = curl_getinfo($this->handle);
        $this->url  = (isset($this->info['redirect_url']) && !empty($this->info['redirect_url']) ? $this->info['redirect_url'] : $this->info['url']);
        curl_close($this->handle);

        return $this->data;
    }

    /* Make a GET request
     */
    function get($url, $referer = '', $xhr = false)
    {
        $this->handle = curl_init();

        $this->xhr = $xhr;
        $this->setopt($url, $referer);

        $this->data = curl_exec($this->handle);
        $this->code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
        $this->info = curl_getinfo($this->handle);
        $this->url  = (isset($this->info['redirect_url']) && !empty($this->info['redirect_url']) ? $this->info['redirect_url'] : $this->info['url']);
        curl_close($this->handle);

        return $this->data;
    }

    /* Removes stored cookies
     */
    public function cleanup()
    {
        if (file_exists($this->cookiejar))
        {
            unlink($this->cookiejar);
        }
    }

    /* Simple helper function to get data between two strings
     * $string  The string to search in
     * $str1    The start string to find
     * $str2    The end string to find
     * usage $request->between($request->data, '<h1>', '</h1>');
     * However if you are parsing HTML, it's better to use a DOM library.
     */
    function between($string, $str1, $str2)
    {
        $pos = strpos($string, $str1);
        if ($pos === false)
        {
            return '';
        }

        $end = strpos($string, $str2, $pos+strlen($str1));

        return substr($string, $pos+strlen($str1), ($end-$pos)-strlen($str1));
    }
}

?>