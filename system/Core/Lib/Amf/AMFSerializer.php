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
 * @version $Id: AMFSerializer.php 1 2014-04-30 05:53:30Z lisijie $
 */
class AMFSerializer extends AMFBaseSerializer
{

    /**
     * AMFSerializer is the constructor function.  You must pass the
     * method an AMFOutputStream as the single argument.
     *
     * @param object $stream The AMFOutputStream
     */
    function AMFSerializer()
    {
        AMFBaseSerializer::AMFBaseSerializer();
    }


    function writeBoolean($d)
    {
        $this->writeByte(1); // write the boolean flag
        $this->writeByte($d); // write  the boolean byte
    }

    function writeString($d)
    {
        $count = strlen($d);
        if ($count < 65536) {
            $this->writeByte(2);
            $this->writeUTF($d);
        } else {
            $this->writeByte(12);
            $this->writeLongUTF($d);
        }
    }

    /**
     * writeXML writes the xml code (0x0F) and the XML string to the output stream
     * Note: strips whitespace
     * @param string $d The XML string
     */
    function writeXML($d)
    {
        if (!$this->writeReferenceIfExists($d)) {
            $this->writeByte(15);
            $this->writeLongUTF(preg_replace('/\>(\n|\r|\r\n| |\t)*\</', '><', trim($d)));
        }
    }

    function writeDate($d)
    {
        $this->writeByte(11); // write  date code
        $this->writeDouble($d); //  write date (milliseconds from 1970)
        $this->writeInt(0);
    }

    /**
     * writeNumber writes the number code (0x00) and the numeric data to the output stream
     * All numbers passed through remoting are floats.
     *
     * @param int $d The numeric data
     */
    function writeNumber($d)
    {
        $this->writeByte(0); // write the number code
        $this->writeDouble(floatval($d)); // write  the number as a double
    }

    /**
     * writeNull writes the null code (0x05) to the output stream
     */
    function writeNull()
    {
        $this->writeByte(5); // null is only a  0x05 flag
    }

    /**
     * writeArray first deterines if the PHP array contains all numeric indexes
     * or a mix of keys.  Then it either writes the array code (0x0A) or the
     * object code (0x03) and then the associated data.
     *
     * @param array $d The php array
     */
    function writeArray($d)
    {
        if ($this->writeReferenceIfExists($d)) {
            return;
        }

        $numeric = array(); // holder to store the numeric keys
        $string = array(); // holder to store the string keys
        $len = count($d); // get the total number of entries for the array
        $largestKey = -1;
        foreach ($d as $key => $data) { // loop over each element
            if (is_int($key) && ($key >= 0)) { // make sure the keys are numeric
                $numeric[$key] = $data; // The key is an index in an array
                $largestKey = max($largestKey, $key);
            } else {
                $string[$key] = $data; // The key is a property of an object
            }
        }
        $num_count = count($numeric); // get the number of numeric keys
        $str_count = count($string); // get the number of string keys

        if (($num_count > 0 && $str_count > 0) ||
            ($num_count > 0 && $largestKey != $num_count - 1)
        ) { // this is a mixed array

            $this->writeByte(8); // write the mixed array code
            $this->writeLong($num_count); // write  the count of items in the array
            $this->writeObjectFromArray($numeric + $string); // write the numeric and string keys in the mixed array
        } else if ($num_count > 0) { // this is just an array

            $num_count = count($numeric); // get the new count

            $this->writeByte(10); // write  the array code
            $this->writeLong($num_count); // write  the count of items in the array
            for ($i = 0; $i < $num_count; $i++) { // write all of the array elements
                $this->writeData($numeric[$i]);
            }
        } else if ($str_count > 0) { // this is an object
            $this->writeByte(3); // this is an  object so write the object code
            $this->writeObjectFromArray($string); // write the object name/value pairs
        } else { //Patch submitted by Jason Justman

            $this->writeByte(10); // make this  an array still
            $this->writeInt(0); //  give it 0 elements
            $this->writeInt(0); //  give it an element pad, this looks like a bug in Flash,
            //but keeps the next alignment proper
        }
    }


    function writeReferenceIfExists($d)
    {
        if (count($this->amf0StoredObjects) >= MAX_STORED_OBJECTS) {
            return false;
        }
        if (is_array($d)) {
            $this->amf0StoredObjects[] = "";
            return false;
        }
        if (($key = array_search($d, $this->amf0StoredObjects, true)) !== FALSE) {
            $this->writeReference($key);
            return true;
        } else {
            $this->amf0StoredObjects[] = & $d;
            return false;
        }
    }

    function writeReference($num)
    {
        $this->writeByte(0x07);
        $this->writeInt($num);
    }

    /**
     * Write a plain numeric array without anything fancy
     */
    function writePlainArray($d)
    {
        if (!$this->writeReferenceIfExists($d)) {
            $num_count = count($d);
            $this->writeByte(10); // write  the mixed array code
            $this->writeLong($num_count); // write  the count of items in the array
            for ($i = 0; $i < $num_count; $i++) { // write all of the array elements
                $this->writeData($d[$i]);
            }
        }
    }

    /**
     * writeObject handles writing a php array with string or mixed keys.  It does
     * not write the object code as that is handled by the writeArray and this method
     * is shared with the CustomClass writer which doesn't use the object code.
     *
     * @param array $d The php array with string keys
     */
    function writeObjectFromArray($d)
    {
        foreach ($d as $key => $data) { // loop over each element
            $this->writeUTF($key); // write the name of the object
            $this->writeData($data); // write the value of the object
        }
        $this->writeInt(0); //  write the end object flag 0x00, 0x00, 0x09
        $this->writeByte(9);
    }

    /**
     * writeObject handles writing a php array with string or mixed keys.  It does
     * not write the object code as that is handled by the writeArray and this method
     * is shared with the CustomClass writer which doesn't use the object code.
     *
     * @param array $d The php array with string keys
     */
    function writeAnonymousObject($d)
    {
        if (!$this->writeReferenceIfExists($d)) {
            $this->writeByte(3);
            $objVars = (array)$d;
            foreach ($d as $key => $data) { // loop over each element
                if ($key[0] != "\0") {
                    $this->writeUTF($key); // write the name of the object
                    $this->writeData($data); // write the value of the object
                }
            }
            $this->writeInt(0); //  write the end object flag 0x00, 0x00, 0x09
            $this->writeByte(9);
        }
    }

    /**
     * writePHPObject takes an instance of a class and writes the variables defined
     * in it to the output stream.
     * To accomplish this we just blanket grab all of the object vars with get_object_vars
     *
     * @param object $d The object to serialize the properties
     */
    function writeTypedObject($d)
    {
        if ($this->writeReferenceIfExists($d)) {
            return;
        }

        $this->writeByte(16); // write  the custom class code
        $classname = $this->getClassName($d);

        $this->writeUTF($classname); // write the class name
        if (AMFPHP_PHP5) {
            $objVars = $d;
        } else {
            $objVars = (array)$d;
        }
        foreach ($objVars as $key => $data) { // loop over each element
            if ($key[0] != "\0") {
                $this->writeUTF($key); // write the name of the object
                $this->writeData($data); // write the value of the object
            }
        }
        $this->writeInt(0); //  write the end object flag 0x00, 0x00, 0x09
        $this->writeByte(9);
    }

    /**
     * writeRecordSet is the abstracted method to write a custom class recordset object.
     * Any recordset from any datasource can be written here, it just needs to be properly formatted
     * beforehand.
     *
     * This was unrolled with at the expense of readability for a
     * 10 fold increase in speed in large recordsets
     *
     * @param object $rs The formatted RecordSet object
     */

    function writeRecordSet(&$rs)
    {
        //Low-level everything here to make things faster
        //This is the bottleneck of AMFPHP, hence the attention in making things faster
        if ($this->writeReferenceIfExists($rs)) {
            return;
        }

        $ob = "";
        $data = $rs->rows;

        if (!defined('CLIENT_AMF_ENCODING') || CLIENT_AMF_ENCODING == 'amf0') {

            $this->writeByte(16); // write  the custom class code
            $this->writeUTF("RecordSet"); // write  the class name
            $this->writeUTF("serverInfo");

            //Start writing inner object
            $this->writeByte(3); // this is an  object so write the object code

            //Write total count
            $this->writeUTF("totalCount");
            $this->writeNumber($rs->getRowCount());

            //Write initial data
            $this->writeUTF("initialData");

            //Inner numeric array
            $colnames = $rs->columns;

            $num_count = count($rs->rows);
            $this->writeByte(10); // write  the mixed array code
            $this->writeLong($num_count); // write  the count of items in the array

            //Allow recordsets to create their own serialized data, which is faster
            //since the recordset array is traversed only once
            $numcols = count($colnames);

            $ob = "";
            $be = $this->isBigEndian;
            $fc = pack('N', $numcols);

            for ($i = 0; $i < $num_count; $i++) {
                // write all of the array elements
                $ob .= "\12" . $fc;

                for ($j = 0; $j < $numcols; $j++) { // write all of the array elements

                    $d = $data[$i][$j];
                    if (is_string($d)) { // type as string
                        $os = $this->rsCharsetHandler->transliterate($d);
                        //string flag, string length, and string
                        $ob .= "\2" . pack('n', strlen($os)) . $os;
                    } elseif (is_float($d) || is_int($d)) { // type as double
                        $ob .= "\0";
                        $b = pack('d', $d); // pack the bytes
                        if ($be) { // if we are a big-endian processor
                            $r = strrev($b);
                        } else { // add the bytes to the output
                            $r = $b;
                        }
                        $ob .= $r;
                    } elseif (is_bool($d)) { //type as bool
                        $ob .= "\1";
                        $ob .= pack('c', $d);
                    } elseif (is_null($d)) { // null
                        $ob .= "\5";
                    }
                }
            }
            $this->outBuffer .= $ob;

            //Write cursor
            $this->writeUTF("cursor");
            $this->writeNumber(1);

            //Write service name
            $this->writeUTF("serviceName");
            $this->writeString("PageAbleResult");

            //Write column names
            $this->writeUTF("columnNames");
            $this->writePlainArray($colnames, 'string');

            //Write version number
            $this->writeUTF("version");
            $this->writeNumber(1);

            //Write id
            $this->writeUTF("id");
            $this->writeString($rs->getID());

            //End inner serverInfo object
            $this->writeInt(0); //  write the end object flag 0x00, 0x00, 0x09
            $this->writeByte(9);

            //End outer recordset object
            $this->writeInt(0); //  write the end object flag 0x00, 0x00, 0x09
            $this->writeByte(9);

            $this->paging = -1;
        } else {
            $numObjects = 0;
            $this->writeAmf3ArrayCollectionPreamble();

            //Amf3 array code
            $this->writeByte(0x09);
            $numObjects++;

            $numRows = count($rs->rows);
            $toPack = 2 * $numRows + 1;

            //Write the number of rows
            $this->writeAmf3Int($toPack);

            //No string keys in this array
            $this->writeByte(0x01);

            $numCols = count($rs->columns);

            $columnStringOffsets = array();
            if ($numRows > 0) {
                $j = 0;
                $colNames = array();
                $rows = $rs->rows;

                foreach ($rows as $key => $line) {

                    //Usually we don't use class defs in the serializer since we don't
                    //have sealed objects in php, but for recordsets we do use them
                    //since they are well suited for what we have to do (the same keys
                    //across all objects)
                    if ($key == 0) {
                        $this->outBuffer .= "\12";
                        $this->writeAmf3Int($numCols << 4 | 3);
                        $this->outBuffer .= "\1";
                        foreach ($rs->columns as $key => $val) {
                            $this->writeAmf3String($val);
                        }
                        $defOffset = $this->getAmf3Int(
                            ($this->storedDefinitions) << 2 | 1
                        );
                        $this->storedDefinitions++;
                    } else {
                        $this->outBuffer .= "\12" . $defOffset;
                    }
                    $numObjects++;

                    for ($i = 0; $i < $numCols; $i++) {
                        //Write the col name
                        $value = $line[$i];
                        if (is_string($value)) {
                            $this->outBuffer .= "\6";
                            $value = $this->rsCharsetHandler->transliterate($value);
                            $this->writeAmf3String($value, true);
                        } elseif (is_int($value)) { //int
                            $this->writeAmf3Number($value);
                        } elseif (is_float($value)) { //double
                            $this->outBuffer .= "\5";
                            $b = pack("d", $value); // pack the bytes
                            if ($this->isBigEndian) { // if we are a big-endian processor
                                $r = strrev($b);
                            } else { // add the bytes to the output
                                $r = $b;
                            }

                            $this->outBuffer .= $r;
                        } elseif (is_bool($value)) {
                            $this->outBuffer .= $value ? "\3" : "\2";
                        } else {
                            $this->outBuffer .= "\1"; //null
                        }
                    }
                    //End object
                }
            }

            //Add fake objects to make sure the object counter still works
            for ($i = 0; $i < $numObjects; $i++) {
                $this->storedObjects[] = "";
            }
        }
    }

    /**
     * writeData checks to see if the type was declared and then either
     * auto negotiates the type or relies on the user defined type to
     * serialize the data into amf
     *
     * Note that autoNegotiateType was eliminated in order to tame the
     * call stack which was getting huge and was causing leaks
     *
     * manualType allows the developer to explicitly set the type of
     * the returned data.  The returned data is validated for most of the
     * cases when possible.  Some datatypes like xml and date have to
     * be returned this way in order for the Flash client to correctly serialize them
     *
     * recordsets appears top on the list because that will probably be the most
     * common hit in this method.  Followed by the
     * datatypes that have to be manually set.  Then the auto negotiatable types last.
     * The order may be changed for optimization.
     *
     * @param mixed $d The data
     * @param string $type The optional type
     */
    function writeData(& $d)
    {
        if (is_int($d) || is_float($d)) { // double
            $this->writeNumber($d);
            return;
        } elseif (is_string($d)) { // string
            $this->writeString($d);
            return;
        } elseif (is_bool($d)) { // boolean
            $this->writeBoolean($d);
            return;
        } elseif (is_null($d)) { // null
            $this->writeNull();
            return;
        } elseif (defined('CLIENT_AMF_ENCODING') && CLIENT_AMF_ENCODING == 'amf3') {
            $this->writeByte(0x11);
            $this->writeAmf3Data($d);
            return;
        } elseif (is_array($d)) { // array
            $this->writeArray($d);
            return;
        } elseif (is_resource($d)) { // resource
            $type = get_resource_type($d);
            $subtype = '';
            list($type, $subtype) = $this->sanitizeType($type);
        } elseif (is_object($d)) {
            $className = strtolower(get_class($d));
            if (AMFPHP_PHP5 && $className == 'domdocument') {
                $this->writeXML($d->saveXml());
                return;
            } else if (!AMFPHP_PHP5 && $className == 'domdocument') {
                $this->writeXML($d->dump_mem());
                return;
            } elseif ($className == "simplexmlelement") {
                $this->writeXML($d->asXML());
                return;
            } else if ($className == 'stdclass' && !isset($d->_explicitType)) {
                $this->writeAnonymousObject($d);
                return;
            } elseif ($d instanceof ArrayAccess || $d instanceof ArrayObject) {
                $this->writeArray($d);
                return;
            } else {
                $this->writeTypedObject($d);
                return;
            }
        } else {
            $type = gettype($d);
        }

        switch ($type) {
            default:
                // non of the above so lets assume its a Custom Class thats defined in the client
                $unsanitizedType = '';
                $this->writeTypedObject($unsanitizedType, $d);
                // trigger_error("Unsupported Datatype");
                break;
        }
    }

    /********************************************************************************
     *                             AMF3 related code
     *******************************************************************************/

    function writeAmf3Data(& $d)
    {
        $subtype = '';
        if (is_int($d)) { //int
            $this->writeAmf3Number($d);
            return;
        } elseif (is_float($d)) { //double
            $this->outBuffer .= "\5";
            $this->writeDouble($d);
            return;
        } elseif (is_string($d)) { // string
            $this->outBuffer .= "\6";
            $this->writeAmf3String($d);
            return;
        } elseif (is_bool($d)) { // boolean
            $this->writeAmf3Bool($d);
            return;
        } elseif (is_null($d)) { // null
            $this->writeAmf3Null();
            return;
        } elseif (is_array($d)) { // array
            $this->writeAmf3Array($d);
            return;
        } elseif (is_resource($d)) { // resource
            $type = get_resource_type($d);
            list($type, $subtype) = $this->sanitizeType($type);
        } elseif (is_object($d)) {
            $className = strtolower(get_class($d));
            if (AMFPHP_PHP5 && $className == 'domdocument') {
                $this->writeAmf3Xml($d->saveXml());
                return;
            } else if (!AMFPHP_PHP5 && $className == 'domdocument') {
                $this->writeAmf3Xml($d->dump_mem());
                return;
            } elseif ($className == "simplexmlelement") {
                $this->writeAmf3Xml($d->asXML());
                return;
            } elseif ($className == 'bytearray') {
                $this->writeAmf3ByteArray($d->data);
                return;
            } elseif ($d instanceof ArrayAccess || $d instanceof ArrayObject) {
                $this->writeAmf3Array($d, true);
                return;
            } else {
                $this->writeAmf3Object($d);
                return;
            }
        } else {
            $type = gettype($d);
        }

        switch ($type) {
            default:
                // non of the above so lets assume its a Custom Class thats defined in the client
                //$this->writeTypedObject($unsanitizedType, $d);
                trigger_error("Unsupported Datatype: " . $type);
                break;
        }
    }

    /**
     * Write an ArrayCollection
     */
    function writeAmf3ArrayCollectionPreamble()
    {
        $this->writeByte(0x0a);
        $this->writeByte(0x07);
        $this->writeAmf3String("flex.messaging.io.ArrayCollection");
        $this->storedDefinitions++;
        $this->storedObjects[] = "";
    }

    function writeAmf3Null()
    {
        //Write the null code (0x1) to the output stream.
        $this->outBuffer .= "\1";
    }

    function writeAmf3Bool($d)
    {
        $this->outBuffer .= $d ? "\3" : "\2";
    }

    function writeAmf3Int($d)
    {
        //Sign contraction - the high order bit of the resulting value must match every bit removed from the number
        //Clear 3 bits
        $d &= 0x1fffffff;
        if ($d < 0x80) {
            $this->outBuffer .= chr($d);
        } elseif ($d < 0x4000) {
            $this->outBuffer .= chr($d >> 7 & 0x7f | 0x80) . chr($d & 0x7f);
        } elseif ($d < 0x200000) {
            $this->outBuffer .= chr($d >> 14 & 0x7f | 0x80) . chr($d >> 7 & 0x7f | 0x80) . chr($d & 0x7f);
        } else {
            $this->outBuffer .= chr($d >> 22 & 0x7f | 0x80) . chr($d >> 15 & 0x7f | 0x80) .
                chr($d >> 8 & 0x7f | 0x80) . chr($d & 0xff);
        }
    }

    function writeAmf3String($d, $raw = false)
    {
        if ($d == "") {
            //Write 0x01 to specify the empty ctring
            $this->outBuffer .= "\1";
        } else {
            //if( !isset($this->storedStrings[$d]))
            //{
            if (strlen($d) < 64) {
                $this->storedStrings[$d] = $this->encounteredStrings;
            }
            if (!$raw) {
                $d = $this->charsetHandler->transliterate($d);
            }

            $handle = strlen($d);
            $this->writeAmf3Int($handle * 2 + 1);
            $this->outBuffer .= $d;
            $this->encounteredStrings++;
            return $this->encounteredStrings - 1;
            /*}
            else
            {
                $key = $this->storedStrings[$d];
                $handle = $key << 1;
                $this->writeAmf3Int($handle);
                return $key;
            }*/
        }
    }

    function writeAmf3Array($d, $arrayCollectionable = false)
    {
        //Circular referencing is disabled in arrays
        //Because if the array contains only primitive values,
        //Then === will say that the two arrays are strictly equal
        //if they contain the same values, even if they are really distinct
        //if(($key = patched_array_search($d, $this->storedObjects, TRUE)) === FALSE )
        //{
        if (count($this->storedObjects) < MAX_STORED_OBJECTS) {
            $this->storedObjects[] = & $d;
        }

        $numeric = array(); // holder to store the numeric keys
        $string = array(); // holder to store the string keys
        $len = count($d); // get the total number of entries for the array
        $largestKey = -1;
        foreach ($d as $key => $data) { // loop over each element
            if (is_int($key) && ($key >= 0)) { // make sure the keys are numeric
                $numeric[$key] = $data; // The key is an index in an array
                $largestKey = max($largestKey, $key);
            } else {
                $string[$key] = $data; // The key is a property of an object
            }
        }
        $num_count = count($numeric); // get the number of numeric keys
        $str_count = count($string); // get the number of string keys

        if (($str_count > 0 && $num_count == 0) ||
            ($num_count > 0 && $largestKey != $num_count - 1)
        ) { // this is a mixed array
            $this->writeAmf3ObjectFromArray($numeric + $string); // write the numeric and string keys in the mixed array
        } else { // this is just an array
            if ($arrayCollectionable) {
                $this->writeAmf3ArrayCollectionPreamble();
            }

            $num_count = count($numeric);

            $this->outBuffer .= "\11";
            $handle = $num_count * 2 + 1;
            $this->writeAmf3Int($handle);

            foreach ($string as $key => $val) {
                $this->writeAmf3String($key);
                $this->writeAmf3Data($val);
            }
            $this->writeAmf3String(""); //End start hash

            for ($i = 0; $i < $num_count; $i++) {
                $this->writeAmf3Data($numeric[$i]);
            }
        }
        //}
        //else
        //{
        //	$handle = $key << 1;
        //	$this->outBuffer .= "\11";
        //	$this->writeAmf3Int($handle);
        //}
    }

    function writeAmf3ObjectFromArray($d)
    {
        //Type this as a dynamic object
        $this->outBuffer .= "\12\13\1";

        foreach ($d as $key => $val) {
            $this->writeAmf3String($key);
            $this->writeAmf3Data($val);
        }
        //Now we close the open object
        $this->outBuffer .= "\1";
    }

    /*
    public void WriteAMF3DateTime(DateTime value)
    {
        if( !_objectReferences.Contains(value) )
        {
            _objectReferences.Add(value, _objectReferences.Count);
            int handle = 1;
            WriteAMF3IntegerData(handle);

            // Write date (milliseconds from 1970).
            DateTime timeStart = new DateTime(1970, 1, 1, 0, 0, 0);

            string timezoneCompensation = System.Configuration.ConfigurationSettings.AppSettings["timezoneCompensation"];
            if( timezoneCompensation != null && ( timezoneCompensation.ToLower() == "auto" ) )
            {
                value = value.ToUniversalTime();
            }

            TimeSpan span = value.Subtract(timeStart);
            long milliSeconds = (long)span.TotalMilliseconds;
            long date = BitConverter.DoubleToInt64Bits((double)milliSeconds);
            this.WriteLong(date);
        }
        else
        {
            int handle = (int)_objectReferences[value];
            handle = handle << 1;
            WriteAMF3IntegerData(handle);
        }
    }
    */

    function getAmf3Int($d)
    {
        $d &= 0x1fffffff;
        if ($d < 0x80) {
            return chr($d);
        } elseif ($d < 0x4000) {
            return chr($d >> 7 & 0x7f | 0x80) . chr($d & 0x7f);
        } elseif ($d < 0x200000) {
            return chr($d >> 14 & 0x7f | 0x80) . chr($d >> 7 & 0x7f | 0x80) . chr($d & 0x7f);
        } else {
            return chr($d >> 22 & 0x7f | 0x80) . chr($d >> 15 & 0x7f | 0x80) .
            chr($d >> 8 & 0x7f | 0x80) . chr($d & 0xff);
        }
    }

    function writeAmf3Number($d)
    {
        if ($d >= -268435456 && $d <= 268435455) //check valid range for 29bits
        {
            $this->outBuffer .= "\4";
            $this->writeAmf3Int($d);
        } else {
            //overflow condition would occur upon int conversion
            $this->outBuffer .= "\5";
            $this->writeDouble($d);
        }
    }

    function writeAmf3Xml($d)
    {
        $d = preg_replace('/\>(\n|\r|\r\n| |\t)*\</', '><', trim($d));
        $this->writeByte(0x07);
        $this->writeAmf3String($d);
    }

    function writeAmf3ByteArray($d)
    {
        $this->writeByte(0x0C);
        $this->writeAmf3String($d, true);
    }

    function writeAmf3Object($d)
    {
        //Write the object tag
        $this->outBuffer .= "\12";
        if (($key = array_search($d, $this->storedObjects, TRUE)) === FALSE && $key === FALSE) {
            if (count($this->storedObjects) < MAX_STORED_OBJECTS) {
                $this->storedObjects[] = & $d;
            }

            $this->storedDefinitions++;

            //Type the object as an array
            if (AMFPHP_PHP5) {
                $obj = $d;
            } else {
                $obj = (array)$d;
            }
            $realObj = array();
            foreach ($obj as $key => $val) {
                if ($key[0] != "\0" && $key != '_explicitType') //Don't show private members
                {
                    $realObj[$key] = $val;
                }
            }

            //Type this as a dynamic object
            $this->outBuffer .= "\13";

            $classname = $this->getClassName($d);

            $this->writeAmf3String($classname);

            foreach ($realObj as $key => $val) {
                $this->writeAmf3String($key);
                $this->writeAmf3Data($val);
            }
            //Now we close the open object
            $this->outBuffer .= "\1";
        } else {
            $handle = $key << 1;
            $this->writeAmf3Int($handle);
        }
    }
}
