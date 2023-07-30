/*
------------------------------------------------------------------
Provider:  Zip-Codes.com
Product:   U.S. ZIP Code Database Standard
------------------------------------------------------------------
This SQL Creates a new table named ZIPCodes, 
related indexes, and extended column information.

This script is designed to work with MySQL

Actions:
  1.) Drop Table ZIPCodes if it exists
  2.) Creates Table named ZIPCodes
  3.) Creates Indexes on table ZIPCodes
  4.) Creates Extended Column Information

Last Updated: 07/07/2011
------------------------------------------------------------------
*/


/* 1.) Drop Table if it Exists */
DROP TABLE IF EXISTS ZIPCodes;



/* 2.) Create Table */
CREATE TABLE ZIPCodes (
	ZipCode char(5) NOT NULL,
	City varchar(35) NULL,
	State char(2),
	County varchar(45) NULL,
	AreaCode varchar(55) NULL,
	CityType char(1) NULL,
	CityAliasAbbreviation varchar(13) NULL,
	CityAliasName varchar(35) NULL,
	Latitude decimal(12, 6),
	Longitude decimal(12, 6),
	TimeZone char(2) NULL,
	Elevation int,
	CountyFIPS char(5) NULL,
	DayLightSaving char(1) NULL,
	PreferredLastLineKey varchar(10) NULL,
	ClassificationCode char(1) NULL,
	MultiCounty char(1) NULL,
	StateFIPS char(2) NULL,
	CityStateKey char(6) NULL,
	CityAliasCode varchar(5) NULL,
	PrimaryRecord char(1),
	CityMixedCase varchar(35) NULL,
	CityAliasMixedCase varchar(35) NULL,
	StateANSI varchar(2) NULL,
	CountyANSI varchar(3) NULL,
	FacilityCode varchar(1) NULL,
	CityDeliveryIndicator varchar(1) NULL,
	CarrierRouteRateSortation varchar(1) NULL,
	FinanceNumber varchar(6) NULL,
	UniqueZIPName varchar(1) NULL
);



/* 3.) Create Indexes on most searched fields */
CREATE INDEX Index_ZIPCodes_ZipCode					 ON ZIPCodes (ZipCode);
CREATE INDEX Index_ZIPCodes_State					 ON ZIPCodes (State);
CREATE INDEX Index_ZIPCodes_County					 ON ZIPCodes (County);
CREATE INDEX Index_ZIPCodes_AreaCode				 ON ZIPCodes (AreaCode);
CREATE INDEX Index_ZIPCodes_City					 ON ZIPCodes (City);
CREATE INDEX Index_ZIPCodes_Latitude				 ON ZIPCodes (Latitude);
CREATE INDEX Index_ZIPCodes_Longitude				 ON ZIPCodes (Longitude);
CREATE INDEX Index_ZIPCodes_CityAliasName			 ON ZIPCodes (CityAliasName);
CREATE INDEX Index_ZIPCodes_CityStateKey			 ON ZIPCodes (CityStateKey);



/* 4.) Create Extended Column Information */
ALTER TABLE ZIPCodes COMMENT = 'U.S. Zip Code Database – Standard (from www.zip-codes.com)';