<?php

namespace Drupal\twilio\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default controller for the twilio module.
 */
class TwilioController extends ControllerBase {


  /**
   * The RequestStack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * TwilioController Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The requestStack service.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
    );
  }

  /**
   * Handle incoming status updates.
   */
  public function receiveStatus() {
    if (!empty($this->requestStack->getCurrentRequest()->request->get('CallSid'))) {
      $this->moduleHandler()->invokeAll('twilio_status', $this->requestStack->getCurrentRequest()->request);
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Twilio status'),
    ];
  }

  /**
   * Handle incoming SMS message requests.
   *
   * @todo Needs Work.
   */
  public function receiveMessage() {
    if (
      !empty($this->requestStack->getCurrentRequest()->request->get('From')) &&
      !empty($this->requestStack->getCurrentRequest()->request->get('Body')) &&
      !empty($this->requestStack->getCurrentRequest()->request->get('ToCountry')) &&
      twilio_command('validate')
    ) {
      $codes = $this->countryDialCodes();
      $dial_code = $this->countryIsoToDialCodes($this->requestStack->getCurrentRequest()->request->get('ToCountry'));
      if (empty($codes[$dial_code])) {
        $this->logger('Twilio')->notice(
          'A message was blocked from the country @country, due to your currrent country code settings.',
          ['@country' => $this->requestStack->getCurrentRequest()->request->get('ToCountry')]
        );
        return;
      }
      $number = SafeMarkup::checkPlain(str_replace('+' . $dial_code, '', $this->requestStack->getCurrentRequest()->request->get('From')));

      $number_twilio = !empty($this->requestStack->getCurrentRequest()->request->get('To')) ?
        SafeMarkup::checkPlain(str_replace('+', '', $this->requestStack->getCurrentRequest()->request->get('To'))) : '';

      $message = SafeMarkup::checkPlain(
        htmlspecialchars_decode($this->requestStack->getCurrentRequest()->request->get('Body'), ENT_QUOTES)
      );
      // @todo Support more than one media entry.
      $media = !empty($this->requestStack->getCurrentRequest()->request->get('MediaUrl0')) ?
        $this->requestStack->getCurrentRequest()->request->get('MediaUrl0') : '';

      $options = [];
      // Add the receiver to the options array.
      if (!empty($this->requestStack->getCurrentRequest()->request->get('To'))) {
        $options['receiver'] = SafeMarkup::checkPlain($this->requestStack->getCurrentRequest()->request->get('To'));
      }
      $this->logger('Twilio')->notice('An SMS message was sent from @number containing the message "@message"', [
        '@number' => $number,
        '@message' => $message,
      ]);
      $this->messageIncoming($number, $number_twilio, $message, $media, $options);
    }
  }

  /**
   * Handle incoming voice requests.
   *
   * @todo Needs Work.
   */
  public function receiveVoice() {
    if (
      !empty($this->requestStack->getCurrentRequest()->request->get('From')) &&
      twilio_command('validate', ['type' => 'voice'])
    ) {
      $number = SafeMarkup::checkPlain(
        str_replace('+1', '', $this->requestStack->getCurrentRequest()->request->get('From'))
      );
      $number_twilio = !empty($this->requestStack->getCurrentRequest()->request->get('To')) ?
        SafeMarkup::checkPlain(str_replace('+', '', $this->requestStack->getCurrentRequest()->request->get('To'))) : '';

      $options = [];
      if (!empty($this->requestStack->getCurrentRequest()->request->get('To'))) {
        $options['receiver'] = SafeMarkup::checkPlain($this->requestStack->getCurrentRequest()->request->get('To'));
      }
      $this->logger('Twilio')->notice('A voice call from @number was received.', ['@number' => $number]);
      $this->voiceIncoming($number, $number_twilio, $options);
    }
  }

  /**
   * Invokes twilio_message_incoming hook.
   *
   * @param string $number
   *   The sender's mobile number.
   * @param string $number_twilio
   *   The twilio recipient number.
   * @param string $message
   *   The content of the text message.
   * @param array $media
   *   The absolute media url for a media file attatched to the message.
   * @param array $options
   *   Options array.
   */
  public function messageIncoming($number, $number_twilio, $message, array $media = [], array $options = []) {
    // Build our SMS array to be used by our hook and rules event.
    $sms = [
      'number' => $number,
      'number_twilio' => $number_twilio,
      'message' => $message,
      'media' => $media,
    ];
    // Invoke a hook for the incoming message so other modules can do things.
    $params = [$sms, $options];
    $this->moduleHandler()->invokeAll('twilio_message_incoming', $params);
    if ($this->moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('twilio_message_incoming', $sms);
    }
  }

  /**
   * Invokes twilio_voice_incoming hook.
   *
   * @param string $number
   *   The sender's mobile number.
   * @param string $number_twilio
   *   The twilio recipient number.
   * @param array $options
   *   Options array.
   */
  public function voiceIncoming($number, $number_twilio, array $options = []) {
    $voice = [
      'number' => $number,
      'number_twilio' => $number_twilio,
    ];
    // Invoke a hook for the incoming message so other modules can do things.
    $params = [$voice, $options];
    $this->moduleHandler()->invokeAll('twilio_voice_incoming', $params);
    if ($this->moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('twilio_voice_incoming', $voice);
    }
  }

  /**
   * Returns an array of E.164 international country calling codes.
   *
   * @param bool $all
   *   Boolean - If all possible options should be returned.
   *
   * @return array
   *   Associative array of country calling codes and country names.
   */
  public static function countryDialCodes($all = FALSE) {
    $codes = [
      1 => "USA / Canada / Dominican Rep. / Puerto Rico (1)",
      93 => "Afghanistan (93)",
      355 => "Albania (355)",
      213 => "Algeria (213)",
      376 => "Andorra (376)",
      244 => "Angola (244)",
      1264 => "Anguilla (1264)",
      1268 => "Antigua & Barbuda (1268)",
      54 => "Argentina (54)",
      374 => "Armenia (374)",
      297 => "Aruba (297)",
      61 => "Australia (61)",
      43 => "Austria (43)",
      994 => "Azerbaijan (994)",
      1242 => "Bahamas (1242)",
      973 => "Bahrain (973)",
      880 => "Bangladesh (880)",
      1246 => "Barbados (1246)",
      375 => "Belarus (375)",
      32 => "Belgium (32)",
      501 => "Belize (501)",
      229 => "Benin (229)",
      1441 => "Bermuda (1441)",
      975 => "Bhutan (975)",
      591 => "Bolivia (591)",
      387 => "Bosnia-Herzegovina (387)",
      267 => "Botswana (267)",
      55 => "Brazil (55)",
      1284 => "British Virgin Islands (1284)",
      673 => "Brunei (673)",
      359 => "Bulgaria (359)",
      226 => "Burkina Faso (226)",
      257 => "Burundi (257)",
      855 => "Cambodia (855)",
      237 => "Cameroon (237)",
      34 => "Canary Islands (34)",
      238 => "Cape Verde (238)",
      1345 => "Cayman Islands (1345)",
      236 => "Central African Republic (236)",
      235 => "Chad (235)",
      56 => "Chile (56)",
      86 => "China (86)",
      57 => "Colombia (57)",
      269 => "Comoros (269)",
      242 => "Congo (242)",
      243 => "Democratic Republic Congo (243)",
      682 => "Cook Islands (682)",
      385 => "Croatia (385)",
      53 => "Cuba (53)",
      357 => "Cyprus (357)",
      420 => "Czech Republic (420)",
      45 => "Denmark (45)",
      253 => "Djibouti (253)",
      1767 => "Dominica (1767)",
      670 => "East Timor (670)",
      593 => "Ecuador (593)",
      20 => "Egypt (20)",
      503 => "El Salvador (503)",
      240 => "Equatorial Guinea (240)",
      372 => "Estonia (372)",
      251 => "Ethiopia (251)",
      500 => "Falkland Islands (500)",
      298 => "Faroe Islands (298)",
      679 => "Fiji (679)",
      358 => "Finland (358)",
      33 => "France (33)",
      594 => "French Guiana (594)",
      689 => "French Polynesia (689)",
      241 => "Gabon (241)",
      220 => "Gambia (220)",
      995 => "Georgia (995)",
      49 => "Germany (49)",
      233 => "Ghana (233)",
      350 => "Gibraltar (350)",
      881 => "Global Mobile Satellite (881)",
      30 => "Greece (30)",
      299 => "Greenland (299)",
      1473 => "Grenada (1473)",
      590 => "Guadeloupe (590)",
      1671 => "Guam (1671)",
      502 => "Guatemala (502)",
      224 => "Guinea (224)",
      592 => "Guyana (592)",
      509 => "Haiti (509)",
      504 => "Honduras (504)",
      852 => "HongKong (852)",
      36 => "Hungary (36)",
      354 => "Iceland (354)",
      91 => "India (91)",
      62 => "Indonesia (62)",
      98 => "Iran (98)",
      964 => "Iraq (964)",
      353 => "Ireland (353)",
      972 => "Israel (972)",
      39 => "Italy / Vatican City State (39)",
      225 => "Ivory Coast (225)",
      1876 => "Jamaica (1876)",
      81 => "Japan (81)",
      962 => "Jordan (962)",
      254 => "Kenya (254)",
      82 => "Korea (South) (82)",
      965 => "Kuwait (965)",
      996 => "Kyrgyzstan (996)",
      856 => "Lao (856)",
      371 => "Latvia (371)",
      961 => "Lebanon (961)",
      266 => "Lesotho (266)",
      231 => "Liberia (231)",
      218 => "Libya (218)",
      423 => "Liechtenstein (423)",
      370 => "Lithuania (370)",
      352 => "Luxembourg (352)",
      853 => "Macau (853)",
      389 => "Macedonia (389)",
      261 => "Madagascar (261)",
      265 => "Malawi (265)",
      60 => "Malaysia (60)",
      960 => "Maldives (960)",
      223 => "Mali (223)",
      356 => "Malta (356)",
      596 => "Martinique (596)",
      222 => "Mauritania (222)",
      230 => "Mauritius (230)",
      269 => "Mayotte Island (Comoros) (269)",
      52 => "Mexico (52)",
      373 => "Moldova (373)",
      377 => "Monaco (Kosovo) (377)",
      976 => "Mongolia (976)",
      382 => "Montenegro (382)",
      1664 => "Montserrat (1664)",
      212 => "Morocco (212)",
      258 => "Mozambique (258)",
      95 => "Myanmar (95)",
      264 => "Namibia (264)",
      977 => "Nepal (977)",
      31 => "Netherlands (31)",
      599 => "Netherlands Antilles (599)",
      687 => "New Caledonia (687)",
      64 => "New Zealand (64)",
      505 => "Nicaragua (505)",
      227 => "Niger (227)",
      234 => "Nigeria (234)",
      47 => "Norway (47)",
      968 => "Oman (968)",
      92 => "Pakistan (92)",
      970 => "Palestine (+970)",
      9725 => "Palestine (+9725)",
      507 => "Panama (507)",
      675 => "Papua New Guinea (675)",
      595 => "Paraguay (595)",
      51 => "Peru (51)",
      63 => "Philippines (63)",
      48 => "Poland (48)",
      351 => "Portugal (351)",
      974 => "Qatar (974)",
      262 => "Reunion (262)",
      40 => "Romania (40)",
      7 => "Russia / Kazakhstan (7)",
      250 => "Rwanda (250)",
      1670 => "Saipan (1670)",
      1684 => "Samoa (American) (1684)",
      685 => "Samoa (Western) (685)",
      378 => "San Marino (378)",
      882 => "Satellite-Thuraya (882)",
      966 => "Saudi Arabia (966)",
      221 => "Senegal (221)",
      381 => "Serbia (381)",
      248 => "Seychelles (248)",
      232 => "Sierra Leone (232)",
      65 => "Singapore (65)",
      421 => "Slovakia (421)",
      386 => "Slovenia (386)",
      252 => "Somalia (252)",
      27 => "South Africa (27)",
      34 => "Spain (34)",
      94 => "Sri Lanka (94)",
      1869 => "St. Kitts And Nevis (1869)",
      1758 => "St. Lucia (1758)",
      1784 => "St. Vincent (1784)",
      249 => "Sudan (249)",
      597 => "Suriname (597)",
      268 => "Swaziland (268)",
      46 => "Sweden (46)",
      41 => "Switzerland (41)",
      963 => "Syria (963)",
      886 => "Taiwan (886)",
      992 => "Tajikistan (992)",
      255 => "Tanzania (255)",
      66 => "Thailand (66)",
      228 => "Togo (228)",
      676 => "Tonga Islands (676)",
      1868 => "Trinidad and Tobago (1868)",
      216 => "Tunisia (216)",
      90 => "Turkey (90)",
      993 => "Turkmenistan (993)",
      1649 => "Turks and Caicos Islands (1649)",
      256 => "Uganda (256)",
      44 => "UK / Isle of Man / Jersey / Guernsey (44)",
      380 => "Ukraine (380)",
      971 => "United Arab Emirates (971)",
      598 => "Uruguay (598)",
      998 => "Uzbekistan (998)",
      678 => "Vanuatu (678)",
      58 => "Venezuela (58)",
      84 => "Vietnam (84)",
      967 => "Yemen (967)",
      260 => "Zambia (260)",
      255 => "Zanzibar (255)",
      263 => "Zimbabwe (263)",
    ];
    if ($all === TRUE) {
      return $codes;
    }
    // Get configured codes or return all.
    $settings = \Drupal::config('twilio.settings')->get('twilio_country_codes_container')['country_codes'] ?? [];

    $codes_to_return = array_filter($settings);

    if (empty($codes_to_return)) {
      return $codes;
    }
    else {
      return array_intersect_key($codes, $codes_to_return);
    }
  }

  /**
   * An array of country short codes with their calling codes.
   *
   * @param string $iso
   *   The two letter country code.
   *
   * @return string|array
   *   The dial code for the passed in ISO country code,
   *   OR the full array of ISO to dial codes.
   */
  public static function countryIsoToDialCodes(string $iso = NULL) {
    $codes = [
      'US' => 1,
      'CA' => 1,
      'DO' => 1,
      'PR' => 1,
      'AF' => 93,
      'AL' => 355,
      'DZ' => 213,
      'AD' => 376,
      'AO' => 244,
      'AI' => 1264,
      'AG' => 1268,
      'AR' => 54,
      'AM' => 374,
      'AW' => 297,
      'AU' => 61,
      'AT' => 43,
      'AZ' => 994,
      'BS' => 1242,
      'BH' => 973,
      'BD' => 880,
      'BB' => 1246,
      'BY' => 375,
      'BE' => 32,
      'BZ' => 501,
      'BJ' => 229,
      'BM' => 1441,
      'BT' => 975,
      'BO' => 591,
      'BA' => 387,
      'BW' => 267,
      'BR' => 55,
      'VG' => 1284,
      'BN' => 673,
      'BG' => 359,
      'BF' => 226,
      'BI' => 257,
      'KH' => 855,
      'CM' => 237,
      'IC' => 34,
      'CV' => 238,
      'KY' => 1345,
      'CF' => 236,
      'TD' => 235,
      'CL' => 56,
      'CN' => 86,
      'CO' => 57,
      'KM' => 269,
      'CG' => 242,
      'CD' => 243,
      'CK' => 682,
      'HR' => 385,
      'CU' => 53,
      'CY' => 357,
      'CZ' => 420,
      'DK' => 45,
      'DJ' => 253,
      'DM' => 1767,
      'EC' => 593,
      'EG' => 20,
      'SV' => 503,
      'GQ' => 240,
      'EE' => 372,
      'ET' => 251,
      'FK' => 500,
      'FO' => 298,
      'FJ' => 679,
      'FI' => 358,
      'FR' => 33,
      'GF' => 594,
      'PF' => 689,
      'GA' => 241,
      'GM' => 220,
      'GE' => 995,
      'DE' => 49,
      'GH' => 233,
      'GI' => 350,
      'GR' => 30,
      'GL' => 299,
      'GD' => 1473,
      'GP' => 590,
      'GU' => 1671,
      'GT' => 502,
      'GN' => 224,
      'GY' => 592,
      'HT' => 509,
      'HN' => 504,
      'HK' => 852,
      'HU' => 36,
      'IS' => 354,
      'IN' => 91,
      'ID' => 62,
      'IR' => 98,
      'IQ' => 964,
      'IE' => 353,
      'IL' => 972,
      'IT' => 39,
      'JM' => 1876,
      'JP' => 81,
      'JO' => 962,
      'KE' => 254,
      'KR' => 82,
      'KW' => 965,
      'KG' => 996,
      'LA' => 856,
      'LV' => 371,
      'LB' => 961,
      'LS' => 266,
      'LR' => 231,
      'LY' => 218,
      'LI' => 423,
      'LT' => 370,
      'LU' => 352,
      'MO' => 853,
      'MK' => 389,
      'MG' => 261,
      'MW' => 265,
      'MY' => 60,
      'MV' => 960,
      'ML' => 223,
      'MT' => 356,
      'MQ' => 596,
      'MR' => 222,
      'MU' => 230,
      'YT' => 269,
      'MX' => 52,
      'MD' => 373,
      'MC' => 377,
      'MN' => 976,
      'ME' => 382,
      'MS' => 1664,
      'MA' => 212,
      'MZ' => 258,
      'MM' => 95,
      'NA' => 264,
      'NP' => 977,
      'NL' => 31,
      'AN' => 599,
      'NC' => 687,
      'NZ' => 64,
      'NI' => 505,
      'NE' => 227,
      'NG' => 234,
      'NO' => 47,
      'OM' => 968,
      'PK' => 92,
      'PS' => 970,
      'PA' => 507,
      'PG' => 675,
      'PY' => 595,
      'PE' => 51,
      'PH' => 63,
      'PL' => 48,
      'PT' => 351,
      'QA' => 974,
      'RE' => 262,
      'RO' => 40,
      'RU' => 7,
      'RW' => 250,
      'WS' => 1684,
      'SM' => 378,
      'SA' => 966,
      'SN' => 221,
      'RS' => 381,
      'SC' => 248,
      'SL' => 232,
      'SG' => 65,
      'SK' => 421,
      'SI' => 386,
      'SO' => 252,
      'ZA' => 27,
      'ES' => 34,
      'LK' => 94,
      'SD' => 249,
      'SR' => 597,
      'SZ' => 268,
      'SE' => 46,
      'CH' => 41,
      'SY' => 963,
      'TW' => 886,
      'TJ' => 992,
      'TZ' => 255,
      'TH' => 66,
      'TG' => 228,
      'TO' => 676,
      'TT' => 1868,
      'TN' => 216,
      'TR' => 90,
      'TM' => 993,
      'TC' => 1649,
      'UG' => 256,
      'GB' => 44,
      'IM' => 44,
      'JE' => 44,
      'GG' => 44,
      'UA' => 380,
      'AE' => 971,
      'UY' => 598,
      'UZ' => 998,
      'VU' => 678,
      'VE' => 58,
      'VN' => 84,
      'YE' => 967,
      'ZM' => 260,
      'ZW' => 263,
    ];
    if ($iso) {
      return $codes[$iso];
    }
    return $codes;
  }

}
