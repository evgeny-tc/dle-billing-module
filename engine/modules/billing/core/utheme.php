<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

trait Utheme
{
    /**
     * Template build
     * @var array
     */
    public array $elements = [];
    public array $element_block = [];

    /**
     * TPL: Added tag
     * @param string $field
     * @param string|null $value
     * @return void
     */
    public function ThemeSetElement(string $field, string|null $value = '' ) : void
    {
        $this->elements[$field] = $value;
    }

    /**
     * TPL: Added split tag
     * @param string $fields
     * @param string|null $value
     * @return void
     */
    public function ThemeSetElementBlock(string $fields, string|null $value = '' ) : void
    {
        $this->element_block[$fields] = $value;
    }

    /**
     * TPL: Loader
     * @param string $file
     * @return string
     * @throws \Exception
     */
    public function ThemeLoad( string $file ) : string
    {
        if( ! file_exists( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $file . ".tpl" ) )
        {
            throw new \Exception($this->lang['cabinet_theme_error'] . "{$file}.tpl");
        }

        return @file_get_contents( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $file . ".tpl" );
    }

    /**
     * Show page with errors
     * @param string $title
     * @param string $errors
     * @param bool $show_panel
     * @return string
     * @throws \Exception
     */
    public function ThemeMsg( string $title, string $errors, bool $show_panel = true ) : string
    {
        $this->ThemeSetElement( "{msg}", $errors );
        $this->ThemeSetElement( "{title}", $title );

        return $this->Show( $this->ThemeLoad( "msg" ), $show_panel );
    }

    /**
     * Replace content tag
     * @param string $tag
     * @param string $data
     * @param string|null $update
     * @return void
     */
    public function ThemePregReplace(string $tag, string &$data, string|null $update = '' ) : void
    {
        $data = preg_replace("'\\[$tag\\].*?\\[/$tag\\]'si", $update, $data);
    }

    /**
     * Get content tag
     * @param string $theme
     * @param string $tag
     * @return string
     */
    public function ThemePregMatch( string $theme, string $tag ) : string
    {
        $answer = [];

        preg_match($tag, $theme, $answer);

        return $answer[1];
    }

    /**
     * Return dle_template
     * @return \dle_template
     * @throws \Exception
     */
    public static function TPL() : \dle_template
    {
        global $tpl;

        return $tpl ?: throw new \Exception("tpl class error load");
    }
}