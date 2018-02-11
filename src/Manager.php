<?php
/**
 * Input Manager Class
 *
 * @author Eric
 * @package SlabPHP
 * @subpackage Input
 */
namespace SlabPHP\Input;

class Manager implements \SlabPHP\Components\InputManagerInterface
{
    /**
     * Get variables
     *
     * @var string[]
     */
    private $getParams;

    /**
     * Post params
     *
     * @var string[]
     */
    private $postParams;

    /**
     * Server params
     *
     * @var string[]
     */
    private $serverParams;

    /**
     * File params
     *
     * @var string[]
     */
    private $fileParams;

    /**
     * Cookie params
     *
     * @var string[]
     */
    private $cookieParams;

    /**
     * Environment variables
     *
     * @var string[]
     */
    private $environmentParams;

    /**
     * Should we sanitize input aggressively or not
     *
     * @var boolean
     */
    private $shouldSanitizeInput = true;


    /**
     * Public constructor
     * @param bool $sanitizeInput
     * @param bool $clearSuperGlobals
     */
    public function __construct($sanitizeInput = true, $clearSuperGlobals = false)
    {
        $this->shouldSanitizeInput = $sanitizeInput;
        $shouldClearSuperGlobals = $clearSuperGlobals;

        $this->initializeAndCleanSuperGlobal($_GET, $this->getParams);
        if ($shouldClearSuperGlobals) unset($_GET);

        $this->supplementGetArray();

        $this->initializeAndCleanSuperGlobal($_POST, $this->postParams);
        if ($shouldClearSuperGlobals) unset($_POST);

        $this->initializeAndCleanSuperGlobal($_SERVER, $this->serverParams);
        if ($shouldClearSuperGlobals) unset($_SERVER);

        $this->initializeAndCleanSuperGlobal($_COOKIE, $this->cookieParams);
        if ($shouldClearSuperGlobals) unset($_COOKIE);

        $this->initializeAndCleanSuperGlobal($_FILES, $this->fileParams);
        if ($shouldClearSuperGlobals) unset($_FILES);

        $this->initializeAndCleanSuperGlobal($_ENV, $this->environmentParams);
        if ($shouldClearSuperGlobals) unset($_ENV);

        if ($shouldClearSuperGlobals) unset($_REQUEST);
        if ($shouldClearSuperGlobals) unset($GLOBALS);
    }

    /**
     * Some servers will not populate the remaining get params because of the mod rewrite redirect
     *
     * This will solve it.
     */
    private function supplementGetArray()
    {
        if (empty($_SERVER["REQUEST_URI"]))
        {
            return;
        }

        $queryStringBegins = strpos($_SERVER["REQUEST_URI"], '?');

        if (empty($queryStringBegins))
        {
            return;
        }

        $queryString = substr($_SERVER["REQUEST_URI"], $queryStringBegins + 1);

        $getArray = array();
        parse_str($queryString, $getArray);

        foreach ($getArray as $variable => $value) {
            $this->getParams[$variable] = $this->cleanVariable($value);
        }
    }

    /**
     * Initialize local storage of a super global and kill the super global version
     *
     * @param array $superGlobal
     * @param string $localStorage
     */
    private function initializeAndCleanSuperGlobal(&$superGlobal, &$localStorage)
    {
        $localStorage = [];
        if (!empty($superGlobal)) {
            foreach ($superGlobal as $variableName => $variableValue) {
                $localStorage[$variableName] = $this->cleanVariable($variableValue);
            }
        }
    }

    /**
     * Cleans a variable from input
     *
     * @param string $input
     * @return string
     */
    private function cleanVariable($input)
    {
        if (is_string($input)) {
            if ($this->shouldSanitizeInput) {
                return trim(strip_tags($input));
            } else {
                return trim($input);
            }
        } else {
            return $input;
        }
    }

    /**
     * Check get, post, and cookie for return
     *
     * @param string $variable
     * @return mixed
     */
    public function request($variable)
    {
        $order = ini_get('request_order');

        if (empty($order)) {
            $order = ini_get('variables_order');
        }

        $len = strlen($order);

        for ($i = 0; $i < $len; ++$i) {
            $character = strtoupper($order[$i]);

            if ($character == 'G') {
                if ($this->get($variable)) return $this->get($variable);
            } else if ($character == 'P') {
                if ($this->post($variable)) return $this->post($variable);
            } else if ($character == 'C') {
                if ($this->cookie($variable)) return $this->cookie($variable);
            }
        }

        return null;
    }

    /**
     * Return a GET parameter, enter null for the entire array
     *
     * @param string $variable
     * @param string $default
     * @param mixed $validator
     * @return string
     */
    public function get($variable = null, $default = '', $validator = null)
    {
        $value = $this->returnLocalParam('getParams', $variable, $default);

        return $value;
    }


    /**
     * Return a POSt parameter, enter null for the entire array
     *
     * @param string $variable
     * @param string $default
     * @param mixed $validator
     * @return string
     */
    public function post($variable = null, $default = '', $validator = null)
    {
        $value = $this->returnLocalParam('postParams', $variable, $default);

        return $value;
    }

    /**
     * Post is set
     *
     * @param $variable
     * @return bool
     */
    public function postIsSet($variable)
    {
        return isset($this->postParams[$variable]);
    }

    /**
     * Return a SERVER parameter, enter null for the entire array
     *
     * @param string $variable
     * @return string
     */
    public function server($variable = null)
    {
        return $this->returnLocalParam('serverParams', $variable);
    }

    /**
     * Not implemented yet due to laziness
     *
     * @param string $fileName
     * @return \stdClass
     */
    public function file($fileName = null)
    {
        return $this->returnLocalParam('fileParams', $fileName);
    }

    /**
     * Return an environment variable
     *
     * @param string $variable
     * @return string
     */
    public function env($variable = null)
    {
        return $this->returnLocalParam('environmentParams', $variable);
    }

    /**
     * Return a cookie variable
     *
     * @param string $variable
     * @return string
     */
    public function cookie($variable = null)
    {
        return $this->returnLocalParam('cookieParams', $variable);
    }

    /**
     * Set the current page's instance of a cookie value. Does not actually set a cookie
     *
     * @param string $variable
     * @param mixed $value
     */
    public function setCookie($variable, $value)
    {
        if (!empty($value)) {
            $this->cookieParams[$variable] = $value;
            return;
        }

        unset($this->cookieParams[$variable]);
    }

    /**
     * Return a stored local variable
     *
     * @param string $localStorage
     * @param string $variable
     * @param bool $default
     */
    private function returnLocalParam($localStorage, $variable, $default = false)
    {
        if (!empty($variable)) {
            if (isset($this->{$localStorage}[$variable])) {
                return $this->{$localStorage}[$variable];
            } else {
                return $default;
            }
        } else {
            return $this->{$localStorage};
        }
    }

    /**
     * Set a stored local variable to something else
     *
     * @param string $localStorage
     * @param string $variable
     * @param mixed $value
     */
    private function setLocalParam($localStorage, $variable, $value)
    {
        $this->{$localStorage}[$variable] = $value;
    }
}
