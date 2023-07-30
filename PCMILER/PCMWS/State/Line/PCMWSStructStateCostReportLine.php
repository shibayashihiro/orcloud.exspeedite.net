<?php
/**
 * File for class PCMWSStructStateCostReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructStateCostReportLine originally named StateCostReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructStateCostReportLine extends PCMWSWsdlClass
{
    /**
     * The StCntry
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $StCntry;
    /**
     * The Total
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Total;
    /**
     * The Toll
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Toll;
    /**
     * The Free
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Free;
    /**
     * The Ferry
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Ferry;
    /**
     * The Loaded
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Loaded;
    /**
     * The Empty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Empty;
    /**
     * The Tolls
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Tolls;
    /**
     * The Energy
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Energy;
    /**
     * Constructor method for StateCostReportLine
     * @see parent::__construct()
     * @param string $_stCntry
     * @param string $_total
     * @param string $_toll
     * @param string $_free
     * @param string $_ferry
     * @param string $_loaded
     * @param string $_empty
     * @param string $_tolls
     * @param string $_energy
     * @return PCMWSStructStateCostReportLine
     */
    public function __construct($_stCntry = NULL,$_total = NULL,$_toll = NULL,$_free = NULL,$_ferry = NULL,$_loaded = NULL,$_empty = NULL,$_tolls = NULL,$_energy = NULL)
    {
        parent::__construct(array('StCntry'=>$_stCntry,'Total'=>$_total,'Toll'=>$_toll,'Free'=>$_free,'Ferry'=>$_ferry,'Loaded'=>$_loaded,'Empty'=>$_empty,'Tolls'=>$_tolls,'Energy'=>$_energy),false);
    }
    /**
     * Get StCntry value
     * @return string|null
     */
    public function getStCntry()
    {
        return $this->StCntry;
    }
    /**
     * Set StCntry value
     * @param string $_stCntry the StCntry
     * @return string
     */
    public function setStCntry($_stCntry)
    {
        return ($this->StCntry = $_stCntry);
    }
    /**
     * Get Total value
     * @return string|null
     */
    public function getTotal()
    {
        return $this->Total;
    }
    /**
     * Set Total value
     * @param string $_total the Total
     * @return string
     */
    public function setTotal($_total)
    {
        return ($this->Total = $_total);
    }
    /**
     * Get Toll value
     * @return string|null
     */
    public function getToll()
    {
        return $this->Toll;
    }
    /**
     * Set Toll value
     * @param string $_toll the Toll
     * @return string
     */
    public function setToll($_toll)
    {
        return ($this->Toll = $_toll);
    }
    /**
     * Get Free value
     * @return string|null
     */
    public function getFree()
    {
        return $this->Free;
    }
    /**
     * Set Free value
     * @param string $_free the Free
     * @return string
     */
    public function setFree($_free)
    {
        return ($this->Free = $_free);
    }
    /**
     * Get Ferry value
     * @return string|null
     */
    public function getFerry()
    {
        return $this->Ferry;
    }
    /**
     * Set Ferry value
     * @param string $_ferry the Ferry
     * @return string
     */
    public function setFerry($_ferry)
    {
        return ($this->Ferry = $_ferry);
    }
    /**
     * Get Loaded value
     * @return string|null
     */
    public function getLoaded()
    {
        return $this->Loaded;
    }
    /**
     * Set Loaded value
     * @param string $_loaded the Loaded
     * @return string
     */
    public function setLoaded($_loaded)
    {
        return ($this->Loaded = $_loaded);
    }
    /**
     * Get Empty value
     * @return string|null
     */
    public function getEmpty()
    {
        return $this->Empty;
    }
    /**
     * Set Empty value
     * @param string $_empty the Empty
     * @return string
     */
    public function setEmpty($_empty)
    {
        return ($this->Empty = $_empty);
    }
    /**
     * Get Tolls value
     * @return string|null
     */
    public function getTolls()
    {
        return $this->Tolls;
    }
    /**
     * Set Tolls value
     * @param string $_tolls the Tolls
     * @return string
     */
    public function setTolls($_tolls)
    {
        return ($this->Tolls = $_tolls);
    }
    /**
     * Get Energy value
     * @return string|null
     */
    public function getEnergy()
    {
        return $this->Energy;
    }
    /**
     * Set Energy value
     * @param string $_energy the Energy
     * @return string
     */
    public function setEnergy($_energy)
    {
        return ($this->Energy = $_energy);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructStateCostReportLine
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
