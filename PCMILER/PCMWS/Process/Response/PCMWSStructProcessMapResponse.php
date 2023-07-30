<?php
/**
 * File for class PCMWSStructProcessMapResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessMapResponse originally named ProcessMapResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessMapResponse extends PCMWSWsdlClass
{
    /**
     * The ProcessMapResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructMapRoutesResponse
     */
    public $ProcessMapResult;
    /**
     * Constructor method for ProcessMapResponse
     * @see parent::__construct()
     * @param PCMWSStructMapRoutesResponse $_processMapResult
     * @return PCMWSStructProcessMapResponse
     */
    public function __construct($_processMapResult = NULL)
    {
        parent::__construct(array('ProcessMapResult'=>$_processMapResult),false);
    }
    /**
     * Get ProcessMapResult value
     * @return PCMWSStructMapRoutesResponse|null
     */
    public function getProcessMapResult()
    {
        return $this->ProcessMapResult;
    }
    /**
     * Set ProcessMapResult value
     * @param PCMWSStructMapRoutesResponse $_processMapResult the ProcessMapResult
     * @return PCMWSStructMapRoutesResponse
     */
    public function setProcessMapResult($_processMapResult)
    {
        return ($this->ProcessMapResult = $_processMapResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessMapResponse
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
