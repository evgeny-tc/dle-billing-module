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

    /**
     * @var mixed
     */
    private mixed $lastResult;

    /**
     * dle
     * @var array
     */
    private array $global = [];

    /**
     * @throws \Exception
     */
    public function __construct(?int $userId = 0, ?string $name = '', ?string $email = '', ?int $group_id = 0)
    {
        global $config, $db, $_TIME;

        $this->global['dle_config'] = $config;
        $this->global['db'] = $db;
        $this->global['time'] = $_TIME;

        if( ! class_exists('\dle_mail') )
        {
            include_once \DLEPlugins::Check( ENGINE_DIR . '/classes/mail.class.php' );
        }

        if( file_exists(ENGINE_DIR . '/data/billing/config.php') )
        {
            $this->global['billing'] = include ENGINE_DIR . '/data/billing/config.php';
        }

        $this->global['dle_mail'] = new \dle_mail( $config, true );

        if( $userId )
        {
            $this->UserConnect['id'] = $userId;
        }
        else if( $name )
        {
            $this->UserConnect['name'] = $name;
        }
        else if( $email )
        {
            $this->UserConnect['email'] = $email;
        }
        else if( $group_id )
        {
            $this->UserConnect['group_id'] = $group_id;
        }

        if( ! count($this->UserConnect) )
        {
            throw new \Exception('UserConnect');
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
     * Загрузить содержимое уведомления из файла
     * @param string $filename
     * @return $this
     * @throws \Exception
     */
    public function loadTemplate(string $filename) : self
    {
        if( ! $template = file_get_contents( ROOT_DIR . '/templates/' . $this->global['dle_config']['skin'] . '/billing/mail/' . $filename . '.tpl') )
        {
            throw new \Exception('Load template');
        }

        preg_match('~\[title\](.*?)\[/title\]~is', $template, $Title);

        if( $Title[1] )
        {
            $this->message_title = $Title[1];
        }

        $this->message_body = preg_replace("'\\[title\\].*?\\[/title\\]'si", '', $template);

        if( ! $this->message_title  )
        {
            throw new \Exception('Message title');
        }

        if( ! $this->message_body  )
        {
            throw new \Exception('Message body');
        }

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
            foreach ($data as $key => $value)
            {
                $key = htmlspecialchars($key);
                $value = htmlspecialchars($value);

                $this->message_body = str_replace(
                    $key,
                    $value,
                    $this->message_body
                );
                $this->message_body = str_replace(
                    $key,
                    $value,
                    $this->message_body
                );
            }
        }

        return $this;
    }

    /**
     * Личные сообщения на сайте
     * @throws \Exception
     */
    public function pm(?string $from = '', ?int $time = 0) : self
    {
        $from = $from ? $this->global['db']->safesql( $from ) : $this->global['billing']['admin'];
        $time = $time ?: $this->global['time'];

        $this->lastResult = [];

        $this->global['db']->query("START TRANSACTION;");

        foreach( $this->getUsersQuery() as $user )
        {
            $this->global['db']->query( "INSERT INTO " . PREFIX . "_pm
											(subj, text, user, user_from, date, pm_read, folder) VALUES
											('{$this->message_title}', '{$this->message_body}', '{$user['user_id']}', '{$from}', '{$time}', '0', 'inbox')" );

            $this->lastResult[$user['user_id']] = $this->global['db']->insert_id();

            $this->global['db']->query( "UPDATE " . USERPREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$user['user_id']}'" );
        }

        $this->global['db']->query("COMMIT");

        return $this;
    }

    /**
     * Отправить email
     * @return Alert
     */
    public function email() : self
    {
        $this->lastResult = [];

        foreach( $this->getUsersQuery() as $user )
        {
            $this->lastResult[] = $this->global['dle_mail']->send(
                $user['email'],
                $this->message_title,
                $this->message_body
            );
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResult() : mixed
    {
        return $this->lastResult;
    }

    /**
     * Найти пользователя
     * @return array
     */
    protected function getUsersQuery() : array
    {
        $_return = [];

        if( count($this->UserConnect) )
        {
            if( $this->UserConnect['name'] )
            {
                $this->global['db']->query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE name = '" . $this->global['db']->safesql($this->UserConnect['name']) . "'" );
            }
            else if( $this->UserConnect['email'] )
            {
                $this->global['db']->query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE email = '" . $this->global['db']->safesql($this->UserConnect['email']) . "'" );
            }
            else if( $this->UserConnect['group_id'] )
            {
                $this->global['db']->query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE user_group = '" . intval($this->UserConnect['group_id']) . "'" );
            }
            else
            {
                $this->global['db']->query( "SELECT user_id, email FROM " . USERPREFIX . "_users WHERE user_id = '" . intval($this->UserConnect['id']) . "'" );
            }

            while($user = $this->global['db']->get_row())
            {
                $_return[] = $user;
            }
        }

        return $_return;
    }
}