<?php

namespace Library;

class Links
{
    private $link_url;
    private $in;
    private $res;

    /** @var PDO $db */
    private $db;

    /**
     * @var Johncms\Tools
     */
    private $tools;

    public function __construct($in, $link_url = '?act=tags&amp;tag=')
    {
        $this->link_url = $link_url;
        $this->in = $in;
        $container = \App::getContainer();
        $this->db = $container->get(\PDO::class);
        $this->tools = $container->get('tools');
    }

    public function proccess($tpl)
    {
        if ($this->in) {
            $this->res = array_map([$this, $tpl], $this->in);

            return $this;
        } else {
            return false;
        }
    }
    
    public function tpl_tag($n) 
    {
        return '<a href="' . $this->link_url . $n . '">' . $this->tools->checkout($n) . '</a>';
    }
    
    public function tpl_cloud($n) 
    {
        return '<a href="' . $this->link_url . $this->tools->checkout($n['name']) . '"><span style="font-size: ' . $n['rang'] . 'em;">' . $this->tools->checkout($n['name']) . '</span></a>';
    }

    public function link_separator($sepatator = ' | ')
    {
        if ($this->in) {
            $this->res = implode($sepatator, $this->res ? $this->res : $this->in);

            return $this;
        } else {
            return false;
        }
    }

    public function result()
    {
        return $this->res;
    }

    public function get_in()
    {
        return $this->in;
    }

}