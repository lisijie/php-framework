<?php
define("RPC_BASE64_ENCODE", 2);
define("RPC_XXTEA_ENCODE", 4);
define("MAX_STORED_OBJECTS", 1024);
define("DISABLED_DESCRIBE_SERVICE", false);
define("AMFPHP_PHP5", true);
define("AMFPHP_BIG_ENDIAN", pack("d", 1) == "\0\0\0\0\0\0\360\77");
define('CLIENT_AMF_ENCODING', 'amf3');

class Headers
{
    public static function setHeader($key = NULL, $val = NULL)
    {
        static $headers = array();
        if ($val !== NULL) {
            $headers[$key] = $val;
        }
        return $headers[$key];
    }

    public static function getHeader($key)
    {
        return Headers::setHeader($key);
    }
}

class MessageHeader
{
    public $name;
    public $required;
    public $value;

    function MessageHeader($name = "", $required = false, $value = null)
    {
        $this->name = $name;
        $this->required = $required;
        $this->value = $value;
    }
}

/**
 * AMFBody is a data type that encapsulates all of the various properties a body object can have.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) 2003 amfphp.org
 * @package flashservices
 * @subpackage util
 * @version $Id: Message.php 1 2014-04-30 05:53:30Z lisijie $
 */
class MessageBody
{

    var $targetURI = "";
    var $responseURI = "";
    var $uriClassPath = "";
    var $classPath = "";
    var $className = "";
    var $methodName = "";
    var $responseTarget = "null";
    var $noExec = false;

    var $_value = NULL;
    var $_results = NULL;
    var $_classConstruct = NULL;
    var $_specialHandling = NULL;
    var $_metaData = array();

    /**
     * AMFBody is the Contstructor method for the class
     */
    function MessageBody($targetURI = "", $responseIndex = "", $value = "")
    {
        $GLOBALS['amfphp']['lastMethodCall'] = $responseIndex;
        $this->responseIndex = $responseIndex;
        $this->targetURI = $targetURI;
        $this->responseURI = $this->responseIndex . "/onStatus"; // default to the onstatus method
        $this->setValue($value);
    }

    /**
     * setter for the results from the process execution
     *
     * @param mixed $results The returned results from the process execution
     */
    function setResults($result)
    {
        $this->_results = $result;
    }

    /**
     * getter for the result of the process execution
     *
     * @return mixed The results
     */
    function &getResults()
    {
        return $this->_results;
    }

    /**
     * setter for the class construct
     *
     * @param object $classConstruct The instance of the service class
     */
    function setClassConstruct(&$classConstruct)
    {
        $this->_classConstruct = & $classConstruct;
    }

    /**
     * getter for the class construct
     *
     * @return object The class instance
     */
    function &getClassConstruct()
    {
        return $this->_classConstruct;
    }

    /**
     * setter for the value property
     *
     * @param mixed $value The value of the body object
     */
    function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * getter for the value property
     *
     * @return mixed The value property
     */
    function &getValue()
    {
        return $this->_value;
    }

    /**
     * Set special handling type for this body
     */
    function setSpecialHandling($type)
    {
        $this->_specialHandling = $type;
    }

    /**
     * Get special handling type for this body
     */
    function getSpecialHandling()
    {
        return $this->_specialHandling;
    }

    /**
     * Check if this body is handled special against an array of special cases
     */
    function isSpecialHandling($against = NULL)
    {
        if ($against !== NULL) {
            return in_array($this->_specialHandling, $against);
        } else {
            return ($this->_specialHandling != NULL);
        }
    }

    function setMetaData($key, $val)
    {
        $this->_metaData[$key] = $val;
    }

    function getMetaData($key)
    {
        if (isset($this->_metaData[$key])) {
            return $this->_metaData[$key];
        } else {
            return NULL;
        }
    }
} 
