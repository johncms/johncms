/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

// Must stay first: both libraries below register their jQuery plugins while they
// are evaluated, and only find jQuery if it has already been published on window.
import './globals';
import flatpickr from 'flatpickr';
import 'bootstrap';

// The flatpickr locale files are loaded as plain <script> tags and extend window.flatpickr.l10ns.
window.flatpickr = flatpickr;
