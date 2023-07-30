<?php
/**
 * File for class PCMWSStructRouteLegOptions
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteLegOptions originally named RouteLegOptions
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteLegOptions extends PCMWSWsdlClass
{
    /**
     * The LineOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRouteLineOptions
     */
    public $LineOptions;
    /**
     * The TextOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfRouteLabelOptions
     */
    public $TextOptions;
    /**
     * Constructor method for RouteLegOptions
     * @see parent::__construct()
     * @param PCMWSStructArrayOfRouteLineOptions $_lineOptions
     * @param PCMWSStructArrayOfRouteLabelOptions $_textOptions
     * @return PCMWSStructRouteLegOptions
     */
    public function __construct($_lineOptions = NULL,$_textOptions = NULL)
    {
        parent::__construct(array('LineOptions'=>($_lineOptions instanceof PCMWSStructArrayOfRouteLineOptions)?$_lineOptions:new PCMWSStructArrayOfRouteLineOptions($_lineOptions),'TextOptions'=>($_textOptions instanceof PCMWSStructArrayOfRouteLabelOptions)?$_textOptions:new PCMWSStructArrayOfRouteLabelOptions($_textOptions)),false);
    }
    /**
     * Get LineOptions value
     * @return PCMWSStructArrayOfRouteLineOptions|null
     */
    public function getLineOptions()
    {
        return $this->LineOptions;
    }
    /**
     * Set LineOptions value
     * @param PCMWSStructArrayOfRouteLineOptions $_lineOptions the LineOptions
     * @return PCMWSStructArrayOfRouteLineOptions
     */
    public function setLineOptions($_lineOptions)
    {
        return ($this->LineOptions = $_lineOptions);
    }
    /**
     * Get TextOptions value
     * @return PCMWSStructArrayOfRouteLabelOptions|null
     */
    public function getTextOptions()
    {
        return $this->TextOptions;
    }
    /**
     * Set TextOptions value
     * @param PCMWSStructArrayOfRouteLabelOptions $_textOptions the TextOptions
     * @return PCMWSStructArrayOfRouteLabelOptions
     */
    public function setTextOptions($_textOptions)
    {
        return ($this->TextOptions = $_textOptions);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteLegOptions
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
