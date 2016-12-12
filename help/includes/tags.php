<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Справка по BBcode
echo '
<div class="phdr">
    <a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _t('bbCode Tags') . '
</div>
<div class="menu">
    <p>
    <table cellpadding="3" cellspacing="0">
        <tr>
            <td align="right">[php]...[/php]</td><td>' . _t('PHP code') . '</td>
            </tr>
        <tr>
            <td align="right">[url=http://site_url]<span style="color:blue">' . _t('Link name') . '</span>[/url]</td>
            <td><a href="#">' . _t('URL Link') . '</a></td>
        </tr>
        <tr>
            <td align="right">[b]...[/b]</td><td><b>' . _t('Bold') . '</b></td>
        </tr>
        <tr>
            <td align="right">[i]...[/i]</td>
            <td><i>' . _t('Italic') . '</i></td>
        </tr>
        <tr>
            <td align="right">[u]...[/u]</td>
            <td><u>' . _t('Underline') . '</u></td>
        </tr>
        <tr>
            <td align="right">[s]...[/s]</td>
            <td><strike>' . _t('Strike') . '</strike></td>
        </tr>
        <tr>
            <td align="right">[red]...[/red]</td>
            <td><span style="color:red">' . _t('Red') . '</span></td>
        </tr>
        <tr>
            <td align="right">[green]...[/green]</td>
            <td><span style="color:green">' . _t('Green') . '</span></td>
        </tr>
        <tr>
            <td align="right">[blue]...[/blue]</td>
            <td><span style="color:blue">' . _t('Blue') . '</span></td>
        </tr>
        <tr>
            <td align="right">[color=]...[/color]</td>
            <td>' . _t('Text Color') . '</td>
        </tr>
        <tr>
            <td align="right">[bg=][/bg]</td>
            <td>' . _t('Background Color') . '</td>
        </tr>
        <tr>
            <td align="right">[c]...[/c]</td>
            <td><span class="quote">' . _t('Quote') . '</span></td>
        </tr>
        <tr>
            <td align="right" valign="top">[*]...[/*]</td>
            <td><span class="bblist">' . _t('Bulleted list') . '</span></td>
        </tr>
        <tr>
            <td align="right">[spoiler=' . _t('Title') . ']' . _t('Text') . '[/spoiler]</td>
            <td>' . _t('Spoiler') . '</td>
        </tr>
    </table>
    </p>
</div>
<div class="phdr">
    <a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a>
</div>';
