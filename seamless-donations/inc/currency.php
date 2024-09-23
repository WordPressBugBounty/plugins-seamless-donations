<?php
/*
Seamless Donations by David Gewirtz, adopted from Allen Snook

Lab Notes: http://zatzlabs.com/lab-notes/
Plugin Page: http://zatzlabs.com/seamless-donations/
Contact: http://zatzlabs.com/contact-us/

Copyright (c) 2015-2022 by David Gewirtz
*/

// Exit if .php file accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function seamless_donations_get_currencies() {
	// https://stripe.com/docs/currencies
	// https://en.wikipedia.org/wiki/List_of_circulating_currencies
	// https://gist.github.com/Gibbs/3920259
	// https://html-css-js.com/html/character-codes/currency/
	// https://www.toptal.com/designers/htmlarrows/currency/
	$master_currency_array = array(
		'AED' => array(
			'name'     => 'United Arab Emirates Dirham',
			'symbol'   => '&#1583;.&#1573;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'AFN' => array(
			'name'     => 'Afghan Afghani',
			'symbol'   => '&#65;&#102;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ALL' => array(
			'name'     => 'Albanian Lek',
			'symbol'   => 'L',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'AMD' => array(
			'name'     => 'Armenian Dram',
			'symbol'   => '&#1423; &#x58F;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ANG' => array(
			'name'     => 'Netherlands Antillean Guilder',
			'symbol'   => '&#402;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'AOA' => array(
			'name'     => 'Angolan Kwanza',
			'symbol'   => 'Kz',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ARS' => array(
			'name'     => 'Argentine Peso',
			'symbol'   => '$',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'AUD' => array(
			'name'     => 'Australian Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'AWG' => array(
			'name'     => 'Aruban Florin',
			'symbol'   => '&#402;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'AZN' => array(
			'name'     => 'Azerbaijani Manat',
			'symbol'   => '&#1084;&#1072;&#1085;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BAM' => array(
			'name'     => 'Bosnia and Herzegovina Convertible Mark',
			'symbol'   => '&#75;&#77;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BBD' => array(
			'name'     => 'Barbadian Dollar',
			'symbol'   => ' &#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BDT' => array(
			'name'     => 'Bangladeshi Taka',
			'symbol'   => '&#2547;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BGN' => array(
			'name'     => 'Bulgarian Lev',
			'symbol'   => ' &#1083;&#1074;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BIF' => array(
			'name'     => 'Burundian Franc',
			'symbol'   => '&#70;&#66;&#117;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BMD' => array(
			'name'     => 'Bermudian Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BND' => array(
			'name'     => 'Brunei Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BOB' => array(
			'name'     => 'Bolivian Boliviano',
			'symbol'   => '&#36;&#98;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BRL' => array(
			'name'     => 'Brazilian Real',
			'symbol'   => 'R$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'BSD' => array(
			'name'     => 'Bahamian Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BWP' => array(
			'name'     => 'Botswana Pula',
			'symbol'   => '&#80;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'BZD' => array(
			'name'     => 'Belize Dollar',
			'symbol'   => '&#66;&#90;&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CAD' => array(
			'name'     => 'Canadian Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'CDF' => array(
			'name'     => 'Congolese Franc',
			'symbol'   => '&#70;&#67;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CHF' => array(
			'name'     => 'Swiss Franc',
			'symbol'   => 'CHF',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'CLP' => array(
			'name'     => 'Chilean Peso',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CNY' => array(
			'name'     => 'China Yuan Renminbi',
			'symbol'   => '&#165;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'COP' => array(
			'name'     => 'Colombia Peso',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CRC' => array(
			'name'     => 'Costa Rica Colon',
			'symbol'   => '&#8353;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CVE' => array(
			'name'     => 'Cape Verdean Escudo',
			'symbol'   => '&#36;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'CZK' => array(
			'name'     => 'Czech Koruna',
			'symbol'   => 'Kc',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'DJF' => array(
			'name'     => 'Djiboutian Franc',
			'symbol'   => '&#70;&#100;&#106;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'DKK' => array(
			'name'     => 'Danish Krone',
			'symbol'   => 'kr',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'DOP' => array(
			'name'     => 'Dominican Republic Peso',
			'symbol'   => '&#82;&#68;&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'DZD' => array(
			'name'     => 'Algerian dinar',
			'symbol'   => '&#1583;&#1580;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'EGP' => array(
			'name'     => 'Egypt Pound',
			'symbol'   => '&#163;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ETB' => array(
			'name'     => 'Ethiopian Birr',
			'symbol'   => '&#66;&#114;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'EUR' => array(
			'name'     => 'Euro',
			'symbol'   => ' & euro;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'FJD' => array(
			'name'     => 'Fiji Dollar',
			'symbol'   => ' &#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'FKP' => array(
			'name'     => 'Falkland Islands Pound',
			'symbol'   => '&#163;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GBP' => array(
			'name'     => 'Pound Sterling',
			'symbol'   => '&pound;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'GEL' => array(
			'name'     => 'Georgian Lari',
			'symbol'   => '&#4314;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GIP' => array(
			'name'     => 'Gibraltar Pound',
			'symbol'   => '&#163;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GMD' => array(
			'name'     => 'Gambian Dalasi',
			'symbol'   => '&#68;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GNF' => array(
			'name'     => 'Guinean Franc',
			'symbol'   => '&#70;&#71;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GTQ' => array(
			'name'     => 'Guatemala Quetzal',
			'symbol'   => '&#81;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'GYD' => array(
			'name'     => 'Guyana Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'HKD' => array(
			'name'     => 'Hong Kong Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'HNL' => array(
			'name'     => 'Honduras Lempira',
			'symbol'   => '&#76;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'HRK' => array(
			'name'     => 'Croatian Kuna',
			'symbol'   => '&#107;&#110;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'HTG' => array(
			'name'     => 'Haitian Gourde',
			'symbol'   => '&#71;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'HUF' => array(
			'name'     => 'Hungarian Forint',
			'symbol'   => 'Ft',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'IDR' => array(
			'name'     => 'Indonesia Rupiah',
			'symbol'   => '&#82;&#112;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'INR' => array(
			'name'     => 'Indian Rupee',
			'symbol'   => '&#8377;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'ISK' => array(
			'name'     => 'Iceland Krona',
			'symbol'   => 'kr',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ILS' => array(
			'name'     => 'Israeli New Sheqel',
			'symbol'   => '&#8362;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'JMD' => array(
			'name'     => 'Jamaica Dollar',
			'symbol'   => 'J$',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'JPY' => array(
			'name'     => 'Japanese Yen',
			'symbol'   => '&yen;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'KES' => array(
			'name'     => 'Kenyan Shilling',
			'symbol'   => '&#75;&#83;&#104;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KGS' => array(
			'name'     => 'Kyrgyzstan Som',
			'symbol'   => '&#1083;&#1074;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KHR' => array(
			'name'     => 'Cambodian Riel',
			'symbol'   => '&#6107;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KMF' => array(
			'name'     => 'Comorian Franc',
			'symbol'   => '&#67;&#70;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KRW' => array(
			'name'     => 'Korea Won',
			'symbol'   => '&#8361;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KYD' => array(
			'name'     => 'Cayman Islands Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'KZT' => array(
			'name'     => 'Kazakhstan Tenge',
			'symbol'   => '&#1083;&#1074;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'LAK' => array(
			'name'     => 'Laos Kip',
			'symbol'   => '&#8365;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'LBP' => array(
			'name'     => 'Lebanon Pound',
			'symbol'   => '&#163;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'LKR' => array(
			'name'     => 'Sri Lankan Rupee',
			'symbol'   => '&#8360;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'LRD' => array(
			'name'     => 'Liberia Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'LSL' => array(
			'name'     => 'Lesotho Loti',
			'symbol'   => '&#76;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MAD' => array(
			'name'     => 'Moroccan Dirham',
			'symbol'   => '&#1583;.&#1605;.',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MDL' => array(
			'name'     => 'Moldovan Leu',
			'symbol'   => '&#76;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MGA' => array(
			'name'     => 'Malagasy Ariary',
			'symbol'   => '&#65;&#114;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MKD' => array(
			'name'     => 'Macedonia Denar',
			'symbol'   => '&#1076;&#1077;&#1085;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MMK' => array(
			'name'     => 'Burmese Kyat',
			'symbol'   => '&#75;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MNT' => array(
			'name'     => 'Mongolia Tughrik',
			'symbol'   => '&#8366;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MOP' => array(
			'name'     => 'Macanese Pataca',
			'symbol'   => '&#77;&#79;&#80;&#36;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MRO' => array(
			'name'     => 'Mauritanian Ouguiya',
			'symbol'   => '&#85;&#77;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MUR' => array(
			'name'     => 'Mauritius Rupee',
			'symbol'   => '&#8360;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MVR' => array(
			'name'     => 'Maldivian Rufiyaa',
			'symbol'   => '.&#1923;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MWK' => array(
			'name'     => 'Malawian Kwacha',
			'symbol'   => '&#77;&#75;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'MYR' => array(
			'name'     => 'Malaysian Ringgit',
			'symbol'   => 'RM',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'MXN' => array(
			'name'     => 'Mexican Peso',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'MZN' => array(
			'name'     => 'Mozambique Metical',
			'symbol'   => '&#77;&#84;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'NAD' => array(
			'name'     => 'Namibia Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'NGN' => array(
			'name'     => 'Nigerian Naira',
			'symbol'   => '&#8358;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'NIO' => array(
			'name'     => 'Nicaragua Cordoba',
			'symbol'   => '&#67;&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'NOK' => array(
			'name'     => 'Norwegian Krone',
			'symbol'   => 'kr',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'NPR' => array(
			'name'     => 'Nepalese Rupee',
			'symbol'   => '&#8360;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'NZD' => array(
			'name'     => 'New Zealand Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'PAB' => array(
			'name'     => 'Panama Balboa',
			'symbol'   => '&#66;&#47;&#46;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'PEN' => array(
			'name'     => 'Peru Sol',
			'symbol'   => '&#83;&#47;&#46;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'PGK' => array(
			'name'     => 'Papua New Guinean Kina',
			'symbol'   => '&#75;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'PHP' => array(
			'name'     => 'Philippine Peso',
			'symbol'   => '&#8369;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'PKR' => array(
			'name'     => 'Pakistani Rupee',
			'symbol'   => '&#8360;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'PLN' => array(
			'name'     => 'Polish Zloty',
			'symbol'   => '&#122;&#322;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'PYG' => array(
			'name'     => 'Paraguay Guarani',
			'symbol'   => '&#71;&#115;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'QAR' => array(
			'name'     => 'Qatar Riyal',
			'symbol'   => '&#65020;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'RON' => array(
			'name'     => 'Romania New Leu',
			'symbol'   => 'lei',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'RSD' => array(
			'name'     => 'Serbia Dinar',
			'symbol'   => '&#1044;&#1080;&#1085;&#46;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'RUB' => array(
			'name'     => 'Russian Ruble',
			'symbol'   => '&#8381;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'RWF' => array(
			'name'     => 'Rwandan Franc',
			'symbol'   => '&#1585;.&#1587;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SAR' => array(
			'name'     => 'Saudi Arabia Riyal',
			'symbol'   => '&#65020;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SBD' => array(
			'name'     => 'Solomon Islands Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SCR' => array(
			'name'     => 'Seychelles Rupee',
			'symbol'   => '&#8360;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SGD' => array(
			'name'     => 'Singapore Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'SEK' => array(
			'name'     => 'Swedish Krona',
			'symbol'   => 'kr',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'SHP' => array(
			'name'     => 'Saint Helena Pound',
			'symbol'   => '&#163;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SLL' => array(
			'name'     => 'Sierra Leonean Leone',
			'symbol'   => '&#76;&#101;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SOS' => array(
			'name'     => 'Somalia Shilling',
			'symbol'   => '&#83;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SRD' => array(
			'name'     => 'Suriname Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'STD' => array(
			'name'     => 'Surinamese Dollar',
			'symbol'   => '&#68;&#98;',
			// ?',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'SZL' => array(
			'name'     => 'Taiwan New Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'THB' => array(
			'name'     => 'Thai Baht',
			'symbol'   => '&#3647;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'TJS' => array(
			'name'     => 'Tajikistani Somoni',
			'symbol'   => 'SM',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'TOP' => array(
			'name'     => 'Tongan Paʻanga',
			'symbol'   => '&#84;&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'TTD' => array(
			'name'     => 'Trinidad and Tobago Dollar',
			'symbol'   => 'TT$',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'TRY' => array(
			'name'     => 'Turkish Lira',
			'symbol'   => '&#8378;',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'TZS' => array(
			'name'     => 'Tanzanian Shilling',
			'symbol'   => '',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'UAH' => array(
			'name'     => 'Ukraine Hryvnia',
			'symbol'   => '&#8372;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'UGX' => array(
			'name'     => 'Ugandan Shilling',
			'symbol'   => '&#85;&#83;&#104;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'USD' => array(
			'name'     => 'U.S. Dollar',
			'symbol'   => '$',
			'gateways' => array(
				'PAYPAL' => true,
				'STRIPE' => true,
			),
		),
		'UYU' => array(
			'name'     => 'Uruguay Peso',
			'symbol'   => '&#36;&#85;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'UZS' => array(
			'name'     => 'Uzbekistan Som',
			'symbol'   => '&#1083;&#1074;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'VND' => array(
			'name'     => 'Vietnam Dong',
			'symbol'   => '&#8363;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'VUV' => array(
			'name'     => 'Vanuatu Vatu',
			'symbol'   => '&#86;&#84;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'WST' => array(
			'name'     => 'Samoan Tālā',
			'symbol'   => '&#87;&#83;&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'XAF' => array(
			'name'     => 'Central African Franc',
			'symbol'   => '&#70;&#67;&#70;&#65;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'XCD' => array(
			'name'     => 'Eastern Caribbean Dollar',
			'symbol'   => '&#36;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'XOF' => array(
			'name'     => 'West African Franc',
			'symbol'   => '',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'XPF' => array(
			'name'     => 'CFP Franc',
			'symbol'   => '&#70;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'YER' => array(
			'name'     => 'Yemeni Rial',
			'symbol'   => '&#65020;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ZAR' => array(
			'name'     => 'South African Rand',
			'symbol'   => '&#82;',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
		'ZMW' => array(
			'name'     => 'Zambian Kwacha',
			'symbol'   => 'ZK',
			'gateways' => array(
				'STRIPE' => true,
			),
		),
	);

	$payment_gateway = trim( get_option( 'dgx_donate_payment_processor_choice' ) );

	$currencies = array();
	foreach ( $master_currency_array as $currency_code => $currency_details ) {
		$currency_gateways = $currency_details['gateways'];
		if ( isset( $currency_gateways[ $payment_gateway ] ) ) {
			$currencies[ $currency_code ]['name']   = $currency_details['name'];
			$currencies[ $currency_code ]['symbol'] = $currency_details['symbol'];
		}
	}

	return $currencies;
}

function dgx_donate_get_currencies() {
	$extended_currencies = get_option( 'dgx_donate_currency_extend' );

	if ( $extended_currencies == 'on' ) {
		$currencies = seamless_donations_get_currencies();
	} else {
		$currencies = array(
			'AUD' => array(
				'name'   => 'Australian Dollar',
				'symbol' => '$',
			),
			'BRL' => array(
				'name'   => 'Brazilian Real',
				'symbol' => 'R$',
			),
			'CAD' => array(
				'name'   => 'Canadian Dollar',
				'symbol' => '$',
			),
			'CZK' => array(
				'name'   => 'Czech Koruna',
				'symbol' => 'Kc',
			),
			'DKK' => array(
				'name'   => 'Danish Krone',
				'symbol' => 'kr',
			),
			'EUR' => array(
				'name'   => 'Euro',
				'symbol' => '&euro;',
			),
			'HKD' => array(
				'name'   => 'Hong Kong Dollar',
				'symbol' => '$',
			),
			'HUF' => array(
				'name'   => 'Hungarian Forint',
				'symbol' => 'Ft',
			),
			'INR' => array(
				'name'   => 'Indian Rupee',
				'symbol' => '&#8377;',
			),
			'ILS' => array(
				'name'   => 'Israeli New Sheqel',
				'symbol' => '&#8362;',
			),
			'JPY' => array(
				'name'   => 'Japanese Yen',
				'symbol' => '&yen;',
			),
			'MYR' => array(
				'name'   => 'Malaysian Ringgit',
				'symbol' => 'RM',
			),
			'MXN' => array(
				'name'   => 'Mexican Peso',
				'symbol' => '$',
			),
			'NOK' => array(
				'name'   => 'Norwegian Krone',
				'symbol' => 'kr',
			),
			'NZD' => array(
				'name'   => 'New Zealand Dollar',
				'symbol' => '$',
			),
			'PHP' => array(
				'name'   => 'Philippine Peso',
				'symbol' => '&#8369;',
			),
			'PLN' => array(
				'name'   => 'Polish Zloty',
				'symbol' => '&#122;&#322;',
			),
			'GBP' => array(
				'name'   => 'Pound Sterling',
				'symbol' => '&pound;',
			),
			'RUB' => array(
				'name'   => 'Russian Ruble',
				'symbol' => '&#8381;',
			),
			'SGD' => array(
				'name'   => 'Singapore Dollar',
				'symbol' => '$',
			),
			'SEK' => array(
				'name'   => 'Swedish Krona',
				'symbol' => 'kr',
			),
			'CHF' => array(
				'name'   => 'Swiss Franc',
				'symbol' => 'CHF',
			),
			'TWD' => array(
				'name'   => 'Taiwan New Dollar',
				'symbol' => '$',
			),
			'THB' => array(
				'name'   => 'Thai Baht',
				'symbol' => '&#3647;',
			),
			'TRY' => array(
				'name'   => 'Turkish Lira',
				'symbol' => '&#8378;',
			),
			'USD' => array(
				'name'   => 'U.S. Dollar',
				'symbol' => '$',
			),
		);
	}

	return $currencies;
}

// builds a simple array of currency_symbol => currency_name
function dgx_donate_get_currency_list() {
	$currencies    = dgx_donate_get_currencies();
	$currency_list = array();
	foreach ( $currencies as $currency_code => $currency_details ) {
		$currency_list[ $currency_code ] = $currency_details['name'];
	}

	return $currency_list;
}

/*
 * From https://developer.paypal.com/docs/classic/api/currency_codes/
 */
function dgx_donate_get_currency_selector( $select_name, $select_initial_value ) {
	$output = "<select id='" . esc_attr( $select_name ) . "' name='" . esc_attr( $select_name ) . "'>";

	$currencies = dgx_donate_get_currencies();

	foreach ( $currencies as $currency_code => $currency_details ) {
		$selected = '';
		if ( strcasecmp( $select_initial_value, $currency_code ) == 0 ) {
			$selected = ' selected ';
		}
		$output .= "<option value='" . esc_attr( $currency_code ) . "'" . esc_attr( $selected ) . '>' .
				   esc_html( $currency_details['name'] ) . '</option>';
	}

	$output .= '</select>';

	return $output;
}

function dgx_donate_get_escaped_formatted_amount( $amount, $decimal_places = 2, $currency_code = '' ) {
	if ( empty( $currency_code ) ) {
		$currency_code = get_option( 'dgx_donate_currency' );
	}

	$currencies      = dgx_donate_get_currencies();
	$currency        = $currencies[ $currency_code ];
	$currency_symbol = $currency['symbol'];

	return $currency_symbol . esc_html( number_format( $amount, $decimal_places ) );
}

function dgx_donate_get_plain_formatted_amount(
	$amount, $decimal_places = 2, $currency_code = '', $append_currency_code = false
) {
	if ( empty( $currency_code ) ) {
		$currency_code = get_option( 'dgx_donate_currency' );
	}

	$formatted_amount = number_format( $amount, $decimal_places );
	if ( $append_currency_code ) {
		$formatted_amount .= ' (' . $currency_code . ')';
	}

	return $formatted_amount;
}

function dgx_donate_get_donation_currency_code( $donation_id ) {
	/*
	 gets the currency code for the donation */
	/* updates donations without one (pre version 2.8.1) as USD */
	$currency_code = get_post_meta( $donation_id, '_dgx_donate_donation_currency', true );
	if ( empty( $currency_code ) ) {
		$currency_code = get_option( 'dgx_donate_currency' );
		if ( $currency_code == false ) {
			$currency_code = 'USD';
		}
		update_post_meta( $donation_id, '_dgx_donate_donation_currency', $currency_code );
	}

	return $currency_code;
}
