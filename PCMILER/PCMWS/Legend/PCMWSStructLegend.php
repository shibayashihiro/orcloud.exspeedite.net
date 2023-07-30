<?php
/**
 * File for class PCMWSStructLegend
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructLegend originally named Legend
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructLegend extends PCMWSWsdlClass
{
    /**
     * The Type
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumLegendType
     */
    public $Type;
    /**
     * The DrawOnMap
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $DrawOnMap;
    /**
     * Constructor method for Legend
     * @see parent::__construct()
     * @param PCMWSEnumLegendType $_type
     * @param boolean $_drawOnMap
     * @return PCMWSStructLegend
     */
    public function __construct($_type = NULL,$_drawOnMap = NULL)
    {
        parent::__construct(array('Type'=>$_type,'DrawOnMap'=>$_drawOnMap),false);
    }
    /**
     * Get Type value
     * @return PCMWSEnumLegendType|null
     */
    public function getType()
    {
        return $this->Type;
    }
    /**
     * Set Type value
     * @uses PCMWSEnumLegendType::valueIsValid()
     * @param PCMWSEnumLegendType $_type the Type
     * @return PCMWSEnumLegendType
     */
    public function setType($_type)
    {
        if(!PCMWSEnumLegendType::valueIsValid($_type))
        {
            return false;
        }
        return ($this->Type = $_type);
    }
    /**
     * Get DrawOnMap value
     * @return boolean|null
     */
    public function getDrawOnMap()
    {
        return $this->DrawOnMap;
    }
    /**
     * Set DrawOnMap value
     * @param boolean $_drawOnMap the DrawOnMap
     * @return boolean
     */
    public function setDrawOnMap($_drawOnMap)
    {
        return ($this->DrawOnMap = $_drawOnMap);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructLegend
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
