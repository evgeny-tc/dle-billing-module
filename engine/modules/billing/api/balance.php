<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2024
 */

namespace Billing\Api;

use \Billing\BalanceException;

/**
 * API Баланс пользователя
 * @api
 */
Class Balance
{
	private static ?self $instance = null;

	private function __construct(){}
    private function __clone()    {}
    public function __wakeup()   {}

    private static array $global = [];

    /**
     * Перед созданием квитанции проверить на дубли
     * @var bool
     */
    private bool $CHECK_INVOICE_DOUBLE = false;

    /**
     * Максимальная длина цепочки событий
     */
    const MAX_HOOK_EVENTS = 10;

    /**
     * @var array
     */
    protected static array $buffer = [];

    /**
     * Данные для события
     * @var array
     */
    private array $hook_data = [];

    /**
     * Loaded hook plugins: name => ['hook' => Hooks, 'config' => array]
     */
    private static ?array $hookRegistry = null;

    /**
     * Текущее звено события
     * @var int
     */
    private int $hook_num = 0;

    /**
     * @param array|null $params
     * @return static
     * @throws BalanceException
     */
    public static function Init(?array $params = []) : self
	{
        if ( empty(self::$instance) )
		{
            global $db, $member_id, $_TIME, $config;

            self::$instance = new self();

            if( ! $params )
            {
                $params = file_exists( ENGINE_DIR . '/data/billing/config.php' ) ? require ENGINE_DIR . '/data/billing/config.php' : throw new BalanceException('Unable to load config file');
            }

            self::$global = [
                'DB' => $db,
                'USER' => $member_id,
                'TIME' => $_TIME,
                'DLE' => $config,
                'BILLING' => $params,
                'LANG' => file_exists( ENGINE_DIR . '/modules/billing/lang/api.php' )
                            ? require ENGINE_DIR . '/modules/billing/lang/api.php'
                            : []
            ];

            self::$instance->hook_num = 0;
            self::$instance->hook_data = [];
        }

        return self::$instance;
    }

    /**
     * Reset singleton (tests)
     * @internal
     */
    public static function reset() : void
    {
        self::$instance = null;
        self::$buffer = [];
        self::$global = [];
        self::$hookRegistry = null;
    }

    /**
     * @param bool $check
     * @return $this
     */
    public function checkDouble(bool $check = true) : self
    {
        $this->CHECK_INVOICE_DOUBLE = $check;

        return $this;
    }

    /**
     * Создать счет на оплату
     * @param int $userId - id
     * @param string $userLogin - or name
     * @param string $userAnonymous - or ip
     * @param string $payment
     * @param float $sum_get
     * @param float $sum_pay
     * @param mixed $payer_info
     * @param string $handler
     * @return int
     * @throws BalanceException
     */
    public function createInvoice(
        int $userId = 0,
        string $userLogin = '',
        string $userAnonymous = '',
        string $payment = '',
        float $sum_get = 0,
        float $sum_pay = 0,
        mixed $payer_info = '',
        string $handler = '') : int
    {
        $payment = self::$global['DB']->safesql( $payment );
        $handler = self::$global['DB']->safesql( $handler );

        if( $userAnonymous )
        {
            $getUser['name'] = $userAnonymous;
        }
        else
        {
            $getUser = $this->getUser($userId, $userLogin);
        }

        if( is_array( $payer_info ) )
        {
            foreach( $payer_info as $key => $info )
            {
                if( is_array($info) )
                {
                    foreach($info as $info_key => $info_val)
                    {
                        $payer_info[$key][$info_key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info_val );
                    }
                }
                else
                {
                    $payer_info[$key] = preg_replace('/[^ a-z&#;@а-яA-ZА-Я\d.]/ui', '', $info);
                }
            }

            $payer_info = serialize( $payer_info );
        }
        else
        {
            $payer_info = self::$global['DB']->safesql( $payer_info );
        }

        # Неавторизованный пользователь
        #
        $invoice_user_anonymous = $userAnonymous ? 1 : 0;

        # Проверка на дубль
        #
        if( $this->CHECK_INVOICE_DOUBLE )
        {
            $search_double = self::$global['DB']->super_query( "SELECT invoice_id FROM " . USERPREFIX . "_billing_invoice 
                                                                    where invoice_paysys = '{$payment}'
                                                                        and invoice_user_name = '{$getUser['name']}'
                                                                        and invoice_user_anonymous = '{$invoice_user_anonymous}'
                                                                        and invoice_get = '{$sum_get}'
                                                                        and invoice_pay = '{$sum_pay}'
                                                                        and invoice_payer_info = '{$payer_info}'
                                                                        and invoice_handler = '{$handler}'
                                                                        and invoice_date_pay = 0 " );

            if( intval( $search_double['invoice_id'] ) )
            {
                return $search_double['invoice_id'];
            }
        }

        self::$global['DB']->query( "INSERT INTO " . USERPREFIX . "_billing_invoice
							(invoice_paysys, invoice_user_name, invoice_user_anonymous, invoice_get, invoice_pay, invoice_date_creat, invoice_payer_info, invoice_handler) values
							('{$payment}',  '{$getUser['name']}', '{$invoice_user_anonymous}', '{$sum_get}', '{$sum_pay}', '" . self::$global['TIME'] . "', '{$payer_info}', '{$handler}')" );

        $this->checkDouble( false );

        return self::$global['DB']->insert_id();
    }

    /**
     * Depth of nested Transaction() calls
     */
    private int $transactionLevel = 0;

    /**
     * PM / email to send after Commit
     */
    private array $pendingNotices = [];

    /**
     * DB start transaction
     * @return $this
     */
    public function Transaction() : self
    {
        if( $this->transactionLevel === 0 )
        {
            self::$global['DB']->query('START TRANSACTION');
        }

        $this->transactionLevel++;

        return $this;
    }

    /**
     * DB commit transaction
     * @return void
     */
    public function Commit() : void
    {
        if( $this->transactionLevel <= 0 )
        {
            return;
        }

        $this->transactionLevel--;

        if( $this->transactionLevel === 0 )
        {
            self::$global['DB']->query('COMMIT');
            $this->flushNotices();
        }
    }

    /**
     * DB cancel transaction
     * @return void
     */
    public function Rollback() : void
    {
        if( $this->transactionLevel <= 0 )
        {
            return;
        }

        $this->transactionLevel = 0;
        $this->pendingNotices = [];
        self::$global['DB']->query('ROLLBACK');
    }

    /**
     * Списать
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function From(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        $sum = abs($sum);

        if( $sum <= 0 )
        {
            return $this;
        }

        $field = self::getBalanceField();
        $sumSql = number_format($sum, 2, '.', '');
        $getUser = $this->getUser($userId, $userLogin, $this->transactionLevel > 0);

        self::$global['DB']->query(
            "UPDATE " . USERPREFIX . "_users
             SET {$field} = {$field} - {$sumSql}
             WHERE user_id = " . intval($getUser['user_id']) . "
               AND {$field} >= {$sumSql}"
        );

        if( (int) self::$global['DB']->get_affected_rows() !== 1 )
        {
            throw new BalanceException('balance.check');
        }

        $this->rememberBalance($getUser, -$sum);

        return $this;
    }

    /**
     * Начислить
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function To(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        $sum = abs($sum);

        if( $sum <= 0 )
        {
            return $this;
        }

        $field = self::getBalanceField();
        $sumSql = number_format($sum, 2, '.', '');
        $getUser = $this->getUser($userId, $userLogin, $this->transactionLevel > 0);

        self::$global['DB']->query(
            "UPDATE " . USERPREFIX . "_users
             SET {$field} = {$field} + {$sumSql}
             WHERE user_id = " . intval($getUser['user_id'])
        );

        if( (int) self::$global['DB']->get_affected_rows() !== 1 )
        {
            throw new BalanceException('user.not_found:' . $userId . $userLogin);
        }

        $this->rememberBalance($getUser, $sum);

        return $this;
    }

    /**
     * Запись в журнал
     * @param int $userId
     * @param string $userLogin
     * @param float $plus
     * @param float $minus
     * @param string $comment
     * @param int $plugin_id
     * @param string $plugin_name
     * @param bool $pm
     * @param bool $email
     * @return $this
     * @throws BalanceException
     * @throws \Exception
     */
    public function Comment(
        int $userId = 0,
        string $userLogin = '',
        float $plus = 0,
        float $minus = 0,
        string $comment = '',
        int $plugin_id = 0,
        string $plugin_name = 'api',
        bool $pm = false,
        bool $email = false) : self
    {
        $getUser = $this->getUser($userId, $userLogin);
        $balance_after = $getUser[self::getBalanceField()] + $plus - $minus;
        $currency = $this->Declension( $plus ?: $minus );

        $comment = self::$global['DB']->safesql($comment);
        $comment = addslashes( $comment );

        $plugin_name = self::$global['DB']->safesql($plugin_name);

        $ip = self::$global['DB']->safesql($_SERVER['REMOTE_ADDR'] ?? '');
        $agent = self::$global['DB']->safesql($_SERVER['HTTP_USER_AGENT'] ?? '');

        self::$global['DB']->query( "INSERT INTO " . PREFIX . "_billing_history
							(history_plugin, history_plugin_id, history_user_name, history_ip, history_agent_info, history_plus, history_minus, history_balance, history_currency, history_text, history_date) values
							('{$plugin_name}', '{$plugin_id}', '{$getUser['name']}', '{$ip}', '{$agent}', '{$plus}', '{$minus}', '{$balance_after}', '{$currency}', '{$comment}', '" . self::$global['TIME'] . "')" );

        $userReportBalance = $getUser [self::getBalanceField()] + $plus - $minus;

        # Событие в плагины
        #
        $this->hook_data = [
            'userId' => $getUser['user_id'],
            'userLogin' => $getUser['name'],
            'plus' => $plus,
            'minus' => $minus,
            'balance' => $userReportBalance,
            'comment' => $comment,
            'plugin_id' => $plugin_id,
            'plugin_name' => $plugin_name
        ];

        # Уведомления — после Commit, чтобы ЛС не открывал свою транзакцию внутри оплаты
        #
        if( $pm or $email )
        {
            $this->pendingNotices[] = [
                'pm' => $pm,
                'email' => $email,
                'userId' => $userId,
                'userLogin' => $getUser['name'],
                'plus' => $plus,
                'minus' => $minus,
                'comment' => $comment,
                'balance' => $userReportBalance,
                'currency' => $currency,
            ];

            if( $this->transactionLevel === 0 )
            {
                $this->flushNotices();
            }
        }

        return $this;
    }

    /**
     * Send queued PM / email. Failures must not roll back money.
     */
    private function flushNotices() : void
    {
        $notices = $this->pendingNotices;
        $this->pendingNotices = [];

        foreach( $notices as $notice )
        {
            $date = function_exists('langdate')
                ? langdate( "j F Y  G:i", self::$global['TIME'] )
                : date( "j.m.Y H:i", (int) self::$global['TIME'] );

            $sum = $notice['plus']
                ? "+" . $notice['plus'] . " " . $notice['currency']
                : "-" . $notice['minus'] . " " . $notice['currency'];

            $tags = [
                '{date}' => $date,
                '{login}' => $notice['userLogin'],
                '{sum}' => $sum,
                '{comment}' => strip_tags($notice['comment']),
                '{balance}' => $this->Convert(value: $notice['balance'], separator_space: true, declension: true),
            ];

            try
            {
                if( $notice['pm'] )
                {
                    (new Message(userId: $notice['userId'], name: $notice['userLogin']))
                        ->loadTemplate('balance')
                        ->buildTemplate($tags)
                        ->send();
                }

                if( $notice['email'] )
                {
                    (new Email(userId: $notice['userId'], name: $notice['userLogin']))
                        ->loadTemplate('balance')
                        ->buildTemplate($tags)
                        ->send();
                }
            }
            catch( \Throwable )
            {
            }
        }
    }

    /**
     * Отправить событие в плагины
     * @param mixed ...$hook_new_data
     * @return Balance
     */
    public function sendEvent(...$hook_new_data) : self
    {
        if( $this->hook_num <= self::MAX_HOOK_EVENTS )
        {
            $this->hook_num += 1;

            if( $hook_new_data )
            {
                $this->hook_data = array_merge($this->hook_data , $hook_new_data);
            }

            foreach( self::hookRegistry() as $plugin )
            {
                if( in_array('init', get_class_methods($plugin['hook']), true) )
                {
                    $plugin['hook']->init($plugin['config']);
                }

                $plugin['hook']->pay(
                    $this->hook_data['userLogin'] ?? '',
                    $this->hook_data['plus'] ?? null,
                    $this->hook_data['minus'] ?? null,
                    $this->hook_data['balance'] ?? 0,
                    $this->hook_data['comment'] ?? '',
                    $this->hook_data['plugin_name'] ?? '',
                    $this->hook_data['plugin_id'] ?? 0
                );
            }
        }

        return $this;
    }

    /**
     * Plugins with hook.class.php, loaded once per request
     * @return array<string, array{hook: \Billing\Hooks, config: array}>
     */
    private static function hookRegistry() : array
    {
        if( self::$hookRegistry !== null )
        {
            return self::$hookRegistry;
        }

        self::$hookRegistry = [];

        if( ! class_exists('\Billing\Hooks') )
        {
            require_once ENGINE_DIR . '/modules/billing/core/hooks.php';
        }

        $pluginsDir = ENGINE_DIR . '/modules/billing/plugins';

        if( ! is_dir($pluginsDir) )
        {
            return self::$hookRegistry;
        }

        foreach( scandir($pluginsDir) ?: [] as $name )
        {
            if( $name === '.' || $name === '..' || ! preg_match('/^[a-z0-9_-]+$/i', $name) )
            {
                continue;
            }

            $hookFile = $pluginsDir . '/' . $name . '/hook.class.php';
            $configFile = ENGINE_DIR . '/data/billing/plugin.' . $name . '.php';

            if( ! is_file($hookFile) || ! is_file($configFile) )
            {
                continue;
            }

            $Hook = include $hookFile;

            if( $Hook instanceof \Billing\Hooks )
            {
                self::$hookRegistry[$name] = [
                    'hook' => $Hook,
                    'config' => include $configFile,
                ];
            }
        }

        return self::$hookRegistry;
    }

    /**
     * Проверить достаточно ли средств
     * @param int $userId
     * @param string $userLogin
     * @param float $sum
     * @return $this
     * @throws BalanceException
     */
    public function Check(int $userId = 0, string $userLogin = '', float $sum = 0) : self
    {
        $getUser = $this->getUser($userId, $userLogin, $this->transactionLevel > 0);

        if( (float) $getUser[self::getBalanceField()] < (float) $sum )
        {
            throw new BalanceException('balance.check');
        }

        return $this;
    }

    /**
     * Найти пользователя
     * @param int $userId
     * @param string $userLogin
     * @param bool $forUpdate
     * @return array
     * @throws BalanceException
     */
    protected function getUser(int $userId = 0, string $userLogin = '', bool $forUpdate = false) : array
    {
        $field = self::getBalanceField();
        $cacheKey = md5($userId . $userLogin);

        if( ! $forUpdate )
        {
            if( ( $userId and $userId == (self::$global['USER']['user_id'] ?? 0) ) or ($userLogin and $userLogin == (self::$global['USER']['name'] ?? '') ) )
            {
                return self::$global['USER'];
            }

            if( isset(self::$buffer[$cacheKey]) )
            {
                return self::$buffer[$cacheKey];
            }
        }

        $lock = ( $forUpdate and $this->transactionLevel > 0 ) ? ' FOR UPDATE' : '';

        if( $userId )
        {
            self::$global['DB']->query( "SELECT user_id, name, email, {$field} FROM " . USERPREFIX . "_users WHERE user_id = " . intval($userId) . $lock );
        }
        else if( $userLogin !== '' )
        {
            self::$global['DB']->query( "SELECT user_id, name, email, {$field} FROM " . USERPREFIX . "_users WHERE name = '" . self::$global['DB']->safesql( $userLogin ) . "'" . $lock );
        }
        else
        {
            throw new BalanceException('user.not_found:');
        }

        if( ! $user = self::$global['DB']->get_row())
        {
            throw new BalanceException('user.not_found:' . $userId . $userLogin);
        }

        self::$buffer[$cacheKey] = $user;

        if( (int) (self::$global['USER']['user_id'] ?? 0) === (int) $user['user_id'] )
        {
            self::$global['USER'][$field] = $user[$field];
        }

        return $user;
    }

    /**
     * Keep in-memory balance in sync after From/To
     */
    private function rememberBalance(array $user, float $delta) : void
    {
        $field = self::getBalanceField();
        $newBalance = (float) ($user[$field] ?? 0) + $delta;

        foreach( self::$buffer as $key => $cached )
        {
            if( (int) ($cached['user_id'] ?? 0) === (int) $user['user_id'] )
            {
                self::$buffer[$key][$field] = $newBalance;
            }
        }

        if( (int) (self::$global['USER']['user_id'] ?? 0) === (int) $user['user_id']
            or ( self::$global['USER']['name'] ?? '' ) === ( $user['name'] ?? '' ) )
        {
            self::$global['USER'][$field] = $newBalance;
        }
    }

    /**
     * @param float|null $value
     * @param bool|null $separator_space
     * @param string|null $format
     * @param bool|null $declension
     * @return float|string
     */
    public function Convert(mixed $value = 0, ?bool $separator_space = false, ?string $format = '', ?bool $declension = false) : float|string
    {
        $value = floatval($value);

        $format = $format ?: self::$global['BILLING']['format'];

        $decimal = $format == 'int' ? 0 : 2;

        $decimal_separator = $format == 'int' ? '' : '.';
        $separator = $separator_space ? ' ' : '';

        return number_format(
            $value,
            $decimal,
            $decimal_separator,
            $separator
        )
            .
            ( $declension ? ' ' . $this->Declension($value) : '' );
    }

    /**
     * @param float $value
     * @param array|null $titles
     * @return string
     */
    public function Declension(mixed $value, ?array $titles = []) : string
    {
        $value = abs(floatval($value));

        $titles = $titles ?: explode(',', self::$global['BILLING']['currency']);

        if( count( $titles ) != 3 )
        {
            return $titles[0];
        }

        $cases = [2, 0, 1, 1, 1, 2];

        return $titles[ ($value % 100 > 4 && $value % 100 < 20) ? 2 : $cases[min($value % 10, 5)] ] ?? '';
    }

    /**
     * Отправить коммиссию системному пользователю
     * @param float $sum
     * @param string $comment
     * @param string $plugin
     * @param int $plugin_id
     * @return $this
     * @throws BalanceException
     */
    public function sendCommission(float $sum, string $comment, string $plugin = '', int $plugin_id = 0) : self
    {
        if( self::$global['BILLING']['commission'] and $systemName = self::$global['BILLING']['admin'] )
        {
            $this->Comment(
                userLogin: $systemName,
                plus: $sum,
                comment: self::$global['LANG']['commission'] . $comment,
                plugin_id: $plugin_id,
                plugin_name: $plugin
            )->To(
                userLogin: $systemName,
                sum: $sum
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    protected static function getBalanceField() : string
    {
        return \Billing\Database::safeField( (string) ( self::$global['BILLING']['fname'] ?? 'user_balance' ) );
    }
}
