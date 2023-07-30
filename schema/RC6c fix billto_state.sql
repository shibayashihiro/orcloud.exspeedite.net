update exp_shipment
set BILLTO_STATE = (select state from exp_contact_info
where contact_source = 'client'
and contact_type = 'bill_to'
and contact_code = BILLTO_CLIENT_CODE
limit 1)
where BILLTO_CLIENT_CODE > 0
and BILLTO_STATE is null