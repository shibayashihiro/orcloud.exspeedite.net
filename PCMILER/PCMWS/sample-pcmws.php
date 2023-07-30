<?php
/**
 * Test with PCMWS for 'http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?Wsdl'
 * @package PCMWS
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 * @version 20150429-01
 * @date 2016-08-25
 */
ini_set('memory_limit','512M');
ini_set('display_errors',true);
error_reporting(-1);
/**
 * Load autoload
 */
require_once dirname(__FILE__) . '/PCMWSAutoload.php';
/**
 * Wsdl instanciation infos. By default, nothing has to be set.
 * If you wish to override the SoapClient's options, please refer to the sample below.
 * 
 * This is an associative array as:
 * - the key must be a PCMWSWsdlClass constant beginning with WSDL_
 * - the value must be the corresponding key value
 * Each option matches the {@link http://www.php.net/manual/en/soapclient.soapclient.php} options
 * 
 * Here is below an example of how you can set the array:
 * $wsdl = array();
 * $wsdl[PCMWSWsdlClass::WSDL_URL] = 'http://pcmiler.alk.com/APIs/SOAP/v1.0/Service.svc?Wsdl';
 * $wsdl[PCMWSWsdlClass::WSDL_CACHE_WSDL] = WSDL_CACHE_NONE;
 * $wsdl[PCMWSWsdlClass::WSDL_TRACE] = true;
 * $wsdl[PCMWSWsdlClass::WSDL_LOGIN] = 'myLogin';
 * $wsdl[PCMWSWsdlClass::WSDL_PASSWD] = '**********';
 * etc....
 * Then instantiate the Service class as: 
 * - $wsdlObject = new PCMWSWsdlClass($wsdl);
 */
/**
 * Examples
 */


/*******************************
 * Example for PCMWSServiceAbout
 */
$pCMWSServiceAbout = new PCMWSServiceAbout();
// sample call for PCMWSServiceAbout::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceAbout->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceAbout::AboutService()
if($pCMWSServiceAbout->AboutService(new PCMWSStructAboutService(/*** update parameters list ***/)))
    print_r($pCMWSServiceAbout->getResult());
else
    print_r($pCMWSServiceAbout->getLastError());

/*********************************
 * Example for PCMWSServiceProcess
 */
$pCMWSServiceProcess = new PCMWSServiceProcess();
// sample call for PCMWSServiceProcess::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceProcess->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceProcess::ProcessGeocode()
if($pCMWSServiceProcess->ProcessGeocode(new PCMWSStructProcessGeocode(/*** update parameters list ***/)))
    print_r($pCMWSServiceProcess->getResult());
else
    print_r($pCMWSServiceProcess->getLastError());
// sample call for PCMWSServiceProcess::ProcessReverseGeocode()
if($pCMWSServiceProcess->ProcessReverseGeocode(new PCMWSStructProcessReverseGeocode(/*** update parameters list ***/)))
    print_r($pCMWSServiceProcess->getResult());
else
    print_r($pCMWSServiceProcess->getLastError());
// sample call for PCMWSServiceProcess::ProcessRadiusSearch()
if($pCMWSServiceProcess->ProcessRadiusSearch(new PCMWSStructProcessRadiusSearch(/*** update parameters list ***/)))
    print_r($pCMWSServiceProcess->getResult());
else
    print_r($pCMWSServiceProcess->getLastError());
// sample call for PCMWSServiceProcess::ProcessStates()
if($pCMWSServiceProcess->ProcessStates(new PCMWSStructProcessStates(/*** update parameters list ***/)))
    print_r($pCMWSServiceProcess->getResult());
else
    print_r($pCMWSServiceProcess->getLastError());
// sample call for PCMWSServiceProcess::ProcessMap()
if($pCMWSServiceProcess->ProcessMap(new PCMWSStructProcessMap(/*** update parameters list ***/)))
    print_r($pCMWSServiceProcess->getResult());
else
    print_r($pCMWSServiceProcess->getLastError());

/*****************************
 * Example for PCMWSServiceGet
 */
$pCMWSServiceGet = new PCMWSServiceGet();
// sample call for PCMWSServiceGet::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceGet->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceGet::GetAvoidFavor()
if($pCMWSServiceGet->GetAvoidFavor(new PCMWSStructGetAvoidFavor(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetCustomPlaces()
if($pCMWSServiceGet->GetCustomPlaces(new PCMWSStructGetCustomPlaces(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetRoadSpeeds()
if($pCMWSServiceGet->GetRoadSpeeds(new PCMWSStructGetRoadSpeeds(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetETAOutOfRouteReport()
if($pCMWSServiceGet->GetETAOutOfRouteReport(new PCMWSStructGetETAOutOfRouteReport(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetReports()
if($pCMWSServiceGet->GetReports(new PCMWSStructGetReports(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetPoisAlongRoute()
if($pCMWSServiceGet->GetPoisAlongRoute(new PCMWSStructGetPoisAlongRoute(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetAvoidFavorSets()
if($pCMWSServiceGet->GetAvoidFavorSets(new PCMWSStructGetAvoidFavorSets(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetWeatherAlerts()
if($pCMWSServiceGet->GetWeatherAlerts(new PCMWSStructGetWeatherAlerts(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());
// sample call for PCMWSServiceGet::GetRouteMatrix()
if($pCMWSServiceGet->GetRouteMatrix(new PCMWSStructGetRouteMatrix(/*** update parameters list ***/)))
    print_r($pCMWSServiceGet->getResult());
else
    print_r($pCMWSServiceGet->getLastError());

/*****************************
 * Example for PCMWSServiceSet
 */
$pCMWSServiceSet = new PCMWSServiceSet();
// sample call for PCMWSServiceSet::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceSet->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceSet::SetAvoidFavor()
if($pCMWSServiceSet->SetAvoidFavor(new PCMWSStructSetAvoidFavor(/*** update parameters list ***/)))
    print_r($pCMWSServiceSet->getResult());
else
    print_r($pCMWSServiceSet->getLastError());
// sample call for PCMWSServiceSet::SetCustomPlaces()
if($pCMWSServiceSet->SetCustomPlaces(new PCMWSStructSetCustomPlaces(/*** update parameters list ***/)))
    print_r($pCMWSServiceSet->getResult());
else
    print_r($pCMWSServiceSet->getLastError());
// sample call for PCMWSServiceSet::SetRoadSpeeds()
if($pCMWSServiceSet->SetRoadSpeeds(new PCMWSStructSetRoadSpeeds(/*** update parameters list ***/)))
    print_r($pCMWSServiceSet->getResult());
else
    print_r($pCMWSServiceSet->getLastError());

/********************************
 * Example for PCMWSServiceReduce
 */
$pCMWSServiceReduce = new PCMWSServiceReduce();
// sample call for PCMWSServiceReduce::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceReduce->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceReduce::ReduceTrip()
if($pCMWSServiceReduce->ReduceTrip(new PCMWSStructReduceTrip(/*** update parameters list ***/)))
    print_r($pCMWSServiceReduce->getResult());
else
    print_r($pCMWSServiceReduce->getLastError());

/********************************
 * Example for PCMWSServiceCreate
 */
$pCMWSServiceCreate = new PCMWSServiceCreate();
// sample call for PCMWSServiceCreate::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceCreate->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceCreate::CreateRouteSyncMessage()
if($pCMWSServiceCreate->CreateRouteSyncMessage(new PCMWSStructCreateRouteSyncMessage(/*** update parameters list ***/)))
    print_r($pCMWSServiceCreate->getResult());
else
    print_r($pCMWSServiceCreate->getLastError());

/**********************************
 * Example for PCMWSServiceGenerate
 */
$pCMWSServiceGenerate = new PCMWSServiceGenerate();
// sample call for PCMWSServiceGenerate::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceGenerate->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceGenerate::GenerateDriveTimePolygon()
if($pCMWSServiceGenerate->GenerateDriveTimePolygon(new PCMWSStructGenerateDriveTimePolygon(/*** update parameters list ***/)))
    print_r($pCMWSServiceGenerate->getResult());
else
    print_r($pCMWSServiceGenerate->getLastError());

/********************************
 * Example for PCMWSServiceImport
 */
$pCMWSServiceImport = new PCMWSServiceImport();
// sample call for PCMWSServiceImport::setSoapHeaderAuthHeader() in order to initialize required SoapHeader
$pCMWSServiceImport->setSoapHeaderAuthHeader(new PCMWSStructAuthHeader(/*** update parameters list ***/));
// sample call for PCMWSServiceImport::ImportAvoidFavorSet()
if($pCMWSServiceImport->ImportAvoidFavorSet(new PCMWSStructImportAvoidFavorSet(/*** update parameters list ***/)))
    print_r($pCMWSServiceImport->getResult());
else
    print_r($pCMWSServiceImport->getLastError());
