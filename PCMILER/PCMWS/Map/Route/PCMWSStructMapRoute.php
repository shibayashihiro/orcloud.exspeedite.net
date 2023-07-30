<?php
/**
 * File for class PCMWSStructMapRoute
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructMapRoute originally named MapRoute
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructMapRoute extends PCMWSStructRoute
{
    /**
     * The DrawLeastCost
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $DrawLeastCost;
    /**
     * The RouteLegOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructRouteLegOptions
     */
    public $RouteLegOptions;
    /**
     * The StopLabelDrawer
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumStopLabelType
     */
    public $StopLabelDrawer;
    /**
     * Constructor method for MapRoute
     * @see parent::__construct()
     * @param boolean $_drawLeastCost
     * @param PCMWSStructRouteLegOptions $_routeLegOptions
     * @param PCMWSEnumStopLabelType $_stopLabelDrawer
     * @return PCMWSStructMapRoute
     */
    public function __construct($_drawLeastCost = NULL,$_routeLegOptions = NULL,$_stopLabelDrawer = NULL)
    {
        PCMWSWsdlClass::__construct(array('DrawLeastCost'=>$_drawLeastCost,'RouteLegOptions'=>$_routeLegOptions,'StopLabelDrawer'=>$_stopLabelDrawer),false);
    }
    /**
     * Get DrawLeastCost value
     * @return boolean|null
     */
    public function getDrawLeastCost()
    {
        return $this->DrawLeastCost;
    }
    /**
     * Set DrawLeastCost value
     * @param boolean $_drawLeastCost the DrawLeastCost
     * @return boolean
     */
    public function setDrawLeastCost($_drawLeastCost)
    {
        return ($this->DrawLeastCost = $_drawLeastCost);
    }
    /**
     * Get RouteLegOptions value
     * @return PCMWSStructRouteLegOptions|null
     */
    public function getRouteLegOptions()
    {
        return $this->RouteLegOptions;
    }
    /**
     * Set RouteLegOptions value
     * @param PCMWSStructRouteLegOptions $_routeLegOptions the RouteLegOptions
     * @return PCMWSStructRouteLegOptions
     */
    public function setRouteLegOptions($_routeLegOptions)
    {
        return ($this->RouteLegOptions = $_routeLegOptions);
    }
    /**
     * Get StopLabelDrawer value
     * @return PCMWSEnumStopLabelType|null
     */
    public function getStopLabelDrawer()
    {
        return $this->StopLabelDrawer;
    }
    /**
     * Set StopLabelDrawer value
     * @uses PCMWSEnumStopLabelType::valueIsValid()
     * @param PCMWSEnumStopLabelType $_stopLabelDrawer the StopLabelDrawer
     * @return PCMWSEnumStopLabelType
     */
    public function setStopLabelDrawer($_stopLabelDrawer)
    {
        if(!PCMWSEnumStopLabelType::valueIsValid($_stopLabelDrawer))
        {
            return false;
        }
        return ($this->StopLabelDrawer = $_stopLabelDrawer);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructMapRoute
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
