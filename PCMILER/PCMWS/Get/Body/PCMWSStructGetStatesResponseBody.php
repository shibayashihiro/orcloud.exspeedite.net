<?php
/**
 * File for class PCMWSStructGetStatesResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGetStatesResponseBody originally named GetStatesResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGetStatesResponseBody extends PCMWSWsdlClass
{
    /**
     * The States
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfStateCountry
     */
    public $States;
    /**
     * Constructor method for GetStatesResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfStateCountry $_states
     * @return PCMWSStructGetStatesResponseBody
     */
    public function __construct($_states = NULL)
    {
        parent::__construct(array('States'=>($_states instanceof PCMWSStructArrayOfStateCountry)?$_states:new PCMWSStructArrayOfStateCountry($_states)),false);
    }
    /**
     * Get States value
     * @return PCMWSStructArrayOfStateCountry|null
     */
    public function getStates()
    {
        return $this->States;
    }
    /**
     * Set States value
     * @param PCMWSStructArrayOfStateCountry $_states the States
     * @return PCMWSStructArrayOfStateCountry
     */
    public function setStates($_states)
    {
        return ($this->States = $_states);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGetStatesResponseBody
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
