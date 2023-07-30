<?php
/**
 * File for class PCMWSStructGenerateDriveTimePolygonResponse
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGenerateDriveTimePolygonResponse originally named GenerateDriveTimePolygonResponse
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGenerateDriveTimePolygonResponse extends PCMWSWsdlClass
{
    /**
     * The GenerateDriveTimePolygonResult
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructDriveTimePolygonResponse
     */
    public $GenerateDriveTimePolygonResult;
    /**
     * Constructor method for GenerateDriveTimePolygonResponse
     * @see parent::__construct()
     * @param PCMWSStructDriveTimePolygonResponse $_generateDriveTimePolygonResult
     * @return PCMWSStructGenerateDriveTimePolygonResponse
     */
    public function __construct($_generateDriveTimePolygonResult = NULL)
    {
        parent::__construct(array('GenerateDriveTimePolygonResult'=>$_generateDriveTimePolygonResult),false);
    }
    /**
     * Get GenerateDriveTimePolygonResult value
     * @return PCMWSStructDriveTimePolygonResponse|null
     */
    public function getGenerateDriveTimePolygonResult()
    {
        return $this->GenerateDriveTimePolygonResult;
    }
    /**
     * Set GenerateDriveTimePolygonResult value
     * @param PCMWSStructDriveTimePolygonResponse $_generateDriveTimePolygonResult the GenerateDriveTimePolygonResult
     * @return PCMWSStructDriveTimePolygonResponse
     */
    public function setGenerateDriveTimePolygonResult($_generateDriveTimePolygonResult)
    {
        return ($this->GenerateDriveTimePolygonResult = $_generateDriveTimePolygonResult);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGenerateDriveTimePolygonResponse
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
