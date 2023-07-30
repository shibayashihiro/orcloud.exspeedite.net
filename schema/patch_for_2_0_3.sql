-- Patch for 2.0.3

-- This copies over from EXP_CLIENT_BILLING into EXP_SHIPMENT the
-- total charges and the fuel surcharge, for approved or billed shipments.

-- You need to run this once after installing 2.0.3

-- This is to avoid a left join, and improve performance of various queries.
-- There is also two new triggers on EXP_CLIENT_BILLING to keep EXP_SHIPMENT
-- updated in the future.


UPDATE EXP_SHIPMENT
SET TOTAL_CHARGES = (SELECT TOTAL FROM EXP_CLIENT_BILLING
	WHERE SHIPMENT_ID = SHIPMENT_CODE),
FUEL_SURCHARGE = (SELECT FUEL_COST FROM EXP_CLIENT_BILLING
	WHERE SHIPMENT_ID = SHIPMENT_CODE)
WHERE CURRENT_STATUS IN (31, 32);

-- This copies over the actual delivery date from EXP_STOP into EXP_SHIPMENT

UPDATE EXP_SHIPMENT
SET ACTUAL_DELIVERY = (SELECT ACTUAL_DEPART
FROM EXP_STOP
WHERE STOP_TYPE = 'drop'
AND SHIPMENT = SHIPMENT_CODE
AND CURRENT_STATUS = 16)
                
WHERE CURRENT_STATUS IN (20, 31, 32);