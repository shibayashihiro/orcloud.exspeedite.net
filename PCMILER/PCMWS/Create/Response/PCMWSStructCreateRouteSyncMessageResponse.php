<?php
/**
 * File for class PCMWSStructCreateRouteSyncMessageResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructCreateRouteSyncMessageResponse originally named CreateRouteSyncMessageResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructCreateRouteSyncMessageResponse extends PCMWSWsdlClass
{
    /**
     * The CreateRouteSyncMessageResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructCreateRouteSyncMsgResponse
     */
    public $CreateRouteSyncMessageResult;
    /**
     * Constructor method for CreateRouteSyncMessageResponse
     * @see parent::__construct()
     * @param PCMWSStructCreateRouteSyncMsgResponse $_createRouteSyncMessageResult
     * @return PCMWSStructCreateRouteSyncMessageResponse
     */
    public function __construct($_createRouteSyncMessageResult = NULL)
    {
        parent::__construct(array('CreateRouteSyncMessageResult'=>$_createRouteSyncMessageResult),false);
    }
    /**
     * Get CreateRouteSyncMessageResult value
     * @return PCMWSStructCreateRouteSyncMsgResponse|null
     */
    public function getCreateRouteSyncMessageResult()
    {
        return $this->CreateRouteSyncMessageResult;
    }
    /**
     * Set CreateRouteSyncMessageResult value
     * @param PCMWSStructCreateRouteSyncMsgResponse $_createRouteSyncMessageResult the CreateRouteSyncMessageResult
     * @return PCMWSStructCreateRouteSyncMsgResponse
     */
    public function setCreateRouteSyncMessageResult($_createRouteSyncMessageResult)
    {
        return ($this->CreateRouteSyncMessageResult = $_createRouteSyncMessageResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructCreateRouteSyncMessageResponse
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
