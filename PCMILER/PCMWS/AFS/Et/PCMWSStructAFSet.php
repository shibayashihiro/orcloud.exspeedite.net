<?php
/**
 * File for class PCMWSStructAFSet
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructAFSet originally named AFSet
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructAFSet extends PCMWSWsdlClass
{
    /**
     * The DataVersion
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $DataVersion;
    /**
     * The Links
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var PCMWSStructArrayOfAFLink
     */
    public $Links;
    /**
     * The Name
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Name;
    /**
     * The SetID
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var int
     */
    public $SetID;
    /**
     * The Tag
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Tag;
    /**
     * Constructor method for AFSet
     * @see parent::__construct()
     * @param string $_dataVersion
     * @param PCMWSStructArrayOfAFLink $_links
     * @param string $_name
     * @param int $_setID
     * @param string $_tag
     * @return PCMWSStructAFSet
     */
    public function __construct($_dataVersion = NULL,$_links = NULL,$_name = NULL,$_setID = NULL,$_tag = NULL)
    {
        parent::__construct(array('DataVersion'=>$_dataVersion,'Links'=>($_links instanceof PCMWSStructArrayOfAFLink)?$_links:new PCMWSStructArrayOfAFLink($_links),'Name'=>$_name,'SetID'=>$_setID,'Tag'=>$_tag),false);
    }
    /**
     * Get DataVersion value
     * @return string|null
     */
    public function getDataVersion()
    {
        return $this->DataVersion;
    }
    /**
     * Set DataVersion value
     * @param string $_dataVersion the DataVersion
     * @return string
     */
    public function setDataVersion($_dataVersion)
    {
        return ($this->DataVersion = $_dataVersion);
    }
    /**
     * Get Links value
     * @return PCMWSStructArrayOfAFLink|null
     */
    public function getLinks()
    {
        return $this->Links;
    }
    /**
     * Set Links value
     * @param PCMWSStructArrayOfAFLink $_links the Links
     * @return PCMWSStructArrayOfAFLink
     */
    public function setLinks($_links)
    {
        return ($this->Links = $_links);
    }
    /**
     * Get Name value
     * @return string|null
     */
    public function getName()
    {
        return $this->Name;
    }
    /**
     * Set Name value
     * @param string $_name the Name
     * @return string
     */
    public function setName($_name)
    {
        return ($this->Name = $_name);
    }
    /**
     * Get SetID value
     * @return int|null
     */
    public function getSetID()
    {
        return $this->SetID;
    }
    /**
     * Set SetID value
     * @param int $_setID the SetID
     * @return int
     */
    public function setSetID($_setID)
    {
        return ($this->SetID = $_setID);
    }
    /**
     * Get Tag value
     * @return string|null
     */
    public function getTag()
    {
        return $this->Tag;
    }
    /**
     * Set Tag value
     * @param string $_tag the Tag
     * @return string
     */
    public function setTag($_tag)
    {
        return ($this->Tag = $_tag);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructAFSet
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
