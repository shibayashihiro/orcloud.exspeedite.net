<?php
/**
 * File for class PCMWSStructGeofenceIntersectRequestBody
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructGeofenceIntersectRequestBody originally named GeofenceIntersectRequestBody
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructGeofenceIntersectRequestBody extends PCMWSWsdlClass
{
    /**
     * The fenceNames
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfstring
     */
    public $fenceNames;
    /**
     * The setIds
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfint
     */
    public $setIds;
    /**
     * The setNames
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfstring
     */
    public $setNames;
    /**
     * The setTags
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfstring
     */
    public $setTags;
    /**
     * Constructor method for GeofenceIntersectRequestBody
     * @see parent::__construct()
     * @param PCMWSStructArrayOfstring $_fenceNames
     * @param PCMWSStructArrayOfint $_setIds
     * @param PCMWSStructArrayOfstring $_setNames
     * @param PCMWSStructArrayOfstring $_setTags
     * @return PCMWSStructGeofenceIntersectRequestBody
     */
    public function __construct($_fenceNames = NULL,$_setIds = NULL,$_setNames = NULL,$_setTags = NULL)
    {
        parent::__construct(array('fenceNames'=>($_fenceNames instanceof PCMWSStructArrayOfstring)?$_fenceNames:new PCMWSStructArrayOfstring($_fenceNames),'setIds'=>($_setIds instanceof PCMWSStructArrayOfint)?$_setIds:new PCMWSStructArrayOfint($_setIds),'setNames'=>($_setNames instanceof PCMWSStructArrayOfstring)?$_setNames:new PCMWSStructArrayOfstring($_setNames),'setTags'=>($_setTags instanceof PCMWSStructArrayOfstring)?$_setTags:new PCMWSStructArrayOfstring($_setTags)),false);
    }
    /**
     * Get fenceNames value
     * @return PCMWSStructArrayOfstring|null
     */
    public function getFenceNames()
    {
        return $this->fenceNames;
    }
    /**
     * Set fenceNames value
     * @param PCMWSStructArrayOfstring $_fenceNames the fenceNames
     * @return PCMWSStructArrayOfstring
     */
    public function setFenceNames($_fenceNames)
    {
        return ($this->fenceNames = $_fenceNames);
    }
    /**
     * Get setIds value
     * @return PCMWSStructArrayOfint|null
     */
    public function getSetIds()
    {
        return $this->setIds;
    }
    /**
     * Set setIds value
     * @param PCMWSStructArrayOfint $_setIds the setIds
     * @return PCMWSStructArrayOfint
     */
    public function setSetIds($_setIds)
    {
        return ($this->setIds = $_setIds);
    }
    /**
     * Get setNames value
     * @return PCMWSStructArrayOfstring|null
     */
    public function getSetNames()
    {
        return $this->setNames;
    }
    /**
     * Set setNames value
     * @param PCMWSStructArrayOfstring $_setNames the setNames
     * @return PCMWSStructArrayOfstring
     */
    public function setSetNames($_setNames)
    {
        return ($this->setNames = $_setNames);
    }
    /**
     * Get setTags value
     * @return PCMWSStructArrayOfstring|null
     */
    public function getSetTags()
    {
        return $this->setTags;
    }
    /**
     * Set setTags value
     * @param PCMWSStructArrayOfstring $_setTags the setTags
     * @return PCMWSStructArrayOfstring
     */
    public function setSetTags($_setTags)
    {
        return ($this->setTags = $_setTags);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructGeofenceIntersectRequestBody
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
