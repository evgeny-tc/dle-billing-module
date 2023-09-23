<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

trait Utheme
{
    /**
     * Template build
     * @var array
     */
    public $elements = [];
    public $element_block = [];

    /**
     * TPL: Added tag
     * @param string $field
     * @param string $value
     * @return void
     */
    public function ThemeSetElement( string $field, $value = '' )
    {
        $this->elements[$field] = $value;

        return;
    }

    /**
     * TPL: Added split tag
     * @param string $fields
     * @param string $value
     * @return void
     */
    public function ThemeSetElementBlock(string $fields, $value = '' ) : void
    {
        $this->element_block[$fields] = $value;
    }

    /**
     * TPL: Loader
     * @param string $file
     * @return string|void
     */
    public function ThemeLoad( string $file )
    {
        if( ! file_exists( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $file . ".tpl" ) )
        {
            throw new \Exception($this->lang['cabinet_theme_error'] . "{$file}.tpl");
        }

        return @file_get_contents( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $file . ".tpl" );
    }

    /**
     * Show page with errors
     * @param $title
     * @param $errors
     * @param $show_panel
     * @return array|false|string|string[]|null
     */
    public function ThemeMsg( string $title, string $errors, $show_panel = true )
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
    public function ThemePregReplace(string $tag, string &$data, string|null $update = '' )
    {
        $data = preg_replace("'\\[$tag\\].*?\\[/$tag\\]'si", $update, $data);

        return;
    }

    /**
     * Get content tag
     * @param string $theme
     * @param string $tag
     * @return mixed
     */
    public function ThemePregMatch( string $theme, string $tag )
    {
        $answer = [];

        preg_match($tag, $theme, $answer);

        return $answer[1];
    }

    public static function TPL()
    {
        global $tpl;

        return $tpl ?: throw new Exception("tpl class error load");
    }
}