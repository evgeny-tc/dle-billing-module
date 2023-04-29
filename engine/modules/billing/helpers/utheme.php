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
     * TPL: Added tag
     * @param string $field
     * @param string $value
     * @return void
     */
    public function ThemeSetElement( string $field, string|null $value = '' )
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
    public function ThemeSetElementBlock( string $fields, string|null $value = '' )
    {
        $this->element_block[$fields] = $value;

        return;
    }

    /**
     * TPL: Loader
     * @param string $file
     * @return string|void
     */
    public function ThemeLoad( string $file )
    {
        $Content = @file_get_contents( ROOT_DIR . "/templates/" . $this->dle['skin'] . "/billing/" . $file . ".tpl" ) or throw new \Exception($this->lang['cabinet_theme_error'] . "{$file}.tpl");;

        return $Content;
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
     * @param string $update
     * @return void
     */
    public function ThemePregReplace( string $tag, string &$data, string|null $update = '' )
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
        $answer = array();

        preg_match($tag, $theme, $answer);

        return $answer[1];
    }

}