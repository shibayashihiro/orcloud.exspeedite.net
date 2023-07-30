<?php
/**
 * File for class PCMWSStructImportAvoidFavorSetResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructImportAvoidFavorSetResponse originally named ImportAvoidFavorSetResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructImportAvoidFavorSetResponse extends PCMWSWsdlClass
{
    /**
     * The ImportAvoidFavorSetResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructImportAFSetResponse
     */
    public $ImportAvoidFavorSetResult;
    /**
     * Constructor method for ImportAvoidFavorSetResponse
     * @see parent::__construct()
     * @param PCMWSStructImportAFSetResponse $_importAvoidFavorSetResult
     * @return PCMWSStructImportAvoidFavorSetResponse
     */
    public function __construct($_importAvoidFavorSetResult = NULL)
    {
        parent::__construct(array('ImportAvoidFavorSetResult'=>$_importAvoidFavorSetResult),false);
    }
    /**
     * Get ImportAvoidFavorSetResult value
     * @return PCMWSStructImportAFSetResponse|null
     */
    public function getImportAvoidFavorSetResult()
    {
        return $this->ImportAvoidFavorSetResult;
    }
    /**
     * Set ImportAvoidFavorSetResult value
     * @param PCMWSStructImportAFSetResponse $_importAvoidFavorSetResult the ImportAvoidFavorSetResult
     * @return PCMWSStructImportAFSetResponse
     */
    public function setImportAvoidFavorSetResult($_importAvoidFavorSetResult)
    {
        return ($this->ImportAvoidFavorSetResult = $_importAvoidFavorSetResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructImportAvoidFavorSetResponse
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
