<?php
/**
 * File for class PCMWSStructKeyValuePairOfstringstring
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructKeyValuePairOfstringstring originally named KeyValuePairOfstringstring
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd2}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructKeyValuePairOfstringstring extends PCMWSWsdlClass
{
    /**
     * The key
     * Meta informations extracted from the WSDL
     * - nillable : true
     * @var string
     */
    public $key;
    /**
     * The value
     * Meta informations extracted from the WSDL
     * - nillable : true
     * @var string
     */
    public $value;
    /**
     * Constructor method for KeyValuePairOfstringstring
     * @see parent::__construct()
     * @param string $_key
     * @param string $_value
     * @return PCMWSStructKeyValuePairOfstringstring
     */
    public function __construct($_key = NULL,$_value = NULL)
    {
        parent::__construct(array('key'=>$_key,'value'=>$_value),false);
    }
    /**
     * Get key value
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }
    /**
     * Set key value
     * @param string $_key the key
     * @return string
     */
    public function setKey($_key)
    {
        return ($this->key = $_key);
    }
    /**
     * Get value value
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Set value value
     * @param string $_value the value
     * @return string
     */
    public function setValue($_value)
    {
        return ($this->value = $_value);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructKeyValuePairOfstringstring
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
