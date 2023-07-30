<?php
/**
 * File for class PCMWSStructSetAvoidFavorResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructSetAvoidFavorResponse originally named SetAvoidFavorResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructSetAvoidFavorResponse extends PCMWSWsdlClass
{
    /**
     * The SetAvoidFavorResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructResponse
     */
    public $SetAvoidFavorResult;
    /**
     * Constructor method for SetAvoidFavorResponse
     * @see parent::__construct()
     * @param PCMWSStructResponse $_setAvoidFavorResult
     * @return PCMWSStructSetAvoidFavorResponse
     */
    public function __construct($_setAvoidFavorResult = NULL)
    {
        parent::__construct(array('SetAvoidFavorResult'=>$_setAvoidFavorResult),false);
    }
    /**
     * Get SetAvoidFavorResult value
     * @return PCMWSStructResponse|null
     */
    public function getSetAvoidFavorResult()
    {
        return $this->SetAvoidFavorResult;
    }
    /**
     * Set SetAvoidFavorResult value
     * @param PCMWSStructResponse $_setAvoidFavorResult the SetAvoidFavorResult
     * @return PCMWSStructResponse
     */
    public function setSetAvoidFavorResult($_setAvoidFavorResult)
    {
        return ($this->SetAvoidFavorResult = $_setAvoidFavorResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructSetAvoidFavorResponse
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
