<?php
/**
 * File for class PCMWSEnumShapeType
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSEnumShapeType originally named ShapeType
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Enumerations
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSEnumShapeType extends PCMWSWsdlClass
{
    /**
     * Constant for value 'None'
     * @return string 'None'
     */
    const VALUE_NONE = 'None';
    /**
     * Constant for value 'Line'
     * @return string 'Line'
     */
    const VALUE_LINE = 'Line';
    /**
     * Constant for value 'Circle'
     * @return string 'Circle'
     */
    const VALUE_CIRCLE = 'Circle';
    /**
     * Constant for value 'Rect'
     * @return string 'Rect'
     */
    const VALUE_RECT = 'Rect';
    /**
     * Constant for value 'Ellipse'
     * @return string 'Ellipse'
     */
    const VALUE_ELLIPSE = 'Ellipse';
    /**
     * Constant for value 'Polygon'
     * @return string 'Polygon'
     */
    const VALUE_POLYGON = 'Polygon';
    /**
     * Return true if value is allowed
     * @uses PCMWSEnumShapeType::VALUE_NONE
     * @uses PCMWSEnumShapeType::VALUE_LINE
     * @uses PCMWSEnumShapeType::VALUE_CIRCLE
     * @uses PCMWSEnumShapeType::VALUE_RECT
     * @uses PCMWSEnumShapeType::VALUE_ELLIPSE
     * @uses PCMWSEnumShapeType::VALUE_POLYGON
     * @param mixed $_value value
     * @return bool true|false
     */
    public static function valueIsValid($_value)
    {
        return in_array($_value,array(PCMWSEnumShapeType::VALUE_NONE,PCMWSEnumShapeType::VALUE_LINE,PCMWSEnumShapeType::VALUE_CIRCLE,PCMWSEnumShapeType::VALUE_RECT,PCMWSEnumShapeType::VALUE_ELLIPSE,PCMWSEnumShapeType::VALUE_POLYGON));
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
