/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

// Must stay first: it publishes jQuery, axios and lodash as globals.
import './bootstrap';
// Self-hosted fonts of the design (SIL OFL 1.1, see THIRD-PARTY-NOTICES.md). Every script is
// declared with its unicode-range, so a browser downloads only the ones a page uses.
// The optical size axis lets small text open up its spacing for the screen.
import '@fontsource-variable/inter/opsz.css';
import '@fontsource-variable/jetbrains-mono';
import '../scss/app.scss';

import './shell';
import './live-list';
import './tabs';
import './role-permissions';
import './modal';
import './offcanvas';
import './notifications';
import './language-panels';
import './mail-settings';
import './language-settings';
import './copy-button';
import './language-menu';
import './ad-form-preview';
import './main';
import './editor';
import './icon-gallery';

import './collection-tags';
