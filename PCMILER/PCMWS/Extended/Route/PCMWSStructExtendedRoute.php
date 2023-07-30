<?php
/**
 * File for class PCMWSStructExtendedRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructExtendedRoute originally named ExtendedRoute
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructExtendedRoute extends PCMWSStructRoute
{
    /**
     * The ExtendedOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructSharedOptions
     */
    public $ExtendedOptions;
    /**
     * Constructor method for ExtendedRoute
     * @see parent::__construct()
     * @param PCMWSStructSharedOptions $_extendedOptions
     * @return PCMWSStructExtendedRoute
     */
    public function __construct($_extendedOptions = NULL)
    {
        PCMWSWsdlClass::__construct(array('ExtendedOptions'=>$_extendedOptions),false);
    }
    /**
     * Get ExtendedOptions value
     * @return PCMWSStructSharedOptions|null
     */
    public function getExtendedOptions()
    {
        return $this->ExtendedOptions;
    }
    /**
     * Set ExtendedOptions value
     * @param PCMWSStructSharedOptions $_extendedOptions the ExtendedOptions
     * @return PCMWSStructSharedOptions
     */
    public function setExtendedOptions($_extendedOptions)
    {
        return ($this->ExtendedOptions = $_extendedOptions);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructExtendedRoute
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
