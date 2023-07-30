-- Exspeedite 1.0 Clean up patch

-- Select which DB to work on.
USE forl1327_exspeed;

-- Leave these alone, their content is important to Exspeedite
-- exp_setting
-- exp_states
-- exp_status_codes
-- exp_un_number
-- exp_unit
-- zipcodes
-- exp_report


-- Various. Order could be important due to dependancies.

LOCK TABLES exp_detail WRITE, exp_image WRITE, exp_load WRITE,
	exp_osd WRITE, exp_pallet WRITE, exp_shipment WRITE,
	exp_status WRITE, exp_stop WRITE, exp_vacation WRITE,
	exp_zone_filter WRITE;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE exp_shipment;
TRUNCATE TABLE exp_stop;
TRUNCATE TABLE exp_detail;
TRUNCATE TABLE exp_load;
TRUNCATE TABLE exp_image;
TRUNCATE TABLE exp_status;
TRUNCATE TABLE exp_osd;
TRUNCATE TABLE exp_pallet;
TRUNCATE TABLE exp_vacation;
TRUNCATE TABLE exp_zone_filter;
SET FOREIGN_KEY_CHECKS = 1;

UNLOCK TABLES;

-- Mona's tables

LOCK TABLES exp_client_assign_rate WRITE, exp_client_billing WRITE, exp_client_cat_rate_master WRITE,
	exp_client_fright_rate WRITE, exp_client_fsc WRITE, exp_client_handling_charges WRITE,
	exp_client_rate_master WRITE, exp_detention_hours_rate WRITE, exp_detention_master WRITE,
	exp_driver_assign_rates WRITE, exp_driver_hours WRITE, exp_driver_manual_rates WRITE,
	exp_driver_pay_master WRITE, exp_driver_range_rates WRITE, exp_driver_rates WRITE,
	exp_fsc_history WRITE, exp_fsc_schedule WRITE, exp_handling WRITE, exp_ifta_log WRITE,
	exp_ifta_rate WRITE, exp_load_manual_rate WRITE, exp_load_pay_master WRITE,
	exp_load_pay_rate WRITE, exp_load_range_rate WRITE, exp_man_code WRITE,
	exp_pallet_master WRITE, exp_pallet_rate_master WRITE, exp_profile_manual WRITE,
	exp_profile_manual_rates WRITE, exp_profile_master WRITE, exp_profile_range WRITE,
	exp_profile_range_rate WRITE, exp_profile_rates WRITE, exp_range_code WRITE,
	exp_unloaded_detention_rate WRITE;

-- Probably keep these
-- exp_category
-- exp_client_billing_rates
-- exp_client_category
-- exp_fuel_type

-- Empty?
-- exp_fuel_card

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE exp_client_assign_rate;
TRUNCATE TABLE exp_client_billing;
TRUNCATE TABLE exp_client_cat_rate_master;
TRUNCATE TABLE exp_client_fright_rate;
TRUNCATE TABLE exp_client_fsc;
TRUNCATE TABLE exp_client_handling_charges;
TRUNCATE TABLE exp_client_rate_master;
TRUNCATE TABLE exp_detention_hours_rate;
TRUNCATE TABLE exp_detention_master;
TRUNCATE TABLE exp_driver_assign_rates;
TRUNCATE TABLE exp_driver_hours;
TRUNCATE TABLE exp_driver_manual_rates;
TRUNCATE TABLE exp_driver_pay_master;
TRUNCATE TABLE exp_driver_range_rates;
TRUNCATE TABLE exp_driver_rates;
TRUNCATE TABLE exp_fsc_history;
TRUNCATE TABLE exp_fsc_schedule;
TRUNCATE TABLE exp_handling;
TRUNCATE TABLE exp_ifta_log;
TRUNCATE TABLE exp_ifta_rate;
TRUNCATE TABLE exp_load_manual_rate;
TRUNCATE TABLE exp_load_pay_master;
TRUNCATE TABLE exp_load_pay_rate;
TRUNCATE TABLE exp_load_range_rate;
TRUNCATE TABLE exp_man_code;
TRUNCATE TABLE exp_pallet_master;
TRUNCATE TABLE exp_pallet_rate_master;
TRUNCATE TABLE exp_profile_manual;
TRUNCATE TABLE exp_profile_manual_rates;
TRUNCATE TABLE exp_profile_master;
TRUNCATE TABLE exp_profile_range;
TRUNCATE TABLE exp_profile_range_rate;
TRUNCATE TABLE exp_profile_rates;
TRUNCATE TABLE exp_range_code;
TRUNCATE TABLE exp_unloaded_detention_rate;
SET FOREIGN_KEY_CHECKS = 1;

UNLOCK TABLES;

-- Profiles
LOCK TABLES exp_carrier WRITE, exp_client WRITE, exp_contact_info WRITE,
	exp_driver WRITE, exp_license WRITE, exp_manual_miles WRITE,
	exp_pcm_cache WRITE, exp_pcm_distance_cache WRITE, exp_tractor WRITE,
	exp_trailer WRITE, quickbooks_config WRITE, quickbooks_log WRITE,
	quickbooks_oauth WRITE, quickbooks_queue WRITE, quickbooks_recur WRITE,
	quickbooks_ticket WRITE, quickbooks_user WRITE, exp_commodity WRITE,
	exp_commodity_class WRITE, exp_user WRITE;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE exp_carrier;
TRUNCATE TABLE exp_client;
TRUNCATE TABLE exp_contact_info;
TRUNCATE TABLE exp_driver;
TRUNCATE TABLE exp_license;
TRUNCATE TABLE exp_manual_miles;
TRUNCATE TABLE exp_pcm_cache;
TRUNCATE TABLE exp_pcm_distance_cache;
TRUNCATE TABLE exp_tractor;
TRUNCATE TABLE exp_trailer;
TRUNCATE TABLE quickbooks_config;
TRUNCATE TABLE quickbooks_log;
TRUNCATE TABLE quickbooks_oauth;
TRUNCATE TABLE quickbooks_recur;
TRUNCATE TABLE quickbooks_ticket;
TRUNCATE TABLE quickbooks_user;
TRUNCATE TABLE exp_commodity;
TRUNCATE TABLE exp_commodity_class;
TRUNCATE TABLE exp_user;
SET FOREIGN_KEY_CHECKS = 1;

UNLOCK TABLES;

-- Unused tables
DROP TABLE IF EXISTS exp_order;
DROP TABLE IF EXISTS exp_order_stop;
DROP TABLE IF EXISTS exp_trace;
DROP TABLE IF EXISTS exp_user_defined;
DROP TABLE IF EXISTS exp_zone;



