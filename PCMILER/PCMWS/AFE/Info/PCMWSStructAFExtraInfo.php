<?php
/**
 * File for class PCMWSStructAFExtraInfo
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAFExtraInfo originally named AFExtraInfo
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd5}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAFExtraInfo extends PCMWSWsdlClass
{
    /**
     * The IsBorder
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var boolean
     */
    public $IsBorder;
    /**
     * The MaxAddress
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $MaxAddress;
    /**
     * The MinAddress
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $MinAddress;
    /**
     * The RoadClass
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedByte
     */
    public $RoadClass;
    /**
     * The RouteNum1
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $RouteNum1;
    /**
     * The RouteNum2
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $RouteNum2;
    /**
     * The RouteNum3
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var unsignedInt
     */
    public $RouteNum3;
    /**
     * The StreetName
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StreetName;
    /**
     * Constructor method for AFExtraInfo
     * @see parent::__construct()
     * @param boolean $_isBorder
     * @param unsignedInt $_maxAddress
     * @param unsignedInt $_minAddress
     * @param unsignedByte $_roadClass
     * @param unsignedInt $_routeNum1
     * @param unsignedInt $_routeNum2
     * @param unsignedInt $_routeNum3
     * @param string $_streetName
     * @return PCMWSStructAFExtraInfo
     */
    public function __construct($_isBorder = NULL,$_maxAddress = NULL,$_minAddress = NULL,$_roadClass = NULL,$_routeNum1 = NULL,$_routeNum2 = NULL,$_routeNum3 = NULL,$_streetName = NULL)
    {
        parent::__construct(array('IsBorder'=>$_isBorder,'MaxAddress'=>$_maxAddress,'MinAddress'=>$_minAddress,'RoadClass'=>$_roadClass,'RouteNum1'=>$_routeNum1,'RouteNum2'=>$_routeNum2,'RouteNum3'=>$_routeNum3,'StreetName'=>$_streetName),false);
    }
    /**
     * Get IsBorder value
     * @return boolean|null
     */
    public function getIsBorder()
    {
        return $this->IsBorder;
    }
    /**
     * Set IsBorder value
     * @param boolean $_isBorder the IsBorder
     * @return boolean
     */
    public function setIsBorder($_isBorder)
    {
        return ($this->IsBorder = $_isBorder);
    }
    /**
     * Get MaxAddress value
     * @return unsignedInt|null
     */
    public function getMaxAddress()
    {
        return $this->MaxAddress;
    }
    /**
     * Set MaxAddress value
     * @param unsignedInt $_maxAddress the MaxAddress
     * @return unsignedInt
     */
    public function setMaxAddress($_maxAddress)
    {
        return ($this->MaxAddress = $_maxAddress);
    }
    /**
     * Get MinAddress value
     * @return unsignedInt|null
     */
    public function getMinAddress()
    {
        return $this->MinAddress;
    }
    /**
     * Set MinAddress value
     * @param unsignedInt $_minAddress the MinAddress
     * @return unsignedInt
     */
    public function setMinAddress($_minAddress)
    {
        return ($this->MinAddress = $_minAddress);
    }
    /**
     * Get RoadClass value
     * @return unsignedByte|null
     */
    public function getRoadClass()
    {
        return $this->RoadClass;
    }
    /**
     * Set RoadClass value
     * @param unsignedByte $_roadClass the RoadClass
     * @return unsignedByte
     */
    public function setRoadClass($_roadClass)
    {
        return ($this->RoadClass = $_roadClass);
    }
    /**
     * Get RouteNum1 value
     * @return unsignedInt|null
     */
    public function getRouteNum1()
    {
        return $this->RouteNum1;
    }
    /**
     * Set RouteNum1 value
     * @param unsignedInt $_routeNum1 the RouteNum1
     * @return unsignedInt
     */
    public function setRouteNum1($_routeNum1)
    {
        return ($this->RouteNum1 = $_routeNum1);
    }
    /**
     * Get RouteNum2 value
     * @return unsignedInt|null
     */
    public function getRouteNum2()
    {
        return $this->RouteNum2;
    }
    /**
     * Set RouteNum2 value
     * @param unsignedInt $_routeNum2 the RouteNum2
     * @return unsignedInt
     */
    public function setRouteNum2($_routeNum2)
    {
        return ($this->RouteNum2 = $_routeNum2);
    }
    /**
     * Get RouteNum3 value
     * @return unsignedInt|null
     */
    public function getRouteNum3()
    {
        return $this->RouteNum3;
    }
    /**
     * Set RouteNum3 value
     * @param unsignedInt $_routeNum3 the RouteNum3
     * @return unsignedInt
     */
    public function setRouteNum3($_routeNum3)
    {
        return ($this->RouteNum3 = $_routeNum3);
    }
    /**
     * Get StreetName value
     * @return string|null
     */
    public function getStreetName()
    {
        return $this->StreetName;
    }
    /**
     * Set StreetName value
     * @param string $_streetName the StreetName
     * @return string
     */
    public function setStreetName($_streetName)
    {
        return ($this->StreetName = $_streetName);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAFExtraInfo
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
