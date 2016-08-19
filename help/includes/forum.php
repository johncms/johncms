<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Правила Форума
echo '<div class="phdr"><a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _td('Forum rules') . '</div>' .
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
    <li><strong>' . _td('FORBIDDEN') . '</strong>
        <ol>
            <li>' . _td('Advertising messages') . '</li>
            <li>' . _td('The messages containing direct propagation of racial, national and religious animosity') . '</li>
            <li>' . _td('Any abusive, obscene, vulgar, slanderous, hateful, threatening and sexually-oriented messages or any other material that may violate any applicable laws') . '</li>
            <li>' . _td('The messages obviously offending someone, espcially forum users') . '</li>
            <li>' . _td('Purposely to deform a writing of nicknames of other users for the purpose of giving to the message an offensive shade') . '</li>
            <li>' . _td('Publishing deleted by moderator messages repeatedly') . '</li>
            <li>' . _td('Post messages not related to the topic') . '</li>
            <li>' . _td('Continuing topics that have been closed by moderator') . '</li>
        </ol>
    </li>
    <li><strong>' . _td('ABSOLUTELY FORBIDDEN') . '</strong> 
        <ol>
            <li>' . _td('Writing in the public topics a discontent with inappropriate actions of other users and discussing about moderators working. Use private messages for this purpose') . '</li>
            <li>' . _td('Register the nicknames having offensive tone for other users') . '</li>
        </ol>
    </li>
    <li><strong>' . _td('UNACCEPTABLE') . '</strong>
        <ol>
            <li>' . _td('Quote full messages of the previous user and quote the messages already containing other quotes') . '</li>
            <li>' . _td('Post messages only contains external link') . '</li>
            <li>' . _td('Post messages written in translite') . '</li>
            <li>' . _td('Posting of purposeless messages: consisting of smileys only or posts like "Ok", "What?", "Who is there?", etc.') . '</li>
        </ol>
    </li>
</ol>

' . _td('Message posted despite the above warnings will be changed or deleted.') . '<br />
' . _td('This rule applies to all members and moderators, repeated actions will make you get ban or even lose your account.');

echo '</p></div><div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
