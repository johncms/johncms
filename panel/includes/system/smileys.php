<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['smileys'] . '</div>';
$total = functions::smileys(0, 2);
if ($total) {
    echo '<div class="gmenu"><p>' . $lng['smileys_updated'] . '</p></div>';
} else {
    echo '<div class="rmenu"><p>' . $lng['smileys_error'] . '</p></div>';
    $total = 0;
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
echo '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';

?>
