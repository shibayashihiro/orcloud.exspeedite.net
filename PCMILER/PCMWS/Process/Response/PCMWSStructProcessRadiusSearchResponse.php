<?php
/**
 * File for class PCMWSStructProcessRadiusSearchResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructProcessRadiusSearchResponse originally named ProcessRadiusSearchResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructProcessRadiusSearchResponse extends PCMWSWsdlClass
{
    /**
     * The ProcessRadiusSearchResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRadiusSearchResponse
     */
    public $ProcessRadiusSearchResult;
    /**
     * Constructor method for ProcessRadiusSearchResponse
     * @see parent::__construct()
     * @param PCMWSStructRadiusSearchResponse $_processRadiusSearchResult
     * @return PCMWSStructProcessRadiusSearchResponse
     */
    public function __construct($_processRadiusSearchResult = NULL)
    {
        parent::__construct(array('ProcessRadiusSearchResult'=>$_processRadiusSearchResult),false);
    }
    /**
     * Get ProcessRadiusSearchResult value
     * @return PCMWSStructRadiusSearchResponse|null
     */
    public function getProcessRadiusSearchResult()
    {
        return $this->ProcessRadiusSearchResult;
    }
    /**
     * Set ProcessRadiusSearchResult value
     * @param PCMWSStructRadiusSearchResponse $_processRadiusSearchResult the ProcessRadiusSearchResult
     * @return PCMWSStructRadiusSearchResponse
     */
    public function setProcessRadiusSearchResult($_processRadiusSearchResult)
    {
        return ($this->ProcessRadiusSearchResult = $_processRadiusSearchResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructProcessRadiusSearchResponse
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
