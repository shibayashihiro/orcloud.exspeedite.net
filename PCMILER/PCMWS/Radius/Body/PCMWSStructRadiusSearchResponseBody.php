<?php
/**
 * File for class PCMWSStructRadiusSearchResponseBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRadiusSearchResponseBody originally named RadiusSearchResponseBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRadiusSearchResponseBody extends PCMWSWsdlClass
{
    /**
     * The POISearchMatches
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRadiusSearchMatch
     */
    public $POISearchMatches;
    /**
     * Constructor method for RadiusSearchResponseBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfRadiusSearchMatch $_pOISearchMatches
     * @return PCMWSStructRadiusSearchResponseBody
     */
    public function __construct($_pOISearchMatches = NULL)
    {
        parent::__construct(array('POISearchMatches'=>($_pOISearchMatches instanceof PCMWSStructArrayOfRadiusSearchMatch)?$_pOISearchMatches:new PCMWSStructArrayOfRadiusSearchMatch($_pOISearchMatches)),false);
    }
    /**
     * Get POISearchMatches value
     * @return PCMWSStructArrayOfRadiusSearchMatch|null
     */
    public function getPOISearchMatches()
    {
        return $this->POISearchMatches;
    }
    /**
     * Set POISearchMatches value
     * @param PCMWSStructArrayOfRadiusSearchMatch $_pOISearchMatches the POISearchMatches
     * @return PCMWSStructArrayOfRadiusSearchMatch
     */
    public function setPOISearchMatches($_pOISearchMatches)
    {
        return ($this->POISearchMatches = $_pOISearchMatches);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRadiusSearchResponseBody
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
