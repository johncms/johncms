<?php

class Router
{
    /**
     * @var null|string
     */
    public $act;

    /**
     * @var Controller
     */
    protected $controller;

    /**
     * Map router. Autoload by name == nameMethod . 'Render'
     *
     * @var array
     */
    protected $map = [
        //
    ];

    /**
     * Router constructor.
     */
    public function __construct()
    {
        $this->controller = new Controller();
        $this->act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : null;
    }

    /**
     * Start render
     *
     * @return void
     */
    public function run(): void
    {
        if ($this->act) {
            if (array_key_exists($this->act, $this->map)) {
                $this->controller->{$this->map[$this->act]}();
            } elseif (method_exists($this->controller, $this->act . 'Render')) {
                $this->controller->{$this->act . 'Render'}();
            }
        }

        $this->controller->defaultRender();
    }
}
