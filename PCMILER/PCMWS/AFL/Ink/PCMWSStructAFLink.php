<?php
/**
 * File for class PCMWSStructAFLink
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAFLink originally named AFLink
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAFLink extends PCMWSWsdlClass
{
    /**
     * The AvoidFavorID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $AvoidFavorID;
    /**
     * The AvoidFavorType
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumAFType
     */
    public $AvoidFavorType;
    /**
     * The Comment
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Comment;
    /**
     * The ExtraInfo
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructAFExtraInfo
     */
    public $ExtraInfo;
    /**
     * The Geometries
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfArrayOfdouble
     */
    public $Geometries;
    /**
     * The LinkPoints
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfArrayOfint
     */
    public $LinkPoints;
    /**
     * Constructor method for AFLink
     * @see parent::__construct()
     * @param int $_avoidFavorID
     * @param PCMWSEnumAFType $_avoidFavorType
     * @param string $_comment
     * @param PCMWSStructAFExtraInfo $_extraInfo
     * @param PCMWSStructArrayOfArrayOfArrayOfdouble $_geometries
     * @param PCMWSStructArrayOfArrayOfint $_linkPoints
     * @return PCMWSStructAFLink
     */
    public function __construct($_avoidFavorID = NULL,$_avoidFavorType = NULL,$_comment = NULL,$_extraInfo = NULL,$_geometries = NULL,$_linkPoints = NULL)
    {
        parent::__construct(array('AvoidFavorID'=>$_avoidFavorID,'AvoidFavorType'=>$_avoidFavorType,'Comment'=>$_comment,'ExtraInfo'=>$_extraInfo,'Geometries'=>($_geometries instanceof PCMWSStructArrayOfArrayOfArrayOfdouble)?$_geometries:new PCMWSStructArrayOfArrayOfArrayOfdouble($_geometries),'LinkPoints'=>($_linkPoints instanceof PCMWSStructArrayOfArrayOfint)?$_linkPoints:new PCMWSStructArrayOfArrayOfint($_linkPoints)),false);
    }
    /**
     * Get AvoidFavorID value
     * @return int|null
     */
    public function getAvoidFavorID()
    {
        return $this->AvoidFavorID;
    }
    /**
     * Set AvoidFavorID value
     * @param int $_avoidFavorID the AvoidFavorID
     * @return int
     */
    public function setAvoidFavorID($_avoidFavorID)
    {
        return ($this->AvoidFavorID = $_avoidFavorID);
    }
    /**
     * Get AvoidFavorType value
     * @return PCMWSEnumAFType|null
     */
    public function getAvoidFavorType()
    {
        return $this->AvoidFavorType;
    }
    /**
     * Set AvoidFavorType value
     * @uses PCMWSEnumAFType::valueIsValid()
     * @param PCMWSEnumAFType $_avoidFavorType the AvoidFavorType
     * @return PCMWSEnumAFType
     */
    public function setAvoidFavorType($_avoidFavorType)
    {
        if(!PCMWSEnumAFType::valueIsValid($_avoidFavorType))
        {
            return false;
        }
        return ($this->AvoidFavorType = $_avoidFavorType);
    }
    /**
     * Get Comment value
     * @return string|null
     */
    public function getComment()
    {
        return $this->Comment;
    }
    /**
     * Set Comment value
     * @param string $_comment the Comment
     * @return string
     */
    public function setComment($_comment)
    {
        return ($this->Comment = $_comment);
    }
    /**
     * Get ExtraInfo value
     * @return PCMWSStructAFExtraInfo|null
     */
    public function getExtraInfo()
    {
        return $this->ExtraInfo;
    }
    /**
     * Set ExtraInfo value
     * @param PCMWSStructAFExtraInfo $_extraInfo the ExtraInfo
     * @return PCMWSStructAFExtraInfo
     */
    public function setExtraInfo($_extraInfo)
    {
        return ($this->ExtraInfo = $_extraInfo);
    }
    /**
     * Get Geometries value
     * @return PCMWSStructArrayOfArrayOfArrayOfdouble|null
     */
    public function getGeometries()
    {
        return $this->Geometries;
    }
    /**
     * Set Geometries value
     * @param PCMWSStructArrayOfArrayOfArrayOfdouble $_geometries the Geometries
     * @return PCMWSStructArrayOfArrayOfArrayOfdouble
     */
    public function setGeometries($_geometries)
    {
        return ($this->Geometries = $_geometries);
    }
    /**
     * Get LinkPoints value
     * @return PCMWSStructArrayOfArrayOfint|null
     */
    public function getLinkPoints()
    {
        return $this->LinkPoints;
    }
    /**
     * Set LinkPoints value
     * @param PCMWSStructArrayOfArrayOfint $_linkPoints the LinkPoints
     * @return PCMWSStructArrayOfArrayOfint
     */
    public function setLinkPoints($_linkPoints)
    {
        return ($this->LinkPoints = $_linkPoints);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAFLink
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
