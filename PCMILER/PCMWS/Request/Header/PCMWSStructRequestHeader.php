<?php
/**
 * File for class PCMWSStructRequestHeader
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRequestHeader originally named RequestHeader
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRequestHeader extends PCMWSWsdlClass
{
    /**
     * The DataVersion
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DataVersion;
    /**
     * The RequestType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $RequestType;
    /**
     * Constructor method for RequestHeader
     * @see parent::__construct()
     * @param string $_dataVersion
     * @param string $_requestType
     * @return PCMWSStructRequestHeader
     */
    public function __construct($_dataVersion = NULL,$_requestType = NULL)
    {
        parent::__construct(array('DataVersion'=>$_dataVersion,'RequestType'=>$_requestType),false);
    }
    /**
     * Get DataVersion value
     * @return string|null
     */
    public function getDataVersion()
    {
        return $this->DataVersion;
    }
    /**
     * Set DataVersion value
     * @param string $_dataVersion the DataVersion
     * @return string
     */
    public function setDataVersion($_dataVersion)
    {
        return ($this->DataVersion = $_dataVersion);
    }
    /**
     * Get RequestType value
     * @return string|null
     */
    public function getRequestType()
    {
        return $this->RequestType;
    }
    /**
     * Set RequestType value
     * @param string $_requestType the RequestType
     * @return string
     */
    public function setRequestType($_requestType)
    {
        return ($this->RequestType = $_requestType);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRequestHeader
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
