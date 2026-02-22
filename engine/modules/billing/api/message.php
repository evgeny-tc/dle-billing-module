<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2026
 */
namespace Billing\Api;

/**
 * ЛС
 */
Class Message extends Email
{
    /**
     * @param string|null $from
     * @return $this
     */
    public function send(?string $from = '') : self
    {
        try
        {
            $from = $from ? $this->global['db']->safesql( $from ) : $this->global['billing']['admin'];

            if( ! $fromUser = $this->getUserSender($from) )
            {
                throw new \Exception("error_search_sender {$from}");
            }

            if( ! $this->message_body or ! $this->message_title )
            {
                throw new \Exception("body and title is required");
            }

            $this->lastResult = [];

            $this->global['db']->query("START TRANSACTION;");

            foreach( $this->getUsersQuery() as $user )
            {
                if( ! $conversion = $this->getConversion($fromUser, $user) )
                {
                    throw new \Exception("conversion is not found");
                }

                $this->sendMessage($conversion, $fromUser, $user);
            }

            $this->global['db']->query("COMMIT");
        }
        catch (\Exception $e)
        {
            $this->global['db']->query("ROLLBACK");

            $this->lastResult = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        return $this;
    }

    /**
     * Отправить сообщение
     * @param int $conversation_id
     * @param array $fromUser
     * @param array $userTo
     * @return void
     */
    private function sendMessage(int $conversation_id, array $fromUser, array $userTo) : void
    {
        $this->global['db']->query("INSERT INTO " . USERPREFIX . "_conversations_messages (conversation_id, sender_id, content, created_at) 
                                    values ('{$conversation_id}', '{$fromUser['user_id']}', '{$this->message_body}', '{$this->global['time']}')");

        if ($fromUser['user_id'] != $userTo['user_id'])
        {
            $this->global['db']->query("INSERT INTO " . USERPREFIX . "_conversation_reads (user_id, conversation_id, last_read_at) values ('{$fromUser['user_id']}', '{$conversation_id}', '{$this->global['time']}') ON DUPLICATE KEY UPDATE last_read_at='{$this->global['time']}'");
        }
    }

    /**
     * Найти/создать диалог
     * @param array $fromUser
     * @param array $userTo
     * @return int
     */
    private function getConversion(array $fromUser, array $userTo) : int
    {
        $result = $this->global['db']->super_query("SELECT id FROM " . USERPREFIX . "_conversations
                                                    WHERE sender_id='{$fromUser['user_id']}' 
                                                        AND recipient_id='{$userTo['user_id']}'
                                                        AND subject='{$this->message_title}'");

        if( ! $result )
        {
            $this->global['db']->query("INSERT INTO " . USERPREFIX . "_conversations (subject, created_at, updated_at, sender_id, recipient_id) 
                                            values ('{$this->message_title}', '{$this->global['time']}', '{$this->global['time']}', '{$fromUser['user_id']}', '{$userTo['user_id']}')");

            $conversation_id = $this->global['db']->insert_id();

            $this->global['db']->query("INSERT INTO " . USERPREFIX . "_conversation_users (user_id, conversation_id) values ('{$fromUser['user_id']}', '{$conversation_id}'), ('{$userTo['user_id']}', '{$conversation_id}') ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)");

            return $conversation_id;
        }

        return $result['id'];
    }
}