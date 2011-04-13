<?php
 
$_VAE['local_newest_version'] = "0.4.0";
 
function _vae_list_countries() {
  return array('AF' => 'Afghanistan',
               'AX' => 'Aland Islands',
               'AL' => 'Albania',
               'DZ' => 'Algeria',
               'AS' => 'American Samoa',
               'AD' => 'Andorra',
               'AO' => 'Angola',
               'AI' => 'Anguilla',
               'AQ' => 'Antarctica',
               'AG' => 'Antigua and Barbuda',
               'AR' => 'Argentina',
               'AM' => 'Armenia',
               'AW' => 'Aruba',
               'AU' => 'Australia',
               'AT' => 'Austria',
               'AZ' => 'Azerbaijan',
               'BS' => 'Bahamas',
               'BH' => 'Bahrain',
               'BD' => 'Bangladesh',
               'BB' => 'Barbados',
               'BY' => 'Belarus',
               'BE' => 'Belgium',
               'BZ' => 'Belize',
               'BJ' => 'Benin',
               'BM' => 'Bermuda',
               'BT' => 'Bhutan',
               'BO' => 'Bolivia',
               'BA' => 'Bosnia-Herzegovina',
               'BW' => 'Botswana',
               'BR' => 'Brazil',
               'VG' => 'British Virgin Islands',
               'BN' => 'Brunei Darussalam',
               'BG' => 'Bulgaria',
               'BF' => 'Burkina Faso',
               'MM' => 'Burma',
               'BI' => 'Burundi',
               'KH' => 'Cambodia',
               'CM' => 'Cameroon',
               'CA' => 'Canada',
               'CV' => 'Cape Verde',
               'KY' => 'Cayman Islands',
               'CF' => 'Central African Republic',
               'TD' => 'Chad',
               'CL' => 'Chile',
               'CN' => 'China',
               'CX' => 'Christmas Island (Australia)',
               'CC' => 'Cocos Island (Australia)',
               'CO' => 'Colombia',
               'KM' => 'Comoros',
               'CG' => 'Congo (Brazzaville),Republic of the',
               'ZR' => 'Congo, Democratic Republic of the',
               'CK' => 'Cook Islands (New Zealand)',
               'CR' => 'Costa Rica',
               'CI' => 'Cote d\'Ivoire (Ivory Coast)',
               'HR' => 'Croatia',
               'CU' => 'Cuba',
               'CY' => 'Cyprus',
               'CZ' => 'Czech Republic',
               'DK' => 'Denmark',
               'DJ' => 'Djibouti',
               'DM' => 'Dominica',
               'DO' => 'Dominican Republic',
               'TP' => 'East Timor (Indonesia)',
               'EC' => 'Ecuador',
               'EG' => 'Egypt',
               'SV' => 'El Salvador',
               'GQ' => 'Equatorial Guinea',
               'ER' => 'Eritrea',
               'EE' => 'Estonia',
               'ET' => 'Ethiopia',
               'FK' => 'Falkland Islands',
               'FO' => 'Faroe Islands',
               'FJ' => 'Fiji',
               'FI' => 'Finland',
               'FR' => 'France',
					     'GF' => 'French Guiana',
               'PF' => 'French Polynesia',
               'GA' => 'Gabon',
               'GM' => 'Gambia',
               'GE' => 'Georgia, Republic of',
               'DE' => 'Germany',
               'GH' => 'Ghana',
               'GI' => 'Gibraltar',
               'GR' => 'Greece',
               'GL' => 'Greenland',
               'GD' => 'Grenada',
               'GP' => 'Guadeloupe',
               'GT' => 'Guatemala',
               'GN' => 'Guinea',
               'GW' => 'Guinea-Bissau',
               'GY' => 'Guyana',
               'HT' => 'Haiti',
               'HN' => 'Honduras',
               'HK' => 'Hong Kong',
               'HU' => 'Hungary',
               'IS' => 'Iceland',
               'IN' => 'India',
               'ID' => 'Indonesia',
               'IR' => 'Iran',
               'IQ' => 'Iraq',
               'IE' => 'Ireland',
               'IL' => 'Israel',
               'IT' => 'Italy',
               'JM' => 'Jamaica',
               'JP' => 'Japan',
               'JO' => 'Jordan',
               'KZ' => 'Kazakhstan',
               'KE' => 'Kenya',
               'KI' => 'Kiribati',
               'KW' => 'Kuwait',
               'KG' => 'Kyrgyzstan',
               'LA' => 'Laos',
               'LV' => 'Latvia',
               'LB' => 'Lebanon',
               'LS' => 'Lesotho',
               'LR' => 'Liberia',
               'LY' => 'Libya',
               'LI' => 'Liechtenstein',
               'LT' => 'Lithuania',
               'LU' => 'Luxembourg',
               'MO' => 'Macao',
               'MK' => 'Macedonia, Republic of',
               'MG' => 'Madagascar',
               'MW' => 'Malawi',
               'MY' => 'Malaysia',
               'MV' => 'Maldives',
               'ML' => 'Mali',
               'MT' => 'Malta',
               'MQ' => 'Martinique',
               'MR' => 'Mauritania',
               'MU' => 'Mauritius',
               'YT' => 'Mayotte (France)',
               'MX' => 'Mexico',
               'MD' => 'Moldova',
               'MC' => 'Monaco (France)',
               'MN' => 'Mongolia',
               'MS' => 'Montserrat',
               'MA' => 'Morocco',
               'MZ' => 'Mozambique',
               'NA' => 'Namibia',
               'NR' => 'Nauru',
               'NP' => 'Nepal',
               'NL' => 'Netherlands',
               'AN' => 'Netherlands Antilles',
               'NC' => 'New Caledonia',
               'NZ' => 'New Zealand',
               'NI' => 'Nicaragua',
               'NE' => 'Niger',
               'NG' => 'Nigeria',
               'KP' => 'North Korea',
               'NO' => 'Norway',
               'OM' => 'Oman',
               'PK' => 'Pakistan',
               'PA' => 'Panama',
               'PG' => 'Papua New Guinea',
               'PY' => 'Paraguay',
               'PE' => 'Peru',
               'PH' => 'Philippines',
               'PN' => 'Pitcairn Island',
               'PL' => 'Poland',
               'PT' => 'Portugal',
               'QA' => 'Qatar',
               'RE' => 'Reunion',
               'RO' => 'Romania',
               'RU' => 'Russia',
               'RW' => 'Rwanda',
               'SH' => 'Saint Helena',
               'KN' => 'Saint Kitts',
               'LC' => 'Saint Lucia',
               'PM' => 'Saint Pierre and Miquelon',
               'VC' => 'Saint Vincent and the Grenadines',
               'SM' => 'San Marino',
               'ST' => 'Sao Tome and Principe',
               'SA' => 'Saudi Arabia',
               'SN' => 'Senegal',
               'YU' => 'Serbia-Montenegro',
               'SC' => 'Seychelles',
               'SL' => 'Sierra Leone',
               'SG' => 'Singapore',
               'SK' => 'Slovak Republic',
               'SI' => 'Slovenia',
               'SB' => 'Solomon Islands',
               'SO' => 'Somalia',
               'ZA' => 'South Africa',
               'GS' => 'South Georgia (Falkland Islands)',
               'KR' => 'South Korea (Korea, Republic of)',
               'ES' => 'Spain',
               'LK' => 'Sri Lanka',
               'SD' => 'Sudan',
               'SR' => 'Suriname',
               'SZ' => 'Swaziland',
               'SE' => 'Sweden',
               'CH' => 'Switzerland',
               'SY' => 'Syrian Arab Republic',
               'TW' => 'Taiwan',
               'TJ' => 'Tajikistan',
               'TZ' => 'Tanzania',
               'TH' => 'Thailand',
               'TG' => 'Togo',
               'TK' => 'Tokelau Group (Western Samoa)',
               'TO' => 'Tonga',
               'TT' => 'Trinidad and Tobago',
               'TN' => 'Tunisia',
               'TR' => 'Turkey',
               'TM' => 'Turkmenistan',
               'TC' => 'Turks and Caicos Islands',
               'TV' => 'Tuvalu',
               'UG' => 'Uganda',
               'UA' => 'Ukraine',
               'AE' => 'United Arab Emirates',
               'GB' => 'United Kingdom',
               'US' => 'United States',
               'UY' => 'Uruguay',
               'UZ' => 'Uzbekistan',
               'VU' => 'Vanuatu',
               'VA' => 'Vatican City',
               'VE' => 'Venezuela',
               'VN' => 'Vietnam',
               'WF' => 'Wallis and Futuna Islands',
               'EH' => 'Western Sahara',
               'WS' => 'Western Samoa',
               'YE' => 'Yemen',
               'ZM' => 'Zambia',
               'ZW' => 'Zimbabwe');
}

$_VAE['continents'] = array("AF","AN","AS","EU","NA","OC","SA");

$_VAE['states'] = array(
  "AU" => array(
    array('', ""), 
		array('ACT', "Australian Capital Territory (ACT)"),
		array('JBT', "Jervis Bay Territory (JBT)"),
		array('NSW', "New South Wales (NSW)"),
		array('NT' , "Northern Territory (NT)"),
		array('QLD', "Queensland (QLD)"),
		array('SA' , "South Australia (SA)"),
		array('TAS', "Tasmania (TAS)"),
		array('VIC', "Victoria (VIC)"),
		array('WA' , "Western Australia (WA)"),
  ),
  "CA" => array(
    array('', ""), 
		array('AB' , "Alberta (AB)"),
		array('BC' , "British Columbia (BC)"),
		array('MB' , "Manitoba (MB)"),
		array('NL' , "Newfoundland and Labrador (NL)"),
		array('NB' , "New Brunswick (NB)"),
		array('NT' , "Northwest Territories (NT)"),
		array('NS' , "Nova Scotia (NS)"),
		array('NU' , "Nunavut (NU)"),
		array('ON' , "Ontario (ON)"),
		array('PE' , "Prince Edward Island (PE)"),
		array('SK' , "Saskatchewan (SK)"),
		array('QC' , "Quebec (QC)"),
		array('YT' , "Yukon (YT)")
  ),
  "US" => array(
    array('', ""), 
    array('AA' , "Armed Forces Americas (AA)"),
    array('AE' , "Armed Forces Europe (AE)"),
    array('AP' , "Armed Forces Pacific (AP)"),
    array('AL' , "Alabama (AL)"),
		array('AK' , "Alaska (AK)"),
		array('AS' , "American Samoa (AS)"),
		array('AZ' , "Arizona (AZ)"),
		array('AR' , "Arkansas (AR)"),
		array('CA' , "California (CA)"),
		array('CO' , "Colorado (CO)"),
		array('CT' , "Connecticut (CT)"),
		array('DE' , "Delaware (DE)"),
		array('DC' , "District Of Columbia (DC)"),
		array('FL' , "Florida (FL)"),
		array('FM' , "Federated States of Micronesia (FM)"),
		array('GA' , "Georgia (GA)"),
		array('GU' , "Guam (GU)"),
		array('HI' , "Hawaii (HI)"),
		array('ID' , "Idaho (ID)"),
		array('IL' , "Illinois (IL)"),
		array('IN' , "Indiana (IN)"),
		array('IA' , "Iowa (IA)"),
		array('KS' , "Kansas (KS)"),
		array('KY' , "Kentucky (KY)"),
		array('LA' , "Louisiana (LA)"),
		array('ME' , "Maine (ME)"),
		array('MH' , "Marshall Islands (MH)"),
		array('MD' , "Maryland (MD)"),
		array('MA' , "Massachusetts (MA)"),
		array('MI' , "Michigan (MI)"),
		array('MN' , "Minnesota (MN)"),
		array('MS' , "Mississippi (MS)"),
		array('MO' , "Missouri (MO)"),
		array('MT' , "Montana (MT)"),
		array('NE' , "Nebraska (NE)"),
		array('NV' , "Nevada (NV)"),
		array('NH' , "New Hampshire (NH)"),
		array('NJ' , "New Jersey (NJ)"),
		array('NM' , "New Mexico (NM)"),
		array('NY' , "New York (NY)"),
		array('NC' , "North Carolina (NC)"),
		array('ND' , "North Dakota (ND)"),
		array('MP' , "Northern Mariana Islands (MP)"),
		array('OH' , "Ohio (OH)"),
		array('OK' , "Oklahoma (OK)"),
		array('OR' , "Oregon (OR)"),
		array('PW' , "Palau (PW)"),
		array('PA' , "Pennsylvania (PA)"),
		array('PR' , "Puerto Rico (PR)"),
		array('RI' , "Rhode Island (RI)"),
		array('SC' , "South Carolina (SC)"),
		array('SD' , "South Dakota (SD)"),
		array('TN' , "Tennessee (TN)"),
		array('TX' , "Texas (TX)"),
		array('UT' , "Utah (UT)"),
		array('VT' , "Vermont (VT)"),
		array('VA' , "Virginia (VA)"),
		array('VI' , "Virgin Islands (VI)"),
		array('WA' , "Washington (WA)"),
		array('WV' , "West Virginia (WV)"),
		array('WI' , "Wisconsin (WI)"),
		array('WY' , "Wyoming (WY)")
  )
);

$_VAE['attributes'] = array(
  'a' => array('charset','coords','href','hreflang','name','rel','rev','shape','target','type'),
  'collection' => array("all","default_page","filter_input","groups","id","max_pages","nested","next","order","output_order","page_select","paginate","path","per_row","previous","skip","store_in_session","wrap"),
  'form' => array('accept','action','accept-charset','ajax','enctype','flash','method','name','target','validateinline'),
  'img' => array('align','alt','border','height','hspace','ismap','longdesc','src','usemap','vspace','width'),
  'input' => array('accept','align','alt','checked','disabled','maxlength','name','placeholder','readonly','size','src','type','value'),
  'select' => array('default','disabled','multiple','name','options','size'),
  'standard' => array('accesskey','class','dir','id','lang','style','tabindex','title','xml:lang','onblur','onchange','onfocus','onreset','onselect','onsubmit','onclick','ondblclick','onkeydown','onkeypress','onkeyup','onmousedown','onmousemove','onmouseover','onmouseout','onmouseup'),
  'textarea' => array('cols','disabled','name','readonly','rows')
);

$_VAE['currency_names'] = array(
  "AUD" => "Australian Dollar",
  "CAD" => "Canadian Dollar",
  "CNY" => "Chinese Yuan",
  "CZK" => "Czech Koruna",
  "EUR" => "Euro",
  "INR" => "Indian Rupee",
  "JPY" => "Japanese Yen",
  "MYR" => "Malaysian Ringgit",
  "MXN" => "Mexican Peso",
  "NZD" => "New Zealand Dollar",
  "NOK" => "Norwegian Krone",
  "PLN" => "Polish Zloty",
  "GBP" => "Pound Sterling",
  "SGD" => "Singapore Dollar",
  "ZAR" => "South African Rand",
  "SEK" => "Swedish Krona",
  "CHF" => "Swiss Franc",
  "THB" => "Thai Baht",
  "USD" => "United States Dollar"
);

$_VAE['currency_symbols'] = array(
  "AUD" => "$",
  "CAD" => "$",
  "CNY" => "&#165;",
  "CZK" => "K&#269;",
  "EUR" => "&#8364;",
  "INR" => "&#8360;",
  "JPY" => "&#165;",
  "MYR" => "RM",
  "MXN" => "$",
  "NOK" => "kr",
  "NZD" => "$",
  "PLN" => "z&#322;",
  "GBP" => "&#163;",
  "SGD" => "$",
  "ZAR" => "R",
  "SEK" => "kr",
  "CHF" => "CHF",
  "THB" => "&#3647;",
  "USD" => "$"
);

$_VAE['recaptcha'] = array(
  'public' => '6LdnAwUAAAAAAKkRHsF2xiQJ_IEqYOkf8NUux3uk',
  'private' => '6LdnAwUAAAAAANP63-wrr83KBL7byxb4diiMJT1_'
);

$_VAE['store']['payment_methods'] = array(
  'authorize_net' => array('name' => "Credit Card", 'credit_card' => true), 
  'braintree' => array('name' => "Credit Card", 'credit_card' => true), 
  'card_stream' => array('name' => "Credit Card", 'credit_card' => true), 
  'cyber_source' => array('name' => "Credit Card", 'credit_card' => true), 
  'data_cash' => array('name' => "Credit Card", 'credit_card' => true), 
  'efsnet' => array('name' => "Credit Card", 'credit_card' => true), 
  'eway' => array('name' => "Credit Card", 'credit_card' => true), 
  'exact' => array('name' => "Credit Card", 'credit_card' => true), 
  'google_checkout' => array('name' => "Google Checkout", 'callback' => '_vae_store_payment_google_checkout_callback', 'ipn' => '_vae_store_payment_google_checkout_ipn'), 
  'linkpoint' => array('name' => "Credit Card", 'credit_card' => true), 
  'check' => array('name' => "Check"), 
  'money_order' => array('name' => "Money Order"), 
  'bank_transfer' => array('name' => "Bank Transfer"), 
  'in_store' => array('name' => "Pay In Store"), 
  'exact' => array('name' => "Credit Card", 'credit_card' => true), 
  'lucy' => array('name' => "Credit Card", 'credit_card' => true), 
  'modern_payments' => array('name' => "Credit Card", 'credit_card' => true), 
  'moneris' => array('name' => "Credit Card", 'credit_card' => true), 
  'net_registry' => array('name' => "Credit Card", 'credit_card' => true), 
  'netbilling' => array('name' => "Credit Card", 'credit_card' => true), 
  'pay_junction' => array('name' => "Credit Card", 'credit_card' => true), 
  'pay_secure' => array('name' => "Credit Card", 'credit_card' => true), 
  'payflow_pro' => array('name' => "Credit Card", 'credit_card' => true), 
  'payment_express' => array('name' => "Credit Card", 'credit_card' => true), 
  'paypal' => array('name' => "PayPal", 'callback' => '_vae_store_payment_paypal_callback', 'ipn' => '_vae_store_payment_paypal_ipn'), 
  'paypal_direct_payment' => array('name' => "Credit Card", 'credit_card' => true), 
  'paypal_express_checkout' => array('name' => "PayPal Express Checkout"), 
  'psigate' => array('name' => "Credit Card", 'credit_card' => true), 
  'psi_card' => array('name' => "Credit Card", 'credit_card' => true), 
  'quickpay' => array('name' => "Credit Card", 'credit_card' => true), 
  'realex' => array('name' => "Credit Card", 'credit_card' => true), 
  'sage' => array('name' => "Credit Card", 'credit_card' => true), 
  'secure_pay' => array('name' => "Credit Card", 'credit_card' => true), 
  'skip_jack' => array('name' => "Credit Card", 'credit_card' => true), 
  'trans_first' => array('name' => "Credit Card", 'credit_card' => true), 
  'trust_commerce' => array('name' => "Credit Card", 'credit_card' => true), 
  'verifi' => array('name' => "Credit Card", 'credit_card' => true), 
  'viaklix' => array('name' => "Credit Card", 'credit_card' => true), 
  'wirecard' => array('name' => "Credit Card", 'credit_card' => true), 
  'test' => array('name' => "Credit Card", 'credit_card' => true)
);






/**********/






$_VAE['tags'] = array (
  'a' => 
  array (
    'handler' => '_vae_render_a',
    'html' => 'a',
  ),
  'a_if' => 
  array (
    'handler' => '_vae_render_a_if',
    'html' => 'a',
  ),
  'asset' => 
  array (
    'handler' => '_vae_render_asset',
  ),
  'captcha' => 
  array (
    'handler' => '_vae_render_captcha',
    'html' => 'input',
  ),
  'cdn' => 
  array (
    'handler' => '_vae_render_cdn'
  ),
  'checkbox' => 
  array (
    'handler' => '_vae_render_checkbox',
    'html' => 'input',
  ),
  'collection' => 
  array (
    'handler' => '_vae_render_collection',
    'required' => 
    array (
      0 => 'path',
    ),
  ),
  'country_select' => 
  array (
    'handler' => '_vae_render_country_select',
    'html' => 'select',
  ),
  'create' => 
  array (
    'callback' => '_vae_callback_create',
    'handler' => '_vae_render_create',
    'required' => 
    array (
      0 => 'path',
    ),
    'filename' => 'callback.php',
    'html' => 'form',
  ),
  'date_select' => 
  array (
    'handler' => '_vae_render_date_select',
    'html' => 'select',
  ),
  'date_selection' => 
  array (
    'handler' => '_vae_render_date_selection',
    'html' => 'a',
    'required' => 
    array (
      0 => 'path',
      1 => 'date_field',
    ),
  ),
  'debug' => 
  array (
    'handler' => '_vae_render_debug',
  ),
  'disqus' => 
  array (
    'handler' => '_vae_render_disqus',
    'required' => 
    array (
      0 => 'shortname',
    ),
  ),
  'divider' => 
  array (
    'handler' => '_vae_render_divider',
  ),
  'else' => 
  array (
    'handler' => '_vae_render_else',
  ),
  'elseif' => 
  array (
    'handler' => '_vae_render_elseif',
  ),
  'facebook_comments' => 
  array (
    'handler' => '_vae_render_facebook_comments',
    'required' => 
    array (
      0 => 'appid',
    ),
  ),
  'facebook_like' => 
  array (
    'handler' => '_vae_render_facebook_like',
  ),
  'file' => 
  array (
    'callback' => '_vae_callback_file',
    'handler' => '_vae_render_file',
    'html' => 'a',
    'required' => 
    array (
      0 => 'path',
    ),
    'filename' => 'callback.php',
  ),
  'file_field' => 
  array (
    'handler' => '_vae_render_file_field',
    'html' => 'input',
  ),
  'fileurl' => 
  array (
    'handler' => '_vae_render_fileurl',
    'required' => 
    array (
      0 => 'path',
    ),
  ),
  'flash' => 
  array (
    'handler' => '_vae_render_flash',
  ),
  'form' => 
  array (
    'handler' => '_vae_render_form',
    'html' => 'form',
    'required' => 
    array (
    ),
  ),
  'formmail' => 
  array (
    'callback' => '_vae_callback_formmail',
    'handler' => '_vae_render_formmail',
    'html' => 'form',
    'required' => 
    array (
      0 => 'to',
    ),
    'filename' => 'callback.php',
  ),
  'fragment' => 
  array (
    'handler' => '_vae_render_fragment',
  ),
  'gravatar' => 
  array (
    'handler' => '_vae_render_gravatar',
    'html' => 'img',
    'required' => 
    array (
      0 => 'email',
    ),
  ),
  'hidden_field' => 
  array (
    'handler' => '_vae_render_hidden_field',
    'html' => 'input',
  ),
  'if' => 
  array (
    'handler' => '_vae_render_if',
  ),
  'if_backstage' => 
  array (
    'handler' => '_vae_render_if_backstage',
  ),
  'if_paginate' => 
  array (
    'handler' => '_vae_render_if_paginate',
  ),
  'if_time' => 
  array (
    'handler' => '_vae_render_if_time',
  ),
  'img' => 
  array (
    'handler' => '_vae_render_img',
    'html' => 'img'
  ),
  'imurl' => 
  array (
    'handler' => '_vae_render_img'
  ),
  'module' => 
  array (
    'handler' => '_vae_render_template',
    'required' => 
    array (
      0 => 'filename',
    ),
  ),
  'nested_collection' => 
  array (
    'handler' => '_vae_render_nested_collection',
    'required' => 
    array (
      0 => 'path',
    ),
  ),
  'nested_divider' => 
  array (
    'handler' => '_vae_render_divider',
  ),
  'newsletter' => 
  array (
    'callback' => '_vae_callback_newsletter',
    'handler' => '_vae_render_newsletter',
    'html' => 'form',
    'required' => 
    array (
      0 => 'code',
    ),
    'filename' => 'callback.php',
  ),
  'nowidows' => 
  array (
    'handler' => '_vae_render_nowidows',
  ),
  'option_select' => 
  array (
    'handler' => '_vae_render_option_select',
    'html' => 'select',
    'required' => 
    array (
      0 => 'fields',
      1 => 'name',
      2 => 'path',
    ),
  ),
  'pdf' => 
  array (
    'handler' => '_vae_render_pdf',
    'required' => 
    array (
    ),
  ),
  'pagination' => 
  array (
    'handler' => '_vae_render_pagination',
    'html' => 'a',
  ),
  'password_field' => 
  array (
    'handler' => '_vae_render_password_field',
    'html' => 'input',
  ),
  'php' => 
  array (
    'handler' => '_vae_render_php',
  ),
  'radio' => 
  array (
    'handler' => '_vae_render_radio',
    'html' => 'input',
  ),
  'repeat' => 
  array (
    'handler' => '_vae_render_repeat',
  ),
  'require_permalink' => 
  array (
    'handler' => '_vae_render_require_permalink',
    'required' => 
    array (
    ),
  ),
  'require_ssl' => 
  array (
    'handler' => '_vae_render_require_ssl',
    'required' => 
    array (
    ),
  ),
  'rss' => 
  array (
    'handler' => '_vae_render_rss',
    'required' => 
    array (
      0 => 'path',
      1 => 'title',
      2 => 'description',
    ),
  ),
  'section' => 
  array (
    'handler' => '_vae_render_section',
    'required' => 
    array (
      0 => 'path',
    ),
  ),
  'select' => 
  array (
    'handler' => '_vae_render_select',
    'html' => 'select',
  ),
  'set' => 
  array (
    'handler' => '_vae_render_set',
    'required' => 
    array (
      0 => 'name',
    ),
  ),
  'set_default' => 
  array (
    'handler' => '_vae_render_set_default',
    'required' => array('name')
  ),
  'session_dump' => 
  array (
    'handler' => '_vae_render_session_dump',
    'required' => 
    array (
      0 => 'key',
    ),
  ),
  'site_seal' => 
  array (
    'handler' => '_vae_render_site_seal',
  ),
  'state_select' => 
  array (
    'handler' => '_vae_render_state_select',
    'html' => 'select',
  ),
  'template' => 
  array (
    'handler' => '_vae_render_template',
    'required' => 
    array (
      0 => 'filename',
    ),
  ),
  'text' => 
  array (
    'handler' => '_vae_render_text',
    'required' => 
    array (
    ),
  ),
  'text_area' => 
  array (
    'handler' => '_vae_render_text_area',
    'html' => 'textarea',
  ),
  'text_field' => 
  array (
    'handler' => '_vae_render_text_field',
    'html' => 'input',
  ),
  'unsubscribe' => 
  array (
    'handler' => '_vae_render_unsubscribe',
  ),
  'update' => 
  array (
    'callback' => '_vae_callback_update',
    'handler' => '_vae_render_update',
    'filename' => 'callback.php',
    'html' => 'form',
  ),
  'video' => 
  array (
    'handler' => '_vae_render_video',
  ),
  'yield' => 
  array (
    'handler' => '_vae_render_yield',
  ),
  'zip' => 
  array (
    'callback' => '_vae_callback_zip',
    'handler' => '_vae_render_zip',
    'html' => 'a',
    'filename' => 'callback.php',
  ),
  'store_add_to_cart' => 
  array (
    'callback' => '_vae_store_callback_add_to_cart',
    'handler' => '_vae_store_render_add_to_cart',
    'html' => 'form',
  ),
  'store_address_delete' => 
  array (
    'callback' => '_vae_store_callback_address_delete',
    'html' => 'a',
    'handler' => '_vae_store_render_address_delete',
  ),
  'store_address_select' => 
  array (
    'callback' => '_vae_store_callback_address_select',
    'handler' => '_vae_store_render_address_select',
    'html' => 'select',
  ),
  'store_addresses' => 
  array (
    'handler' => '_vae_store_render_addresses',
  ),
  'store_bundled_item' => 
  array (
    'handler' => '_vae_store_render_bundled_item',
  ),
  'store_cart' => 
  array (
    'callback' => '_vae_store_callback_cart',
    'handler' => '_vae_store_render_cart',
    'html' => 'form',
  ),
  'store_cart_items' => 
  array (
    'handler' => '_vae_store_render_cart_items',
  ),
  'store_cart_discount' => 
  array (
    'handler' => '_vae_store_render_cart_discount',
  ),
  'store_cart_shipping' => 
  array (
    'handler' => '_vae_store_render_cart_shipping',
  ),
  'store_cart_subtotal' => 
  array (
    'handler' => '_vae_store_render_cart_subtotal',
  ),
  'store_cart_tax' => 
  array (
    'handler' => '_vae_store_render_cart_tax',
  ),
  'store_cart_total' => 
  array (
    'handler' => '_vae_store_render_cart_total',
  ),
  'store_checkout' => 
  array (
    'callback' => '_vae_store_callback_checkout',
    'handler' => '_vae_store_render_checkout',
    'html' => 'form',
    'required' => 
    array (
      0 => 'redirect',
      1 => 'register_page',
    ),
  ),
  'store_credit_card' => 
  array (
    'handler' => '_vae_store_render_if_credit_card',
  ),
  'store_credit_card_select' => 
  array (
    'handler' => '_vae_store_render_credit_card_select',
    'html' => 'select',
  ),
  'store_currency' => 
  array (
    'handler' => '_vae_store_render_currency',
  ),
  'store_currency_select' => 
  array (
    'callback' => '_vae_store_callback_currency_select',
    'handler' => '_vae_store_render_currency_select',
    'html' => 'select',
  ),
  'store_discount' => 
  array (
    'callback' => '_vae_store_callback_discount',
    'handler' => '_vae_store_render_discount',
    'html' => 'form',
  ),
  'store_forgot' => 
  array (
    'callback' => '_vae_store_callback_forgot',
    'handler' => '_vae_store_render_forgot',
    'html' => 'form',
  ),
  'store_google_checkout' => 
  array (
    'callback' => '_vae_store_callback_google_checkout',
    'handler' => '_vae_store_render_google_checkout',
    'html' => 'img',
  ),
  'store_if_bank_transfer' => 
  array (
    'handler' => '_vae_store_render_if_bank_transfer',
  ),
  'store_if_check' => 
  array (
    'handler' => '_vae_store_render_if_check',
  ),
  'store_if_credit_card' => 
  array (
    'handler' => '_vae_store_render_if_credit_card',
  ),
  'store_if_currency' => 
  array (
    'handler' => '_vae_store_render_if_currency',
  ),
  'store_if_digital_downloads' => 
  array (
    'handler' => '_vae_store_render_if_digital_downloads',
  ),
  'store_if_discount' => 
  array (
    'handler' => '_vae_store_render_if_discount',
  ),
  'store_if_field_overridden' => 
  array (
    'handler' => '_vae_store_render_if_field_overridden',
    'required' => 
    array (
      0 => 'field',
      1 => 'options_collection'
    ),
  ),
  'store_if_logged_in' => 
  array (
    'handler' => '_vae_store_render_if_logged_in',
  ),
  'store_if_money_order' => 
  array (
    'handler' => '_vae_store_render_if_money_order',
  ),
  'store_if_pay_in_store' => 
  array (
    'handler' => '_vae_store_render_if_pay_in_store',
  ),
  'store_if_paypal' => 
  array (
    'handler' => '_vae_store_render_if_paypal',
  ),
  'store_if_paypal_express_checkout' => 
  array (
    'handler' => '_vae_store_render_if_paypal_express_checkout',
  ),
  'store_if_recent_order_bank_transfer' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_bank_transfer',
  ),
  'store_if_recent_order_check' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_check',
  ),
  'store_if_recent_order_credit_card' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_credit_card',
  ),
  'store_if_recent_order_digital' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_digital',
  ),
  'store_if_recent_order_money_order' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_money_order',
  ),
  'store_if_recent_order_pay_in_store' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_pay_in_store',
  ),
  'store_if_recent_order_paypal' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_paypal',
  ),
  'store_if_recent_order_paypal_express_checkout' => 
  array (
    'handler' => '_vae_store_render_if_recent_order_paypal_express_checkout',
  ),
  'store_if_shippable' => 
  array (
    'handler' => '_vae_store_render_if_shippable',
  ),
  'store_if_tax' => 
  array (
    'handler' => '_vae_store_render_if_tax',
  ),
  'store_if_user' => 
  array (
    'handler' => '_vae_store_render_if_user',
  ),
  'store_item_if_discount' => 
  array (
    'handler' => '_vae_store_render_item_if_discount',
  ),
  'store_item_price' => 
  array (
    'handler' => '_vae_store_render_item_price',
  ),
  'store_login' => 
  array (
    'callback' => '_vae_store_callback_login',
    'handler' => '_vae_store_render_login',
    'html' => 'form',
  ),
  'store_logout' => 
  array (
    'callback' => '_vae_store_callback_logout',
    'html' => 'a',
    'handler' => '_vae_store_render_logout',
  ),
  'store_paypal_checkout' => 
  array (
    'callback' => '_vae_store_callback_paypal_checkout',
    'handler' => '_vae_store_render_paypal_checkout',
    'html' => 'img',
  ),
  'store_paypal_express_checkout' => 
  array (
    'callback' => '_vae_store_callback_paypal_express_checkout',
    'handler' => '_vae_store_render_paypal_express_checkout',
    'html' => 'img',
  ),
  'store_payment_methods_select' => 
  array (
    'callback' => '_vae_store_callback_payment_methods_select',
    'handler' => '_vae_store_render_payment_methods_select',
    'html' => 'select',
  ),
  'store_previous_order_items' => 
  array (
    'handler' => '_vae_store_render_previous_order_items',
  ),
  'store_previous_order' => 
  array (
    'handler' => '_vae_store_render_previous_order',
  ),
  'store_previous_orders' => 
  array (
    'handler' => '_vae_store_render_previous_orders',
  ),
  'store_recent_order' => 
  array (
    'handler' => '_vae_store_render_recent_order',
  ),
  'store_recent_order_items' => 
  array (
    'handler' => '_vae_store_render_recent_order_items',
  ),
  'store_register' => 
  array (
    'callback' => '_vae_store_callback_register',
    'handler' => '_vae_store_render_register',
    'html' => 'form',
    'required' => 
    array (
      0 => 'redirect',
    ),
  ),
  'store_shipping_methods_select' => 
  array (
    'callback' => '_vae_store_callback_shipping_methods_select',
    'handler' => '_vae_store_render_shipping_methods_select',
    'html' => 'select',
  ),
  'store_user' => 
  array (
    'handler' => '_vae_store_render_user',
  ),
  'users_forgot' => 
  array (
    'callback' => '_vae_users_callback_forgot',
    'handler' => '_vae_users_render_forgot',
    'html' => 'form',
    'required' => 
    array (
      0 => 'email_field',
      1 => 'path',
      2 => 'required',
    ),
    'filename' => 'users.php',
  ),
  'users_if_logged_in' => 
  array (
    'handler' => '_vae_users_render_if_logged_in',
    'filename' => 'users.php',
  ),
  'users_login' => 
  array (
    'callback' => '_vae_users_callback_login',
    'handler' => '_vae_users_render_login',
    'html' => 'form',
    'required' => 
    array (
      0 => 'path',
      1 => 'required',
    ),
    'filename' => 'users.php',
  ),
  'users_logout' => 
  array (
    'callback' => '_vae_users_callback_logout',
    'html' => 'a',
    'handler' => '_vae_users_render_logout',
    'filename' => 'users.php',
  ),
  'users_register' => 
  array (
    'callback' => '_vae_users_callback_register',
    'handler' => '_vae_users_render_register',
    'html' => 'form',
    'required' => 
    array (
      0 => 'path',
      1 => 'redirect',
    ),
    'filename' => 'users.php',
  ),
  'store_if_in_cart' => 
  array (
    'handler' => '_vae_store_render_if_in_cart',
  ),
);
$_VAE['callbacks'] = array (
  'create' => 
  array (
    'callback' => '_vae_callback_create',
    'filename' => 'callback.php',
  ),
  'file' => 
  array (
    'callback' => '_vae_callback_file',
    'filename' => 'callback.php',
  ),
  'formmail' => 
  array (
    'callback' => '_vae_callback_formmail',
    'filename' => 'callback.php',
  ),
  'newsletter' => 
  array (
    'callback' => '_vae_callback_newsletter',
    'filename' => 'callback.php',
  ),
  'update' => 
  array (
    'callback' => '_vae_callback_update',
    'filename' => 'callback.php',
  ),
  'zip' => 
  array (
    'callback' => '_vae_callback_zip',
    'filename' => 'callback.php',
  ),
  'store_add_to_cart' => 
  array (
    'callback' => '_vae_store_callback_add_to_cart',
  ),
  'store_address_delete' => 
  array (
    'callback' => '_vae_store_callback_address_delete',
  ),
  'store_address_select' => 
  array (
    'callback' => '_vae_store_callback_address_select',
  ),
  'store_cart' => 
  array (
    'callback' => '_vae_store_callback_cart',
  ),
  'store_checkout' => 
  array (
    'callback' => '_vae_store_callback_checkout',
  ),
  'store_currency_select' => 
  array (
    'callback' => '_vae_store_callback_currency_select',
  ),
  'store_discount' => 
  array (
    'callback' => '_vae_store_callback_discount',
  ),
  'store_forgot' => 
  array (
    'callback' => '_vae_store_callback_forgot',
  ),
  'store_google_checkout' => 
  array (
    'callback' => '_vae_store_callback_google_checkout',
  ),
  'store_login' => 
  array (
    'callback' => '_vae_store_callback_login',
  ),
  'store_logout' => 
  array (
    'callback' => '_vae_store_callback_logout',
  ),
  'store_paypal_checkout' => 
  array (
    'callback' => '_vae_store_callback_paypal_checkout',
  ),
  'store_paypal_express_checkout' => 
  array (
    'callback' => '_vae_store_callback_paypal_express_checkout',
  ),
  'store_payment_methods_select' => 
  array (
    'callback' => '_vae_store_callback_payment_methods_select',
  ),
  'store_register' => 
  array (
    'callback' => '_vae_store_callback_register',
  ),
  'store_shipping_methods_select' => 
  array (
    'callback' => '_vae_store_callback_shipping_methods_select',
  ),
  'users_forgot' => 
  array (
    'callback' => '_vae_users_callback_forgot',
    'filename' => 'users.php',
  ),
  'users_login' => 
  array (
    'callback' => '_vae_users_callback_login',
    'filename' => 'users.php',
  ),
  'users_logout' => 
  array (
    'callback' => '_vae_users_callback_logout',
    'filename' => 'users.php',
  ),
  'users_register' => 
  array (
    'callback' => '_vae_users_callback_register',
    'filename' => 'users.php',
  ),
);

$_VAE['form_items'] = array (
  'captcha' => 1,
  'checkbox' => 1,
  'country_select' => 1,
  'date_select' => 1,
  'file_field' => 1,
  'hidden_field' => 1,
  'option_select' => 1,
  'password_field' => 1,
  'select' => 1,
  'state_select' => 1,
  'text_area' => 1,
  'text_field' => 1,
  'store_address_select' => 1,
  'store_credit_card_select' => 1,
  'store_currency_select' => 1,
  'store_payment_methods_select' => 1,
  'store_shipping_methods_select' => 1,
);

?>