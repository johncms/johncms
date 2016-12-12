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

// Правила Форума
echo '<div class="phdr"><a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _t('Forum rules') . '</div>' .
    '<div class="menu"><p>';

// Стиль для списков
echo '<style>
ol {counter-reset: item; padding-left: 1em;}
ol > li {counter-increment: item;}
ol ol > li {display: block;}
ol ol > li:before {content: counters(item, ".") ". ";}
</style>';

// Правила Форума
echo '
<ol>
    <li><strong>' . _t('FORBIDDEN') . '</strong>
        <ol>
            <li>' . _t('Advertising messages') . '</li>
            <li>' . _t('The messages containing direct propagation of racial, national and religious animosity') . '</li>
            <li>' . _t('Any abusive, obscene, vulgar, slanderous, hateful, threatening and sexually-oriented messages or any other material that may violate any applicable laws') . '</li>
            <li>' . _t('The messages obviously offending someone, espcially forum users') . '</li>
            <li>' . _t('Purposely to deform a writing of nicknames of other users for the purpose of giving to the message an offensive shade') . '</li>
            <li>' . _t('Publishing deleted by moderator messages repeatedly') . '</li>
            <li>' . _t('Post messages not related to the topic') . '</li>
            <li>' . _t('Continuing topics that have been closed by moderator') . '</li>
        </ol>
    </li>
    <li><strong>' . _t('ABSOLUTELY FORBIDDEN') . '</strong> 
        <ol>
            <li>' . _t('Writing in the public topics a discontent with inappropriate actions of other users and discussing about moderators working. Use private messages for this purpose') . '</li>
            <li>' . _t('Register the nicknames having offensive tone for other users') . '</li>
        </ol>
    </li>
    <li><strong>' . _t('UNACCEPTABLE') . '</strong>
        <ol>
            <li>' . _t('Quote full messages of the previous user and quote the messages already containing other quotes') . '</li>
            <li>' . _t('Post messages only contains external link') . '</li>
            <li>' . _t('Post messages written in translite') . '</li>
            <li>' . _t('Posting of purposeless messages: consisting of smileys only or posts like "Ok", "What?", "Who is there?", etc.') . '</li>
        </ol>
    </li>
</ol>

' . _t('Message posted despite the above warnings will be changed or deleted.') . '<br />
' . _t('This rule applies to all members and moderators, repeated actions will make you get ban or even lose your account.');

echo '</p></div><div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
