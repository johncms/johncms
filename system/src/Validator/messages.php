<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    'ban'           => d__('system', 'You have a ban'),
    'captcha'       => d__('system', 'The security code is not correct'),
    'notSameSite'   => d__('system', 'The form submitted did not originate from the expected site'),
    'flood'         => d__('system', 'You cannot add the message so often. Please, wait %value% seconds.'),
    'modelNotFound' => d__('system', 'No record matching the input was found'),
    'modelExists'   => d__('system', 'A record matching the input was found'),

    'isEmpty'              => d__('system', 'Value is required and can\'t be empty'),
    'stringLengthTooShort' => d__('system', 'The input is less than %min% characters long'),
    'stringLengthTooLong'  => d__('system', 'The input is more than %max% characters long'),

    'notInArray' => d__('system', 'The input was not found in the haystack'),

    'emailAddressInvalid'          => d__('system', "Invalid type given. String expected"),
    'emailAddressInvalidFormat'    => d__('system', "The input is not a valid email address. Use the basic format local-part@hostname"),
    'emailAddressInvalidHostname'  => d__('system', "'%hostname%' is not a valid hostname for the email address"),
    'emailAddressInvalidMxRecord'  => d__('system', "'%hostname%' does not appear to have any valid MX or A records for the email address"),
    'emailAddressInvalidSegment'   => d__('system', "'%hostname%' is not in a routable network segment. The email address should not be resolved from public network"),
    'emailAddressDotAtom'          => d__('system', "'%localPart%' can not be matched against dot-atom format"),
    'emailAddressQuotedString'     => d__('system', "'%localPart%' can not be matched against quoted-string format"),
    'emailAddressInvalidLocalPart' => d__('system', "'%localPart%' is not a valid local part for the email address"),
    'emailAddressLengthExceeded'   => d__('system', "The input exceeds the allowed length"),
];
