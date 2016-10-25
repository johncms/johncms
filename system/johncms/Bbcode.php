<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class Bbcode
{
    private $homeUrl;

    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['johncms'];
        $this->homeUrl = $config['homeurl'];

        return $this;
    }

    function notags($var = '')
    {
        $var = preg_replace('#\[color=(.+?)\](.+?)\[/color]#si', '$2', $var);
        $var = preg_replace('#\[code=(.+?)\](.+?)\[/code]#si', '$2', $var);
        $var = preg_replace('!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', '$2', $var);
        $var = preg_replace('#\[spoiler=(.+?)\]#si', '$2', $var);
        $replace = [
            '[small]'  => '',
            '[/small]' => '',
            '[big]'    => '',
            '[/big]'   => '',
            '[green]'  => '',
            '[/green]' => '',
            '[red]'    => '',
            '[/red]'   => '',
            '[blue]'   => '',
            '[/blue]'  => '',
            '[b]'      => '',
            '[/b]'     => '',
            '[i]'      => '',
            '[/i]'     => '',
            '[u]'      => '',
            '[/u]'     => '',
            '[s]'      => '',
            '[/s]'     => '',
            '[quote]'  => '',
            '[/quote]' => '',
            '[php]'    => '',
            '[/php]'   => '',
            '[c]'      => '',
            '[/c]'     => '',
            '[*]'      => '',
            '[/*]'     => '',
        ];

        return strtr($var, $replace);
    }

    /**
     * BbCode Toolbar
     *
     * @param string $form
     * @param string $field
     * @return string
     */
    public function buttons($form, $field)
    {
        $colors = [
            'ffffff',
            'bcbcbc',
            '708090',
            '6c6c6c',
            '454545',
            'fcc9c9',
            'fe8c8c',
            'fe5e5e',
            'fd5b36',
            'f82e00',
            'ffe1c6',
            'ffc998',
            'fcad66',
            'ff9331',
            'ff810f',
            'd8ffe0',
            '92f9a7',
            '34ff5d',
            'b2fb82',
            '89f641',
            'b7e9ec',
            '56e5ed',
            '21cad3',
            '03939b',
            '039b80',
            'cac8e9',
            '9690ea',
            '6a60ec',
            '4866e7',
            '173bd3',
            'f3cafb',
            'e287f4',
            'c238dd',
            'a476af',
            'b53dd2',
        ];
        $font_color = '';
        $bg_color = '';

        foreach ($colors as $value) {
            $font_color .= '<a href="javascript:tag(\'[color=#' . $value . ']\', \'[/color]\'); show_hide(\'color\');" style="background-color:#' . $value . ';"></a>';
            $bg_color .= '<a href="javascript:tag(\'[bg=#' . $value . ']\', \'[/bg]\'); show_hide(\'bg\');" style="background-color:#' . $value . ';"></a>';
        }

        // Смайлы
        //$smileys = !empty(self::$user_data['smileys']) ? unserialize(self::$user_data['smileys']) : [];
        $smileys = []; //TODO: убрать!

        if (!empty($smileys)) {
            $res_sm = '';
            $bb_smileys = '<small><a href="' . $this->homeUrl . '/help/?act=my_smilies">' . _t('Edit List') . '</a></small><br />';

            foreach ($smileys as $value) {
                $res_sm .= '<a href="javascript:tag(\':' . $value . '\', \':\'); show_hide(\'sm\');">:' . $value . ':</a> ';
            }

            $bb_smileys .= \functions::smileys($res_sm, \core::$user_data['rights'] >= 1 ? 1 : 0);
        } else {
            $bb_smileys = '<small><a href="' . $this->homeUrl . '/help/?act=smilies">' . _t('Add Smilies') . '</a></small>';
        }

        // Код
        $code = [
            'php',
            'css',
            'js',
            'html',
            'sql',
            'xml',
        ];

        $codebtn = '';

        foreach ($code as $val) {
            $codebtn .= '<a href="javascript:tag(\'[code=' . $val . ']\', \'[/code]\'); show_hide(\'code\');">' . strtoupper($val) . '</a>';
        }

        $out = '<style>
.codepopup {margin-top: 3px;}
.codepopup a {
border: 1px solid #a7a7a7;
border-radius: 3px;
background-color: #dddddd;
color: black;
font-weight: bold;
padding: 2px 6px 2px 6px;
display: inline-block;
margin-right: 6px;
margin-bottom: 3px;
text-decoration: none;
}
</style>
            <script language="JavaScript" type="text/javascript">
            function tag(text1, text2) {
              if ((document.selection)) {
                document.' . $form . '.' . $field . '.focus();
                document.' . $form . '.document.selection.createRange().text = text1+document.' . $form . '.document.selection.createRange().text+text2;
              } else if(document.forms[\'' . $form . '\'].elements[\'' . $field . '\'].selectionStart!=undefined) {
                var element = document.forms[\'' . $form . '\'].elements[\'' . $field . '\'];
                var str = element.value;
                var start = element.selectionStart;
                var length = element.selectionEnd - element.selectionStart;
                element.value = str.substr(0, start) + text1 + str.substr(start, length) + text2 + str.substr(start + length);
              } else {
                document.' . $form . '.' . $field . '.value += text1+text2;
              }
            }
            function show_hide(elem) {
              obj = document.getElementById(elem);
              if( obj.style.display == "none" ) {
                obj.style.display = "block";
              } else {
                obj.style.display = "none";
              }
            }
            </script>
            <a href="javascript:tag(\'[b]\', \'[/b]\')"><img src="' . $this->homeUrl . '/images/bb/bold.gif" alt="b" title="' . _t('Bold') . '" border="0"/></a>
            <a href="javascript:tag(\'[i]\', \'[/i]\')"><img src="' . $this->homeUrl . '/images/bb/italics.gif" alt="i" title="' . _t('Italic') . '" border="0"/></a>
            <a href="javascript:tag(\'[u]\', \'[/u]\')"><img src="' . $this->homeUrl . '/images/bb/underline.gif" alt="u" title="' . _t('Underline') . '" border="0"/></a>
            <a href="javascript:tag(\'[s]\', \'[/s]\')"><img src="' . $this->homeUrl . '/images/bb/strike.gif" alt="s" title="' . _t('Strike') . '" border="0"/></a>
            <a href="javascript:tag(\'[*]\', \'[/*]\')"><img src="' . $this->homeUrl . '/images/bb/list.gif" alt="s" title="' . _t('List') . '" border="0"/></a>
            <a href="javascript:tag(\'[spoiler=]\', \'[/spoiler]\');"><img src="' . $this->homeUrl . '/images/bb/sp.gif" alt="spoiler" title="' . _t('Spoiler') . '" border="0"/></a>
            <a href="javascript:tag(\'[c]\', \'[/c]\')"><img src="' . $this->homeUrl . '/images/bb/quote.gif" alt="quote" title="' . _t('Quote') . '" border="0"/></a>
            <a href="javascript:tag(\'[url=]\', \'[/url]\')"><img src="' . $this->homeUrl . '/images/bb/link.gif" alt="url" title="' . _t('URL') . '" border="0"/></a>
            <a href="javascript:show_hide(\'code\');"><img src="' . $this->homeUrl . '/images/bb/php.gif" title="' . _t('Code') . '" border="0"/></a>
            <a href="javascript:show_hide(\'color\');"><img src="' . $this->homeUrl . '/images/bb/color.gif" title="' . _t('Text Color') . '" border="0"/></a>
            <a href="javascript:show_hide(\'bg\');"><img src="' . $this->homeUrl . '/images/bb/color_bg.gif" title="' . _t('Background Color') . '" border="0"/></a>';

        if (\core::$user_id) {
            $out .= ' <a href="javascript:show_hide(\'sm\');"><img src="' . $this->homeUrl . '/images/bb/smileys.gif" alt="sm" title="' . _t('Smilies') . '" border="0"/></a><br />
                <table id="sm" style="display:none"><tr><td>' . $bb_smileys . '</td></tr></table>
                <div id="sm" style="display:none">' . $bb_smileys . '</div>';
        } else {
            $out .= '<br />';
        }
        $out .= '<div id="code" class="codepopup" style="display:none;">' . $codebtn . '</div>' .
            '<div id="color" class="bbpopup" style="display:none;">' . $font_color . '</div>' .
            '<div id="bg" class="bbpopup" style="display:none">' . $bg_color . '</div>';

        return $out;
    }
}
