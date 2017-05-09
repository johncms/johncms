<?php

return [
    'bbcode' => [
        // Жирный
        'b'       => [
            'from' => '#\[b](.+?)\[/b]#is',
            'to'   => '<span style="font-weight: bold">$1</span>',
            'data' => '$1',
        ],
        // Курсив
        'i'       => [
            'from' => '#\[i](.+?)\[/i]#is',
            'to'   => '<span style="font-style:italic">$1</span>',
            'data' => '$1',
        ],
        // Подчёркнутый
        'u'       => [
            'from' => '#\[u](.+?)\[/u]#is',
            'to'   => '<span style="text-decoration:underline">$1</span>',
            'data' => '$1',
        ],
        // Зачёркнутый
        's'       => [
            'from' => '#\[s](.+?)\[/s]#is',
            'to'   => '<span style="text-decoration:line-through">$1</span>',
            'data' => '$1',
        ],
        // Маленький шрифт
        'small'   => [
            'from' => '#\[small](.+?)\[/small]#is',
            'to'   => '<span style="font-size:x-small">$1</span>',
            'data' => '$1',
        ],
        // Большой шрифт
        'big'     => [
            'from' => '#\[big](.+?)\[/big]#is',
            'to'   => '<span style="font-size:large">$1</span>',
            'data' => '$1',
        ],
        // Красный
        'red'     => [
            'from' => '#\[red](.+?)\[/red]#is',
            'to'   => '<span style="color:red">$1</span>',
            'data' => '$1',
        ],
        // Зеленый
        'green'   => [
            'from' => '#\[green](.+?)\[/green]#is',
            'to'   => '<span style="color:green">$1</span>',
            'data' => '$1',
        ],
        // Синий
        'blue'    => [
            'from' => '#\[blue](.+?)\[/blue]#is',
            'to'   => '<span style="color:blue">$1</span>',
            'data' => '$1',
        ],
        // Цвет шрифта
        'color'   => [
            'from' => '!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/color]!is',
            'to'   => '<span style="color:$1">$2</span>',
            'data' => '$2',
        ],
        // Цвет фона
        'bg'      => [
            'from' => '!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is',
            'to'   => '<span style="background-color:$1">$2</span>',
            'data' => '$2',
        ],
        // Цитата
        'quote'   => [
            'from' => '#\[(quote|c)](.+?)\[/(quote|c)]#is',
            'to'   => '<span class="quote" style="display:block">$2</span>',
            'data' => '$2',
        ],
        // Список
        'list'    => [
            'from' => '#\[\*](.+?)\[/\*]#is',
            'to'   => '<span class="bblist">$1</span>',
            'data' => '$1',
        ],
        // Спойлер
        'spoiler' => [
            'from' => '#\[spoiler=(.+?)](.+?)\[/spoiler]#is',
            'to'   => '<div><div class="spoilerhead" style="cursor:pointer;" onclick="var _n=this.parentNode.getElementsByTagName(\'div\')[1];if(_n.style.display==\'none\'){_n.style.display=\'\';}else{_n.style.display=\'none\';}">$1 (+/-)</div><div class="spoilerbody" style="display:none">$2</div></div>',
            'data' => '$1',
        ],
    ],

    'buttons' => [

    ],
];
