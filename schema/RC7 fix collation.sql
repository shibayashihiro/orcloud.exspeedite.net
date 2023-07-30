-- Convert everything to CHARACTER SET utf8 COLLATE utf8_general_ci
-- We were using multiple collations before and it was causing issues.
--
-- replace test_exspeeed with your DB name if not the same.

SET collation_connection = 'utf8_general_ci';

ALTER DATABASE `test_exspeeed` CHARACTER SET utf8 COLLATE utf8_general_ci;

use `test_exspeeed`;

ALTER TABLE exp_category CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_assign_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_billing CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_billing_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_cat_rate_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_category CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_fright_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_fsc CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_handling_charges CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_client_rate_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_commodity CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_commodity_class CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_contact_info CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_detail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_detention_hours_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_detention_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_assign_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_hours CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_manual_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_pay_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_range_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_driver_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_fsc_history CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_fsc_schedule CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_fuel_card CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_fuel_type CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_handling CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_holidays CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_ifta_log CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_ifta_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_image CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_license CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_load CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_load_manual_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_load_pay_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_load_pay_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_load_range_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_man_code CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_manual_miles CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_osd CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_pallet CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_pallet_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_pallet_rate_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_pcm_cache CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_pcm_distance_cache CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_manual CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_manual_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_master CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_range CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_range_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_profile_rates CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_range_code CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_report CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_setting CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_shipment CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_shipment_load CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_states CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_status CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_status_codes CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_stop CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_tractor CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_trailer CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_un_number CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_unit CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_unloaded_detention_rate CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_vacation CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE exp_zone_filter CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_config CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_log CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_oauth CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_queue CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_recur CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_ticket CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE quickbooks_user CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE zipcodes CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
