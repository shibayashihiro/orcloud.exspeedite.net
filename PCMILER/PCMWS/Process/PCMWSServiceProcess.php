<?php
/**
 * File for class PCMWSServiceProcess
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
/**
 * This class stands for PCMWSServiceProcess originally named Process
 * @package PCMWS
 * @subpackage Services
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
class PCMWSServiceProcess extends PCMWSWsdlClass
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
     * Method to call the operation originally named ProcessGeocode
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructProcessGeocode $_pCMWSStructProcessGeocode
     * @return PCMWSStructProcessGeocodeResponse
     */
    public function ProcessGeocode(PCMWSStructProcessGeocode $_pCMWSStructProcessGeocode)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ProcessGeocode($_pCMWSStructProcessGeocode));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named ProcessReverseGeocode
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructProcessReverseGeocode $_pCMWSStructProcessReverseGeocode
     * @return PCMWSStructProcessReverseGeocodeResponse
     */
    public function ProcessReverseGeocode(PCMWSStructProcessReverseGeocode $_pCMWSStructProcessReverseGeocode)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ProcessReverseGeocode($_pCMWSStructProcessReverseGeocode));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named ProcessRadiusSearch
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructProcessRadiusSearch $_pCMWSStructProcessRadiusSearch
     * @return PCMWSStructProcessRadiusSearchResponse
     */
    public function ProcessRadiusSearch(PCMWSStructProcessRadiusSearch $_pCMWSStructProcessRadiusSearch)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ProcessRadiusSearch($_pCMWSStructProcessRadiusSearch));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named ProcessStates
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructProcessStates $_pCMWSStructProcessStates
     * @return PCMWSStructProcessStatesResponse
     */
    public function ProcessStates(PCMWSStructProcessStates $_pCMWSStructProcessStates)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ProcessStates($_pCMWSStructProcessStates));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Method to call the operation originally named ProcessMap
     * Meta informations extracted from the WSDL
     * - SOAPHeaderNames : AuthHeader
     * - SOAPHeaderNamespaces : http://www.alk.com
     * - SOAPHeaderTypes : {@link PCMWSStructAuthHeader}
     * - SOAPHeaders : required
     * @uses PCMWSWsdlClass::getSoapClient()
     * @uses PCMWSWsdlClass::setResult()
     * @uses PCMWSWsdlClass::saveLastError()
     * @param PCMWSStructProcessMap $_pCMWSStructProcessMap
     * @return PCMWSStructProcessMapResponse
     */
    public function ProcessMap(PCMWSStructProcessMap $_pCMWSStructProcessMap)
    {
        try
        {
            return $this->setResult(self::getSoapClient()->ProcessMap($_pCMWSStructProcessMap));
        }
        catch(SoapFault $soapFault)
        {
            return !$this->saveLastError(__METHOD__,$soapFault);
        }
    }
    /**
     * Returns the result
     * @see PCMWSWsdlClass::getResult()
     * @return PCMWSStructProcessGeocodeResponse|PCMWSStructProcessMapResponse|PCMWSStructProcessRadiusSearchResponse|PCMWSStructProcessReverseGeocodeResponse|PCMWSStructProcessStatesResponse
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
