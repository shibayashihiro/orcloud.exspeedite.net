<?php
/**
 * File for class PCMWSStructComparisonReportLine
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructComparisonReportLine originally named ComparisonReportLine
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructComparisonReportLine extends PCMWSWsdlClass
{
    /**
     * The LeastCostTripOptions
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $LeastCostTripOptions;
    /**
     * The Route
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Route;
    /**
     * The Miles
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Miles;
    /**
     * The Cost
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Cost;
    /**
     * The Hours
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Hours;
    /**
     * The Tolls
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Tolls;
    /**
     * The Labor
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Labor;
    /**
     * The Other
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Other;
    /**
     * The Estghg
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Estghg;
    /**
     * The Fuel
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var string
     */
    public $Fuel;
    /**
     * Constructor method for ComparisonReportLine
     * @see parent::__construct()
     * @param string $_leastCostTripOptions
     * @param string $_route
     * @param string $_miles
     * @param string $_cost
     * @param string $_hours
     * @param string $_tolls
     * @param string $_labor
     * @param string $_other
     * @param string $_estghg
     * @param string $_fuel
     * @return PCMWSStructComparisonReportLine
     */
    public function __construct($_leastCostTripOptions = NULL,$_route = NULL,$_miles = NULL,$_cost = NULL,$_hours = NULL,$_tolls = NULL,$_labor = NULL,$_other = NULL,$_estghg = NULL,$_fuel = NULL)
    {
        parent::__construct(array('LeastCostTripOptions'=>$_leastCostTripOptions,'Route'=>$_route,'Miles'=>$_miles,'Cost'=>$_cost,'Hours'=>$_hours,'Tolls'=>$_tolls,'Labor'=>$_labor,'Other'=>$_other,'Estghg'=>$_estghg,'Fuel'=>$_fuel),false);
    }
    /**
     * Get LeastCostTripOptions value
     * @return string|null
     */
    public function getLeastCostTripOptions()
    {
        return $this->LeastCostTripOptions;
    }
    /**
     * Set LeastCostTripOptions value
     * @param string $_leastCostTripOptions the LeastCostTripOptions
     * @return string
     */
    public function setLeastCostTripOptions($_leastCostTripOptions)
    {
        return ($this->LeastCostTripOptions = $_leastCostTripOptions);
    }
    /**
     * Get Route value
     * @return string|null
     */
    public function getRoute()
    {
        return $this->Route;
    }
    /**
     * Set Route value
     * @param string $_route the Route
     * @return string
     */
    public function setRoute($_route)
    {
        return ($this->Route = $_route);
    }
    /**
     * Get Miles value
     * @return string|null
     */
    public function getMiles()
    {
        return $this->Miles;
    }
    /**
     * Set Miles value
     * @param string $_miles the Miles
     * @return string
     */
    public function setMiles($_miles)
    {
        return ($this->Miles = $_miles);
    }
    /**
     * Get Cost value
     * @return string|null
     */
    public function getCost()
    {
        return $this->Cost;
    }
    /**
     * Set Cost value
     * @param string $_cost the Cost
     * @return string
     */
    public function setCost($_cost)
    {
        return ($this->Cost = $_cost);
    }
    /**
     * Get Hours value
     * @return string|null
     */
    public function getHours()
    {
        return $this->Hours;
    }
    /**
     * Set Hours value
     * @param string $_hours the Hours
     * @return string
     */
    public function setHours($_hours)
    {
        return ($this->Hours = $_hours);
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
     * Get Labor value
     * @return string|null
     */
    public function getLabor()
    {
        return $this->Labor;
    }
    /**
     * Set Labor value
     * @param string $_labor the Labor
     * @return string
     */
    public function setLabor($_labor)
    {
        return ($this->Labor = $_labor);
    }
    /**
     * Get Other value
     * @return string|null
     */
    public function getOther()
    {
        return $this->Other;
    }
    /**
     * Set Other value
     * @param string $_other the Other
     * @return string
     */
    public function setOther($_other)
    {
        return ($this->Other = $_other);
    }
    /**
     * Get Estghg value
     * @return string|null
     */
    public function getEstghg()
    {
        return $this->Estghg;
    }
    /**
     * Set Estghg value
     * @param string $_estghg the Estghg
     * @return string
     */
    public function setEstghg($_estghg)
    {
        return ($this->Estghg = $_estghg);
    }
    /**
     * Get Fuel value
     * @return string|null
     */
    public function getFuel()
    {
        return $this->Fuel;
    }
    /**
     * Set Fuel value
     * @param string $_fuel the Fuel
     * @return string
     */
    public function setFuel($_fuel)
    {
        return ($this->Fuel = $_fuel);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructComparisonReportLine
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
