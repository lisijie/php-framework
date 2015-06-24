<?php

/**
 * AMFSerializer manages the job of translating PHP objects into
 * the actionscript equivalent via amf.  The main method of the serializer
 * is the serialize method which takes and AMFObject as it's argument
 * and builds the resulting amf body.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright (c) 2003 amfphp.org
 * @package flashservices
 * @subpackage io
 * @version $Id: AMFBaseSerializer.php 1 2014-04-30 05:53:30Z lisijie $
 */
class AMFBaseSerializer
{

    /**
     * Classes that are serialized as recordsets
     */
    protected $amf0StoredObjects = array();
    protected $storedObjects = array();
    protected $storedDefinitions = 0;
    protected $storedStrings = array();
    public $outBuffer;
    protected $encounteredStrings = 0;

    /**
     * AMFSerializer is the constructor function.  You must pass the
     * method an AMFOutputStream as the single argument.
     *
     * @param object $stream The AMFOutputStream
     */
    function AMFBaseSerializer()
    {
        $this->isBigEndian = AMFPHP_BIG_ENDIAN;
        $this->outBuffer = ""; // the buffer
        $this->charsetHandler = new CharsetHandler('phptoflash');
        $this->rsCharsetHandler = new CharsetHandler('sqltoflash');
        $this->encodeFlags = (AMFPHP_BIG_ENDIAN ? 2 : 0) |
            (defined('CLIENT_AMF_ENCODING') && CLIENT_AMF_ENCODING == 'amf3' ? 1 : 0);
    }


    /**
     * serialize is the run method of the class.  When serialize is called
     * the AMFObject passed in is read and converted into the amf binary
     * representing the PHP data represented.
     *
     * @param object $d the AMFObject to serialize
     */
    function serialize(&$amfout)
    {
        $this->writeInt(0); //  write the version ???
        $count = $amfout->numOutgoingHeader();
        $this->writeInt($count); // write header count
        for ($i = 0; $i < $count; $i++) {
            //write headers
            $header = & $amfout->getOutgoingHeaderAt($i);
            $this->writeUTF($header->name);
            $this->writeByte(0);
            $tempBuf = $this->outBuffer;
            $this->outBuffer = "";
            $this->writeData($header->value);
            $tempBuf2 = $this->outBuffer;
            $this->outBuffer = $tempBuf;
            $this->writeLong(strlen($tempBuf2));
            $this->outBuffer .= $tempBuf2;
        }

        $count = $amfout->numBody();
        $this->writeInt($count); // write the body  count
        for ($i = 0; $i < $count; $i++) {
            //write body
            $this->amf0StoredObjects = array();
            $this->storedStrings = array();
            $this->storedObjects = array();
            $this->encounteredStrings = 0;
            $this->storedDefinitions = 0;
            $body = & $amfout->getBodyAt($i);
            $this->currentBody = & $body;
            $this->writeUTF($body->responseURI); // write the responseURI header
            $this->writeUTF($body->responseTarget); //  write null, haven't found another use for this
            $tempBuf = $this->outBuffer;
            $this->outBuffer = "";
            $this->writeData($body->getResults());
            $tempBuf2 = $this->outBuffer;
            $this->outBuffer = $tempBuf;
            $this->writeLong(strlen($tempBuf2));
            $this->outBuffer .= $tempBuf2;
        }

        return $this->outBuffer;
    }

    function cleanXml($d)
    {
        return preg_replace('/\>(\n|\r|\r\n| |\t)*\</', '><', trim($d));
    }

    /**********************************************************************************
     *                      This code used to be in AMFOutputStream
     ********************************************************************************/

    /**
     * writeByte writes a singe byte to the output stream
     * 0-255 range
     *
     * @param int $b An int that can be converted to a byte
     */
    function writeByte($b)
    {
        $this->outBuffer .= pack("c", $b); // use pack with the c flag
    }

    /**
     * writeInt takes an int and writes it as 2 bytes to the output stream
     * 0-65535 range
     *
     * @param int $n An integer to convert to a 2 byte binary string
     */
    function writeInt($n)
    {
        $this->outBuffer .= pack("n", $n); // use pack with the n flag
    }

    /**
     * writeLong takes an int, float or double and converts it to a 4 byte binary string and
     * adds it to the output buffer
     *
     * @param long $l A long to convert to a 4 byte binary string
     */
    function writeLong($l)
    {
        $this->outBuffer .= pack("N", $l); // use pack with the N flag
    }

    /**
     * writeUTF takes and input string, writes the length as an int and then
     * appends the string to the output buffer
     *
     * @param string $s The string less than 65535 characters to add to the stream
     */
    function writeUTF($s)
    {
        $os = $this->charsetHandler->transliterate($s);
        $this->writeInt(strlen($os)); // write the string length - max 65535
        $this->outBuffer .= $os; // write the string chars
    }

    /**
     * writeBinary takes and input string, writes the length as an int and then
     * appends the string to the output buffer
     *
     * @param string $s The string less than 65535 characters to add to the stream
     */
    function writeBinary($s)
    {
        $this->outBuffer .= $s; // write the string chars
    }

    /**
     * writeLongUTF will write a string longer than 65535 characters.
     * It works exactly as writeUTF does except uses a long for the length
     * flag.
     *
     * @param string $s A string to add to the byte stream
     */
    function writeLongUTF($s)
    {
        $os = $this->charsetHandler->transliterate($s);
        $this->writeLong(strlen($os));
        $this->outBuffer .= $os; // write the string chars
    }

    /**
     * writeDouble takes a float as the input and writes it to the output stream.
     * Then if the system is big-endian, it reverses the bytes order because all
     * doubles passed via remoting are passed little-endian.
     *
     * @param double $d The double to add to the output buffer
     */
    function writeDouble($d)
    {
        $b = pack("d", $d); // pack the bytes
        if ($this->isBigEndian) { // if we are a big-endian processor
            $r = strrev($b);
        } else { // add the bytes to the output
            $r = $b;
        }

        $this->outBuffer .= $r;
    }

    function sanitizeType($type)
    {
        $subtype = -1;
        $type = strtolower($type);
        if ($type == NULL || trim($type) == "") {
            $type = -1;
        }

        if (strpos($type, ' ') !== false) {
            $str = explode(' ', $type);
            if (in_array($str[1], array("result", 'resultset', "recordset", "statement"))) {
                $type = "__RECORDSET__";
                $subtype = $str[0];
            }
        }
        return array($type, $subtype);
    }

    function getClassName(&$d)
    {
        $classname = get_class($d);
        if (strtolower($classname) == 'stdclass' && !isset($d->_explicitType)) {
            return "";
        }

        if (isset($d->_explicitType)) {
            $type = $d->_explicitType;
            unset($d->_explicitType);
            return $type;
        }

        if (isset($GLOBALS['amfphp']['outgoingClassMappings'][strtolower($classname)])) {
            return $GLOBALS['amfphp']['outgoingClassMappings'][strtolower($classname)];
        }

        if (class_exists("ReflectionClass")) //Another way of doing things, by Renaun Erickson
        {
            $reflectionClass = new ReflectionClass($classname);
            $fileName = $reflectionClass->getFileName();

            $basePath = $GLOBALS['amfphp']['customMappingsPath'];
            if ($basePath == "")
                $basePath = getcwd();

            // Handle OS filesystem differences
            if (DIRECTORY_SEPARATOR == "\\" && (strpos($basePath, DIRECTORY_SEPARATOR) === false))
                $basePath = str_replace("/", DIRECTORY_SEPARATOR, $basePath);

            if (strpos($fileName, $basePath) === FALSE) {
                return $classname;
            }
            $fullClassName = substr($fileName, strpos($fileName, $basePath));
            $fullClassName = substr($fullClassName, strlen($basePath));
            $fullClassName = substr($fullClassName, 0, strlen($fullClassName) - 4);
            $fullClassName = str_replace(DIRECTORY_SEPARATOR, '.', $fullClassName);

            return $fullClassName;
        }
        return $classname;
    }
}
