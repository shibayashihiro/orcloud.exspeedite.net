<?php
/**
 * File for class PCMWSStructCreateRouteSyncMsgResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCreateRouteSyncMsgResponseBody originally named CreateRouteSyncMsgResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCreateRouteSyncMsgResponseBody extends PCMWSWsdlClass
{
    /**
     * The MessageBytes
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var base64Binary
     */
    public $MessageBytes;
    /**
     * Constructor method for CreateRouteSyncMsgResponseBody
     * @see parent::__construct()
     * @param base64Binary $_messageBytes
     * @return PCMWSStructCreateRouteSyncMsgResponseBody
     */
    public function __construct($_messageBytes = NULL)
    {
        parent::__construct(array('MessageBytes'=>$_messageBytes),false);
    }
    /**
     * Get MessageBytes value
     * @return base64Binary|null
     */
    public function getMessageBytes()
    {
        return $this->MessageBytes;
    }
    /**
     * Set MessageBytes value
     * @param base64Binary $_messageBytes the MessageBytes
     * @return base64Binary
     */
    public function setMessageBytes($_messageBytes)
    {
        return ($this->MessageBytes = $_messageBytes);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCreateRouteSyncMsgResponseBody
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
