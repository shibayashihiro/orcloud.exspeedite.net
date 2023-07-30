    # usps_states_list.sql
    #
    # This will create and then populate a MySQL table with a list of the names and
    # USPS abbreviations for US states and possessions in existence as of the date
    # below.
    #
    # Usage:
    # mysql -u username -ppassword database_name < usps_states_list.sql
    #
    # For updates to this file, see http://27.org/isocountrylist/
    # For more about USPS state abbreviations, see http://www.usps.com/ncsc/lookups/usps_abbreviations.html
    #
    # Wm. Rhodes <iso_country_list@27.org>
    # 1/1/03
    #
     
    CREATE TABLE IF NOT EXISTS EXP_STATES (
    id INT NOT NULL AUTO_INCREMENT,
    STATE_NAME CHAR(40) NOT NULL,
    abbrev CHAR(2) NOT NULL,
    PRIMARY KEY (id)
    );
     
    INSERT INTO EXP_STATES VALUES (NULL, 'Alaska', 'AK');
    INSERT INTO EXP_STATES VALUES (NULL, 'Alabama', 'AL');
    INSERT INTO EXP_STATES VALUES (NULL, 'American Samoa', 'AS');
    INSERT INTO EXP_STATES VALUES (NULL, 'Arizona', 'AZ');
    INSERT INTO EXP_STATES VALUES (NULL, 'Arkansas', 'AR');
    INSERT INTO EXP_STATES VALUES (NULL, 'California', 'CA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Colorado', 'CO');
    INSERT INTO EXP_STATES VALUES (NULL, 'Connecticut', 'CT');
    INSERT INTO EXP_STATES VALUES (NULL, 'Delaware', 'DE');
    INSERT INTO EXP_STATES VALUES (NULL, 'District of Columbia', 'DC');
    INSERT INTO EXP_STATES VALUES (NULL, 'Federated States of Micronesia', 'FM');
    INSERT INTO EXP_STATES VALUES (NULL, 'Florida', 'FL');
    INSERT INTO EXP_STATES VALUES (NULL, 'Georgia', 'GA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Guam', 'GU');
    INSERT INTO EXP_STATES VALUES (NULL, 'Hawaii', 'HI');
    INSERT INTO EXP_STATES VALUES (NULL, 'Idaho', 'ID');
    INSERT INTO EXP_STATES VALUES (NULL, 'Illinois', 'IL');
    INSERT INTO EXP_STATES VALUES (NULL, 'Indiana', 'IN');
    INSERT INTO EXP_STATES VALUES (NULL, 'Iowa', 'IA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Kansas', 'KS');
    INSERT INTO EXP_STATES VALUES (NULL, 'Kentucky', 'KY');
    INSERT INTO EXP_STATES VALUES (NULL, 'Louisiana', 'LA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Maine', 'ME');
    INSERT INTO EXP_STATES VALUES (NULL, 'Marshall Islands', 'MH');
    INSERT INTO EXP_STATES VALUES (NULL, 'Maryland', 'MD');
    INSERT INTO EXP_STATES VALUES (NULL, 'Massachusetts', 'MA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Michigan', 'MI');
    INSERT INTO EXP_STATES VALUES (NULL, 'Minnesota', 'MN');
    INSERT INTO EXP_STATES VALUES (NULL, 'Mississippi', 'MS');
    INSERT INTO EXP_STATES VALUES (NULL, 'Missouri', 'MO');
    INSERT INTO EXP_STATES VALUES (NULL, 'Montana', 'MT');
    INSERT INTO EXP_STATES VALUES (NULL, 'Nebraska', 'NE');
    INSERT INTO EXP_STATES VALUES (NULL, 'Nevada', 'NV');
    INSERT INTO EXP_STATES VALUES (NULL, 'New Hampshire', 'NH');
    INSERT INTO EXP_STATES VALUES (NULL, 'New Jersey', 'NJ');
    INSERT INTO EXP_STATES VALUES (NULL, 'New Mexico', 'NM');
    INSERT INTO EXP_STATES VALUES (NULL, 'New York', 'NY');
    INSERT INTO EXP_STATES VALUES (NULL, 'North Carolina', 'NC');
    INSERT INTO EXP_STATES VALUES (NULL, 'North Dakota', 'ND');
    INSERT INTO EXP_STATES VALUES (NULL, 'Northern Mariana Islands', 'MP');
    INSERT INTO EXP_STATES VALUES (NULL, 'Ohio', 'OH');
    INSERT INTO EXP_STATES VALUES (NULL, 'Oklahoma', 'OK');
    INSERT INTO EXP_STATES VALUES (NULL, 'Oregon', 'OR');
    INSERT INTO EXP_STATES VALUES (NULL, 'Palau', 'PW');
    INSERT INTO EXP_STATES VALUES (NULL, 'Pennsylvania', 'PA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Puerto Rico', 'PR');
    INSERT INTO EXP_STATES VALUES (NULL, 'Rhode Island', 'RI');
    INSERT INTO EXP_STATES VALUES (NULL, 'South Carolina', 'SC');
    INSERT INTO EXP_STATES VALUES (NULL, 'South Dakota', 'SD');
    INSERT INTO EXP_STATES VALUES (NULL, 'Tennessee', 'TN');
    INSERT INTO EXP_STATES VALUES (NULL, 'Texas', 'TX');
    INSERT INTO EXP_STATES VALUES (NULL, 'Utah', 'UT');
    INSERT INTO EXP_STATES VALUES (NULL, 'Vermont', 'VT');
    INSERT INTO EXP_STATES VALUES (NULL, 'Virgin Islands', 'VI');
    INSERT INTO EXP_STATES VALUES (NULL, 'Virginia', 'VA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Washington', 'WA');
    INSERT INTO EXP_STATES VALUES (NULL, 'West Virginia', 'WV');
    INSERT INTO EXP_STATES VALUES (NULL, 'Wisconsin', 'WI');
    INSERT INTO EXP_STATES VALUES (NULL, 'Wyoming', 'WY');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Africa', 'AE');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Americas (except Canada)', 'AA');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Canada', 'AE');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Europe', 'AE');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Middle East', 'AE');
    INSERT INTO EXP_STATES VALUES (NULL, 'Armed Forces Pacific', 'AP');