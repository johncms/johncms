<?php

namespace Johncms;

use Zend\Stdlib\ArrayObject;

/**
 * Class Config
 *
 * @package Johncms
 *
 * @property $active
 * @property $antiflood
 * @property $clean_time
 * @property $copyright
 * @property $email
 * @property $flsz
 * @property $gzip
 * @property $homeurl
 * @property $karma
 * @property $lng
 * @property $mod_reg
 * @property $mod_forum
 * @property $mod_guest
 * @property $mod_lib
 * @property $mod_lib_comm
 * @property $mod_down
 * @property $mod_down_comm
 * @property $meta_key
 * @property $meta_desc
 * @property $news
 * @property $skindef
 */
class Config extends ArrayObject
{
    public function __construct(array $input)
    {
        parent::__construct($input, parent::ARRAY_AS_PROPS);
    }
}
