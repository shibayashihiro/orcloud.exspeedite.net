-- Patch for 2.0.4

-- You don't need to run the patch_for_2_0_3.sql

use `pipco621`;  -- Change this to your DB name

-- CHECK_SCHEMA should have added these, but if not, uncomment these three indexes.
-- These indexes provide a performance boost to handle the following updates.

--ALTER IGNORE TABLE `exp_client_billing`
--  ADD KEY SHIPMENT (SHIPMENT_ID);

--ALTER IGNORE TABLE `exp_status`
--  ADD KEY ORDER_CODE (ORDER_CODE,SOURCE);

--ALTER IGNORE TABLE `exp_stop`
--  ADD KEY SHIPMENT (SHIPMENT);

-- This is to avoid a left join, and improve performance of various queries.
-- There is also two new triggers on EXP_CLIENT_BILLING to keep EXP_SHIPMENT
-- updated in the future.

UPDATE EXP_SHIPMENT
SET TOTAL_CHARGES = (SELECT TOTAL FROM EXP_CLIENT_BILLING
	WHERE SHIPMENT_ID = SHIPMENT_CODE),
FUEL_SURCHARGE = (SELECT FUEL_COST FROM EXP_CLIENT_BILLING
	WHERE SHIPMENT_ID = SHIPMENT_CODE)
WHERE CURRENT_STATUS IN (31, 32);

-- This fixes null ACTUAL_DELIVERY columns in EXP_SHIPMENT
-- Sometimes the stop does not have an actual time (or the stop could even be missing!)
-- Fall back is the last timestamp the shipment was changed from the EXP_STATUS history

UPDATE EXP_SHIPMENT
SET ACTUAL_DELIVERY = COALESCE((SELECT COALESCE(ACTUAL_DEPART, CHANGED_DATE)
FROM EXP_STOP
WHERE STOP_TYPE = 'drop'
AND SHIPMENT = SHIPMENT_CODE
AND CURRENT_STATUS = 16),
(SELECT MAX(CREATED_DATE) FROM EXP_STATUS
WHERE ORDER_CODE = SHIPMENT_CODE AND
SOURCE = 'shipment') )
                
WHERE CURRENT_STATUS IN (20, 31, 32)
AND ACTUAL_DELIVERY IS NULL