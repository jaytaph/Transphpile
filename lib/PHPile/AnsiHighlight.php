<?php

namespace PHPile;

class AnsiHighlight
{

    /**
     * Converts PHP code into highlighted syntax using ANSI escape codes to pretty print on console.
     *
     * @param $str
     * @return string
     */
    public function highlight($str)
    {
        $str = highlight_string($str, true);
        $str = html_entity_decode($str);
        $replace = array(
            '|</span>|' => "\033[0m",
            '|<code>|' => '',
            '|</code>|' => '',
            '|<br //>|' => "\n",
        );
        foreach ($replace as $html => $ansi) {
            $str = preg_replace($html, $ansi, $str);
        }

        // Replace all color spans with actual escape sequences
        $str = preg_replace_callback(
            '|<span style="color: #([A-Z0-9]{6})">|',
            function ($matches) {
                switch ($matches[1]) {
                    case '007700' :
                        // green
                        return "\033[33;1m";
                    case 'DD0000' :
                        // red
                        return "\033[32;1m";
                    case '0000BB' :
                        // blue
                        return "\033[34;1m";
                    default :
                        // gray
                        return "\033[37;1m";
                }
            },
            $str
        );

        $arr_html = explode('<br />', $str);
        $out = '';

        foreach ($arr_html as $line) {
            $line = str_replace(chr(13), '', $line);
            $out .= $line."\n";
        }

        return $out;
    }
}
