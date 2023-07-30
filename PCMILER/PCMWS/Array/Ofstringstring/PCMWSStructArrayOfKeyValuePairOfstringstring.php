<?php
/**
 * File for class PCMWSStructArrayOfKeyValuePairOfstringstring
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructArrayOfKeyValuePairOfstringstring originally named ArrayOfKeyValuePairOfstringstring
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd2}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructArrayOfKeyValuePairOfstringstring extends PCMWSWsdlClass
{
    /**
     * The KeyValuePairOfstringstring
     * Meta informations extracted from the WSDL
     * - maxOccurs : unbounded
     * - minOccurs : 0
     * @var PCMWSStructKeyValuePairOfstringstring
     */
    public $KeyValuePairOfstringstring;
    /**
     * Constructor method for ArrayOfKeyValuePairOfstringstring
     * @see parent::__construct()
     * @param PCMWSStructKeyValuePairOfstringstring $_keyValuePairOfstringstring
     * @return PCMWSStructArrayOfKeyValuePairOfstringstring
     */
    public function __construct($_keyValuePairOfstringstring = NULL)
    {
        parent::__construct(array('KeyValuePairOfstringstring'=>$_keyValuePairOfstringstring),false);
    }
    /**
     * Get KeyValuePairOfstringstring value
     * @return PCMWSStructKeyValuePairOfstringstring|null
     */
    public function getKeyValuePairOfstringstring()
    {
        return $this->KeyValuePairOfstringstring;
    }
    /**
     * Set KeyValuePairOfstringstring value
     * @param PCMWSStructKeyValuePairOfstringstring $_keyValuePairOfstringstring the KeyValuePairOfstringstring
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function setKeyValuePairOfstringstring($_keyValuePairOfstringstring)
    {
        return ($this->KeyValuePairOfstringstring = $_keyValuePairOfstringstring);
    }
    /**
     * Returns the current element
     * @see PCMWSWsdlClass::current()
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function current()
    {
        return parent::current();
    }
    /**
     * Returns the indexed element
     * @see PCMWSWsdlClass::item()
     * @param int $_index
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function item($_index)
    {
        return parent::item($_index);
    }
    /**
     * Returns the first element
     * @see PCMWSWsdlClass::first()
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function first()
    {
        return parent::first();
    }
    /**
     * Returns the last element
     * @see PCMWSWsdlClass::last()
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function last()
    {
        return parent::last();
    }
    /**
     * Returns the element at the offset
     * @see PCMWSWsdlClass::last()
     * @param int $_offset
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function offsetGet($_offset)
    {
        return parent::offsetGet($_offset);
    }
    /**
     * Returns the attribute name
     * @see PCMWSWsdlClass::getAttributeName()
     * @return string KeyValuePairOfstringstring
     */
    public function getAttributeName()
    {
        return 'KeyValuePairOfstringstring';
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructArrayOfKeyValuePairOfstringstring
     */
    public static function __set_state(array $_array)
    {
	    $_array[] = __CLASS__;
        return parent::__set_state($_array);
    }
    /**
     * Method returning the class name
     * @return string __CLASS__
     */
    public function __toString()
    {
        return __CLASS__;
    }
}
