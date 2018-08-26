<?php
defined('_IN_JOHNCMS') or die('Error: restricted access');

function _e($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}