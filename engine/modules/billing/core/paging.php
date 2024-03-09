<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

Class Paging
{
    /**
     * DOM
     * @var string
     */
    private string $THEME_BLOCK = '<div class="pull-left pagination_div"><ul class="pagination pagination-sm">%s</ul></div>';

    /**
     * Ссылка
     * @var string
     */
    private string $URL = '/page/{p}/';

    /**
     * Шаблон ссылки
     * @var string
     */
    private string $THEME_LINK = '<li><a href="%s">%s</a></li>';

    /**
     * Шаблон активной ссылка
     * @var string
     */
    private string $THEME_LINK_ACTIVE = '<li class="active"><span>%s</span></li>';

    /**
     * Количество записей
     * @var int
     */
    private int $ROWS_COUNT = 0;

    /**
     * Текущая страница
     * @var int
     */
    private int $CURRENT_PAGE = 1;

    /**
     * Результатов на страницу
     * @var int
     */
    private int $PER_PAGE = 10;

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url) : self
    {
        $this->URL = $url;

        return $this;
    }

    /**
     * @param string $block
     * @return $this
     */
    public function setThemeBlock(string $block) : self
    {
        $this->THEME_BLOCK = $block;

        return $this;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPerPage(int $page) : self
    {
        $this->PER_PAGE = $page > 0 ? $page : 10;

        return $this;
    }

    /**
     * @param int|null $page
     * @return $this
     */
    public function setCurrentPage(?int $page) : self
    {
        $this->CURRENT_PAGE = $page > 0 ? $page : 1;

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function setRows(int $count) : self
    {
        $this->ROWS_COUNT = $count;

        return $this;
    }

    /**
     * @param string|null $link
     * @param string|null $active_link
     * @return Paging
     */
    public function setThemeLink(?string $link, ?string $active_link) : self
    {
        if( $link )
        {
            # todo: для совместимости
            $link = str_replace(['{page_num}', '{page_num_link}'], '%s', $link);

            $this->THEME_LINK = $link;
        }

        if( $active_link )
        {
            # todo: для совместимости
            $active_link = str_replace('{page_num}', '%s', $active_link);

            $this->THEME_LINK_ACTIVE = $active_link;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function parse() : string
    {
        $pages = @ceil( $this->ROWS_COUNT / $this->PER_PAGE );

        $_return = [];

        if( $pages == 1 )
        {
            $_return[] = sprintf($this->THEME_LINK, '#', 1);
        }
        else
        {
            $min = false;

            if( $this->CURRENT_PAGE > 1 )
            {
                $_return[] = sprintf(
                    $this->THEME_LINK,
                    str_replace('{p}', ($this->CURRENT_PAGE-1), $this->URL),
                    '&laquo;'
                );
            }

            for( $j = 1; $j <= $pages; $j ++ )
            {
                if( $j < ( $this->CURRENT_PAGE - 4 ) )
                {
                    if( ! $min )
                    {
                        $j++;
                        $min = true;

                        $_return[] = sprintf(
                            $this->THEME_LINK,
                            str_replace('{p}', 1, $this->URL),
                            "1.."
                        );
                    }

                    continue;
                }

                if( $j > ( $this->CURRENT_PAGE + 5 ) )
                {
                    $_return[] = sprintf(
                        $this->THEME_LINK,
                        str_replace('{p}', $pages, $this->URL),
                        "..{$pages}"
                    );

                    break;
                }

                if( $this->CURRENT_PAGE != $j )
                {
                    $_return[] = sprintf(
                        $this->THEME_LINK,
                        str_replace('{p}', $j, $this->URL),
                        $j
                    );
                }
                else
                {
                    $_return[] = sprintf(
                        $this->THEME_LINK_ACTIVE,
                        $j
                    );
                }
            }

            if( $this->CURRENT_PAGE < $pages )
            {
                $_return[] = sprintf(
                    $this->THEME_LINK,
                    str_replace('{p}', ($this->CURRENT_PAGE + 1), $this->URL),
                    "&raquo;"
                );
            }
        }

        return sprintf($this->THEME_BLOCK, implode($_return));
    }
}