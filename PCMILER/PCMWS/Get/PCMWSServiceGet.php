<?php
/**
 * File for class PCMWSServiceGet
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSServiceGet originally named Get
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSServiceGet extends PCMWSWsdlClass
{
    /**
     * Sets the AuthHeader SoapHeader param
     * @uses PCMWSWsdlClass::setSoapHeader()
     * @param PCMWSStructAuthHeader $_pCMWSStructAuthHeader
     * @param string $_nameSpace http://www.alk.com
     * @param bool $_mustUnderstand
     * @param string $_actor
     * @return bool true|false
     */
    public function setSoapHeaderAuthHeader(PCMWSStructAuthHeader $_pCMWSStructAuthHeader,$_nameSpace = 'http://www.alk.com',$_mustUnderstand = false,$_actor = null)
    {
        return $this->setSoapHeader($_nameSpace,'AuthHeader',$_pCMWSStructAuthHeader,$_mustUnderstand,$_actor);
    }
    /**
     * Method to call the operation originally named GetAvoidFavor
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetAvoidFavor $_pCMWSStructGetAvoidFavor
     * @return PCMWSStructGetAvoidFavorResponse
     */
    public function GetAvoidFavor(PCMWSStructGetAvoidFavor $_pCMWSStructGetAvoidFavor)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetAvoidFavor($_pCMWSStructGetAvoidFavor));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetCustomPlaces
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetCustomPlaces $_pCMWSStructGetCustomPlaces
     * @return PCMWSStructGetCustomPlacesResponse
     */
    public function GetCustomPlaces(PCMWSStructGetCustomPlaces $_pCMWSStructGetCustomPlaces)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetCustomPlaces($_pCMWSStructGetCustomPlaces));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetRoadSpeeds
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetRoadSpeeds $_pCMWSStructGetRoadSpeeds
     * @return PCMWSStructGetRoadSpeedsResponse
     */
    public function GetRoadSpeeds(PCMWSStructGetRoadSpeeds $_pCMWSStructGetRoadSpeeds)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetRoadSpeeds($_pCMWSStructGetRoadSpeeds));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetETAOutOfRouteReport
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetETAOutOfRouteReport $_pCMWSStructGetETAOutOfRouteReport
     * @return PCMWSStructGetETAOutOfRouteReportResponse
     */
    public function GetETAOutOfRouteReport(PCMWSStructGetETAOutOfRouteReport $_pCMWSStructGetETAOutOfRouteReport)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetETAOutOfRouteReport($_pCMWSStructGetETAOutOfRouteReport));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetReports
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetReports $_pCMWSStructGetReports
     * @return PCMWSStructGetReportsResponse
     */
    public function GetReports(PCMWSStructGetReports $_pCMWSStructGetReports)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetReports($_pCMWSStructGetReports));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetPoisAlongRoute
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetPoisAlongRoute $_pCMWSStructGetPoisAlongRoute
     * @return PCMWSStructGetPoisAlongRouteResponse
     */
    public function GetPoisAlongRoute(PCMWSStructGetPoisAlongRoute $_pCMWSStructGetPoisAlongRoute)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetPoisAlongRoute($_pCMWSStructGetPoisAlongRoute));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetAvoidFavorSets
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetAvoidFavorSets $_pCMWSStructGetAvoidFavorSets
     * @return PCMWSStructGetAvoidFavorSetsResponse
     */
    public function GetAvoidFavorSets(PCMWSStructGetAvoidFavorSets $_pCMWSStructGetAvoidFavorSets)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetAvoidFavorSets($_pCMWSStructGetAvoidFavorSets));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetWeatherAlerts
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetWeatherAlerts $_pCMWSStructGetWeatherAlerts
     * @return PCMWSStructGetWeatherAlertsResponse
     */
    public function GetWeatherAlerts(PCMWSStructGetWeatherAlerts $_pCMWSStructGetWeatherAlerts)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetWeatherAlerts($_pCMWSStructGetWeatherAlerts));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named GetRouteMatrix
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructGetRouteMatrix $_pCMWSStructGetRouteMatrix
     * @return PCMWSStructGetRouteMatrixResponse
     */
    public function GetRouteMatrix(PCMWSStructGetRouteMatrix $_pCMWSStructGetRouteMatrix)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->GetRouteMatrix($_pCMWSStructGetRouteMatrix));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Returns the result
     * @see PCMWSWsdlClass::getResult()
     * @return PCMWSStructGetAvoidFavorResponse|PCMWSStructGetAvoidFavorSetsResponse|PCMWSStructGetCustomPlacesResponse|PCMWSStructGetETAOutOfRouteReportResponse|PCMWSStructGetPoisAlongRouteResponse|PCMWSStructGetReportsResponse|PCMWSStructGetRoadSpeedsResponse|PCMWSStructGetRouteMatrixResponse|PCMWSStructGetWeatherAlertsResponse
     */
    public function getResult()
    {
        return parent::getResult();
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
