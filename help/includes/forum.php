<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Правила Форума
echo '<div class="phdr"><a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _td('Forum rules', 'help') . '</div>' .
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
    <li><strong>' . _td('FORBIDDEN', 'help') . '</strong>
        <ol>
            <li>' . _td('Advertising messages', 'help') . '</li>
            <li>' . _td('The messages containing direct propagation of racial, national and religious animosity', 'help') . '</li>
            <li>' . _td('Any abusive, obscene, vulgar, slanderous, hateful, threatening and sexually-oriented messages or any other material that may violate any applicable laws', 'help') . '</li>
            <li>' . _td('The messages obviously offending someone, espcially forum users', 'help') . '</li>
            <li>' . _td('Purposely to deform a writing of nicknames of other users for the purpose of giving to the message an offensive shade', 'help') . '</li>
            <li>' . _td('Publishing deleted by moderator messages repeatedly', 'help') . '</li>
            <li>' . _td('Post messages not related to the topic', 'help') . '</li>
            <li>' . _td('Continuing topics that have been closed by moderator', 'help') . '</li>
        </ol>
    </li>
    <li><strong>' . _td('ABSOLUTELY FORBIDDEN', 'help') . '</strong> 
        <ol>
            <li>' . _td('Writing in the public topics a discontent with inappropriate actions of other users and discussing about moderators working. Use private messages for this purpose', 'help') . '</li>
            <li>' . _td('Register the nicknames having offensive tone for other users', 'help') . '</li>
        </ol>
    </li>
    <li><strong>' . _td('UNACCEPTABLE', 'help') . '</strong>
        <ol>
            <li>' . _td('Quote full messages of the previous user and quote the messages already containing other quotes', 'help') . '</li>
            <li>' . _td('Post messages only contains external link', 'help') . '</li>
            <li>' . _td('Post messages written in translite', 'help') . '</li>
            <li>' . _td('Posting of purposeless messages: consisting of smileys only or posts like "Ok", "What?", "Who is there?", etc.', 'help') . '</li>
        </ol>
    </li>
</ol>

' . _td('Message posted despite the above warnings will be changed or deleted.', 'help') . '<br />
' . _td('This rule applies to all members and moderators, repeated actions will make you get ban or even lose your account.', 'help');

echo '</p></div><div class="phdr"><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></div>';
