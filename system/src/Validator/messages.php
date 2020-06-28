<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    'Ban' => [
        'ban' => d__('system', 'You have a ban'),
    ],

    'Captcha' => [
        'captcha' => d__('system', 'The security code is not correct'),
    ],

    'Csrf' => [
        'notSameSite' => d__('system', 'The form submitted did not originate from the expected site'),
    ],

    'Flood' => [
        'flood' => d__('system', 'You cannot add the message so often. Please, wait %value% seconds.'),
    ],

    'ModelNotExists' => [
        'modelExists' => d__('system', 'A record matching the input was found'),
    ],

    'ModelExists' => [
        'modelNotFound' => d__('system', 'No record matching the input was found'),
    ],

    'isEmpty' => [
        'isEmpty' => d__('system', 'Value is required and can\'t be empty'),
    ],

    'StringLength' => [
        'stringLengthTooShort' => d__('system', 'The input is less than %min% characters long'),
        'stringLengthTooLong'  => d__('system', 'The input is more than %max% characters long'),
    ],

    'InArray' => [
        'notInArray' => d__('system', 'The input was not found in the haystack'),
    ],

    'EmailAddress' => [
        'emailAddressInvalid'          => d__('system', 'Invalid type given. String expected'),
        'emailAddressInvalidFormat'    => d__('system', 'The input is not a valid email address. Use the basic format local-part@hostname'),
        'emailAddressInvalidHostname'  => d__('system', '\'%hostname%\' is not a valid hostname for the email address'),
        'emailAddressInvalidMxRecord'  => d__('system', '\'%hostname%\' does not appear to have any valid MX or A records for the email address'),
        'emailAddressInvalidSegment'   => d__('system', '\'%hostname%\' is not in a routable network segment. The email address should not be resolved from public network'),
        'emailAddressDotAtom'          => d__('system', '\'%localPart%\' can not be matched against dot-atom format'),
        'emailAddressQuotedString'     => d__('system', '\'%localPart%\' can not be matched against quoted-string format'),
        'emailAddressInvalidLocalPart' => d__('system', '\'%localPart%\' is not a valid local part for the email address'),
        'emailAddressLengthExceeded'   => d__('system', 'The input exceeds the allowed length'),

        'hostnameCannotDecodePunycode'  => d__('system', 'The input appears to be a DNS hostname but the given punycode notation cannot be decoded'),
        'hostnameInvalid'               => d__('system', 'Invalid type given. String expected'),
        'hostnameDashCharacter'         => d__('system', 'The input appears to be a DNS hostname but contains a dash in an invalid position'),
        'hostnameInvalidHostname'       => d__('system', 'The input does not match the expected structure for a DNS hostname'),
        'hostnameInvalidHostnameSchema' => d__('system', 'The input appears to be a DNS hostname but cannot match against hostname schema for TLD \'%tld%\''),
        'hostnameInvalidLocalName'      => d__('system', 'The input does not appear to be a valid local network name'),
        'hostnameInvalidUri'            => d__('system', 'The input does not appear to be a valid URI hostname'),
        'hostnameIpAddressNotAllowed'   => d__('system', 'The input appears to be an IP address, but IP addresses are not allowed'),
        'hostnameLocalNameNotAllowed'   => d__('system', 'The input appears to be a local network name but local network names are not allowed'),
        'hostnameUndecipherableTld'     => d__('system', 'The input appears to be a DNS hostname but cannot extract TLD part'),
        'hostnameUnknownTld'            => d__('system', 'The input appears to be a DNS hostname but cannot match TLD against known list'),
    ],

    'NotEmpty' => [
        'isEmpty'         => d__('system', 'Value is required and can\'t be empty'),
        'notEmptyInvalid' => d__('system', 'Invalid type given. String, integer, float, boolean or array expected'),
    ],
];
