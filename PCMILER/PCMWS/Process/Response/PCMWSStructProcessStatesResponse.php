<?php
/**
 * File for class PCMWSStructProcessStatesResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessStatesResponse originally named ProcessStatesResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessStatesResponse extends PCMWSWsdlClass
{
    /**
     * The ProcessStatesResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructGetStatesResponse
     */
    public $ProcessStatesResult;
    /**
     * Constructor method for ProcessStatesResponse
     * @see parent::__construct()
     * @param PCMWSStructGetStatesResponse $_processStatesResult
     * @return PCMWSStructProcessStatesResponse
     */
    public function __construct($_processStatesResult = NULL)
    {
        parent::__construct(array('ProcessStatesResult'=>$_processStatesResult),false);
    }
    /**
     * Get ProcessStatesResult value
     * @return PCMWSStructGetStatesResponse|null
     */
    public function getProcessStatesResult()
    {
        return $this->ProcessStatesResult;
    }
    /**
     * Set ProcessStatesResult value
     * @param PCMWSStructGetStatesResponse $_processStatesResult the ProcessStatesResult
     * @return PCMWSStructGetStatesResponse
     */
    public function setProcessStatesResult($_processStatesResult)
    {
        return ($this->ProcessStatesResult = $_processStatesResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessStatesResponse
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
