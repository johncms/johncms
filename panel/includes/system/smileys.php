<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['smileys'] . '</div>';
if ($total = functions::smileys(0, 2)) {
    echo '<div class="gmenu"><p>' . $lng['smileys_updated'] . '</p></div>';
} else {
    echo '<div class="rmenu"><p>' . $lng['smileys_error'] . '</p></div>';
    $total = 0;
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
echo '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';

?>
