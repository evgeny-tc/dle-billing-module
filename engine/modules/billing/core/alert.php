<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing;

/**
 * Отправка уведомление (пм и email)
 * @dev
 */
Class Alert
{
    /**
     * Сущность пользователя из _users
     * @var array
     */
    private array $User = [];

    /**
     * Контакты пользователя
     * @var array
     */
    private array $UserConnect = [];

    /**
     * @var string
     */
    private string $message_title = '';

    /**
     * @var string
     */
    private string $message_body = '';

    private array $global = [];

    public function __construct(?int $userId = 0, ?string $name = '', ?string $email = '')
    {
        global $config, $db;

        $this->global['dle_config'] = $config;
        $this->global['db'] = $db;

        if( $userId )
        {
            $this->UserConnect['id'] = $userId;
        }
        if( $name )
        {
            $this->UserConnect['name'] = $name;
        }
        if( $email )
        {
            $this->UserConnect['email'] = $email;
        }

        if( ! count($this->UserConnect) )
        {
            throw new \Exception('Error: UserConnect');
        }
    }

    /**
     * Указать заголовок
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title) : self
    {
        $this->message_title = $title;

        return $this;
    }

    /**
     * Указать содержмое
     * @param string $text
     * @return $this
     */
    public function setBody(string $text) : self
    {
        $this->message_body = $text;

        return $this;
    }

    /**
     * Загрузить содеримое уведомления из файла
     * @param string $filename
     * @return $this
     * @throws \Exception
     */
    public function loadTemplate(string $filename) : self
    {
        if( ! $template = file_get_contents( ROOT_DIR . '/templates/' . $this->global['dle_config']['skin'] . '/billing/mail/' . $filename . '.tpl') )
        {
            throw new \Exception('Error: load template');
        }

        preg_match('~\[title\](.*?)\[/title\]~is', $template, $Title);

        if( $Title[1] )
        {
            $this->message_title = $Title[1];
        }

        $this->message_body = preg_replace("'\\[title\\].*?\\[/title\\]'si", '', $template);

        return $this;
    }

    /**
     * Заменить теги в содержимом
     * @param array|null $data
     * @return $this
     */
    public function buildTemplate(?array $data = []) : self
    {
        if( $data )
        {
            $this->message_body = str_replace(
                array_keys($data),
                array_values($data),
                $this->message_body
            );
            $this->message_body = str_replace(
                array_keys($data),
                array_values($data),
                $this->message_body
            );
        }

        return $this;
    }

    /**
     * Отправить email
     * @param bool|null $check_user
     * @return Alert
     */
    public function email(?bool $check_user = true) : self
    {
        $getUser = false;

        if( $check_user )
        {
            $getUser = $this->getUser();

            $this->UserConnect['email'] = $getUser['email'];
        }

        if( ! $this->UserConnect['email'] )
        {
            throw new \Exception('Error: email not get');
        }

        if( ! $this->message_body or ! $this->message_title )
        {
            throw new \Exception('Error: data message');
        }

        echo "Send:<br>";
        echo "{$this->message_title}:<br>";
        echo "{$this->message_body}:<br>";
        echo "----<br>{$getUser['email']}:<br>";

        return $this;
    }

    /**
     * Найти пользователя
     * @return array
     */
    public function getUser() : array
    {
        if( ! $this->User and count($this->UserConnect) )
        {
            $resultSearch = false;

            if( $this->UserConnect['id'] )
            {
                  $resultSearch = $this->global['db']->super_query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE user_id = '" . $this->global['db']->safesql($this->UserConnect['id']) . "'" );
            }
            else if( $this->UserConnect['name'] )
            {
                  $resultSearch = $this->global['db']->super_query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE name = '" . $this->global['db']->safesql($this->UserConnect['name']) . "'" );
            }
            else if( $this->UserConnect['email'] )
            {
                  $resultSearch = $this->global['db']->super_query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE email = '" . $this->global['db']->safesql($this->UserConnect['email']) . "'" );
            }

            if( $resultSearch )
            {
                $this->User = $resultSearch;
            }
        }

        return $this->User;
    }
}

/*
try
{
    (new Billing\Alert(userId: 2))->loadTemplate('new')->buildTemplate(['{login}' => 'Имя'])->email();
}
catch(Exception $e)
{
    echo "<b>{$e->getMessage()}</b>";
}*/