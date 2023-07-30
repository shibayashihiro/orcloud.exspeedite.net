<?php
/**
 * File for class PCMWSStructRouteCosts
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSStructRouteCosts originally named RouteCosts
 * Meta informations extracted from the WSDL
 * - from schema : {@link http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?xsd=xsd0}
 * @package PCMWS
 * @subpackage Structs
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSStructRouteCosts extends PCMWSWsdlClass
{
    /**
     * The CostTimeEmpty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $CostTimeEmpty;
    /**
     * The CostTimeLoaded
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $CostTimeLoaded;
    /**
     * The FuelEconomyEmpty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $FuelEconomyEmpty;
    /**
     * The FuelEconomyLoaded
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $FuelEconomyLoaded;
    /**
     * The GreenHouseGas
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $GreenHouseGas;
    /**
     * The OtherCostPerDistUnitLoaded
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $OtherCostPerDistUnitLoaded;
    /**
     * The OtherCostPerDistanceUnitEmpty
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $OtherCostPerDistanceUnitEmpty;
    /**
     * The PricePerFuelUnit
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * - nillable : true
     * @var double
     */
    public $PricePerFuelUnit;
    /**
     * The TruckStyle
     * Meta informations extracted from the WSDL
     * - minOccurs : 0
     * @var PCMWSEnumTruckStyle
     */
    public $TruckStyle;
    /**
     * Constructor method for RouteCosts
     * @see parent::__construct()
     * @param double $_costTimeEmpty
     * @param double $_costTimeLoaded
     * @param double $_fuelEconomyEmpty
     * @param double $_fuelEconomyLoaded
     * @param double $_greenHouseGas
     * @param double $_otherCostPerDistUnitLoaded
     * @param double $_otherCostPerDistanceUnitEmpty
     * @param double $_pricePerFuelUnit
     * @param PCMWSEnumTruckStyle $_truckStyle
     * @return PCMWSStructRouteCosts
     */
    public function __construct($_costTimeEmpty = NULL,$_costTimeLoaded = NULL,$_fuelEconomyEmpty = NULL,$_fuelEconomyLoaded = NULL,$_greenHouseGas = NULL,$_otherCostPerDistUnitLoaded = NULL,$_otherCostPerDistanceUnitEmpty = NULL,$_pricePerFuelUnit = NULL,$_truckStyle = NULL)
    {
        parent::__construct(array('CostTimeEmpty'=>$_costTimeEmpty,'CostTimeLoaded'=>$_costTimeLoaded,'FuelEconomyEmpty'=>$_fuelEconomyEmpty,'FuelEconomyLoaded'=>$_fuelEconomyLoaded,'GreenHouseGas'=>$_greenHouseGas,'OtherCostPerDistUnitLoaded'=>$_otherCostPerDistUnitLoaded,'OtherCostPerDistanceUnitEmpty'=>$_otherCostPerDistanceUnitEmpty,'PricePerFuelUnit'=>$_pricePerFuelUnit,'TruckStyle'=>$_truckStyle),false);
    }
    /**
     * Get CostTimeEmpty value
     * @return double|null
     */
    public function getCostTimeEmpty()
    {
        return $this->CostTimeEmpty;
    }
    /**
     * Set CostTimeEmpty value
     * @param double $_costTimeEmpty the CostTimeEmpty
     * @return double
     */
    public function setCostTimeEmpty($_costTimeEmpty)
    {
        return ($this->CostTimeEmpty = $_costTimeEmpty);
    }
    /**
     * Get CostTimeLoaded value
     * @return double|null
     */
    public function getCostTimeLoaded()
    {
        return $this->CostTimeLoaded;
    }
    /**
     * Set CostTimeLoaded value
     * @param double $_costTimeLoaded the CostTimeLoaded
     * @return double
     */
    public function setCostTimeLoaded($_costTimeLoaded)
    {
        return ($this->CostTimeLoaded = $_costTimeLoaded);
    }
    /**
     * Get FuelEconomyEmpty value
     * @return double|null
     */
    public function getFuelEconomyEmpty()
    {
        return $this->FuelEconomyEmpty;
    }
    /**
     * Set FuelEconomyEmpty value
     * @param double $_fuelEconomyEmpty the FuelEconomyEmpty
     * @return double
     */
    public function setFuelEconomyEmpty($_fuelEconomyEmpty)
    {
        return ($this->FuelEconomyEmpty = $_fuelEconomyEmpty);
    }
    /**
     * Get FuelEconomyLoaded value
     * @return double|null
     */
    public function getFuelEconomyLoaded()
    {
        return $this->FuelEconomyLoaded;
    }
    /**
     * Set FuelEconomyLoaded value
     * @param double $_fuelEconomyLoaded the FuelEconomyLoaded
     * @return double
     */
    public function setFuelEconomyLoaded($_fuelEconomyLoaded)
    {
        return ($this->FuelEconomyLoaded = $_fuelEconomyLoaded);
    }
    /**
     * Get GreenHouseGas value
     * @return double|null
     */
    public function getGreenHouseGas()
    {
        return $this->GreenHouseGas;
    }
    /**
     * Set GreenHouseGas value
     * @param double $_greenHouseGas the GreenHouseGas
     * @return double
     */
    public function setGreenHouseGas($_greenHouseGas)
    {
        return ($this->GreenHouseGas = $_greenHouseGas);
    }
    /**
     * Get OtherCostPerDistUnitLoaded value
     * @return double|null
     */
    public function getOtherCostPerDistUnitLoaded()
    {
        return $this->OtherCostPerDistUnitLoaded;
    }
    /**
     * Set OtherCostPerDistUnitLoaded value
     * @param double $_otherCostPerDistUnitLoaded the OtherCostPerDistUnitLoaded
     * @return double
     */
    public function setOtherCostPerDistUnitLoaded($_otherCostPerDistUnitLoaded)
    {
        return ($this->OtherCostPerDistUnitLoaded = $_otherCostPerDistUnitLoaded);
    }
    /**
     * Get OtherCostPerDistanceUnitEmpty value
     * @return double|null
     */
    public function getOtherCostPerDistanceUnitEmpty()
    {
        return $this->OtherCostPerDistanceUnitEmpty;
    }
    /**
     * Set OtherCostPerDistanceUnitEmpty value
     * @param double $_otherCostPerDistanceUnitEmpty the OtherCostPerDistanceUnitEmpty
     * @return double
     */
    public function setOtherCostPerDistanceUnitEmpty($_otherCostPerDistanceUnitEmpty)
    {
        return ($this->OtherCostPerDistanceUnitEmpty = $_otherCostPerDistanceUnitEmpty);
    }
    /**
     * Get PricePerFuelUnit value
     * @return double|null
     */
    public function getPricePerFuelUnit()
    {
        return $this->PricePerFuelUnit;
    }
    /**
     * Set PricePerFuelUnit value
     * @param double $_pricePerFuelUnit the PricePerFuelUnit
     * @return double
     */
    public function setPricePerFuelUnit($_pricePerFuelUnit)
    {
        return ($this->PricePerFuelUnit = $_pricePerFuelUnit);
    }
    /**
     * Get TruckStyle value
     * @return PCMWSEnumTruckStyle|null
     */
    public function getTruckStyle()
    {
        return $this->TruckStyle;
    }
    /**
     * Set TruckStyle value
     * @uses PCMWSEnumTruckStyle::valueIsValid()
     * @param PCMWSEnumTruckStyle $_truckStyle the TruckStyle
     * @return PCMWSEnumTruckStyle
     */
    public function setTruckStyle($_truckStyle)
    {
        if(!PCMWSEnumTruckStyle::valueIsValid($_truckStyle))
        {
            return false;
        }
        return ($this->TruckStyle = $_truckStyle);
    }
    /**
     * Method called when an object has been exported with var_export() functions
     * It allows to return an object instantiated with the values
     * @see PCMWSWsdlClass::__set_state()
     * @uses PCMWSWsdlClass::__set_state()
     * @param array $_array the exported values
     * @return PCMWSStructRouteCosts
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
