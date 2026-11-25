<?php

namespace CoinsPay\Dle;

/**
 * The module in one place: settings, tables, API client and texts for one request.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
class Module
{
    private Host $host;

    private array $values;

    private int $timeout;

    private ?Settings $settings = null;

    private ?Repo $repo = null;

    private ?Currencies $currencies = null;

    private ?ApiClient $client = null;

    /**
     * @param array $values  The configuration of the payment system in DLE-Billing.
     * @param int   $timeout Seconds of an API request: short inside pages.
     */
    public function __construct(Host $host, array $values, int $timeout = ApiClient::TIMEOUT)
    {
        $this->host = $host;
        $this->values = $values;
        $this->timeout = $timeout;
    }

    public function host(): Host
    {
        return $this->host;
    }

    public function settings(): Settings
    {
        return $this->settings ??= new Settings($this->values);
    }

    public function repo(): Repo
    {
        return $this->repo ??= new Repo($this->host->db(), $this->host->prefix(), $this->host->historyTable());
    }

    public function currencies(): Currencies
    {
        return $this->currencies ??= new Currencies($this);
    }

    public function client(): ApiClient
    {
        return $this->client ??= $this->settings()->client($this->userAgent(), $this->timeout);
    }

    /** For tests: an API client of their own. */
    public function setClient(ApiClient $client): void
    {
        $this->client = $client;
    }

    /** For tests: tables in memory. */
    public function setRepo(Repo $repo): void
    {
        $this->repo = $repo;
    }

    /** Texts in the language of the site: the log and the notes of the balance history. */
    public function adminText(): Text
    {
        return new Text($this->host->adminLanguage());
    }

    /** Texts of the page shown now, in the language of its reader. */
    public function pageText(): Text
    {
        return new Text($this->host->userLanguage());
    }

    public function sync(): Sync
    {
        return new Sync($this);
    }

    public function payment(): Payment
    {
        return new Payment($this);
    }

    public function poller(): Poller
    {
        return new Poller($this);
    }

    public function webhook(): Webhook
    {
        return new Webhook($this);
    }

    public function userAgent(): string
    {
        return 'CoinsPay-DLE-Billing/' . Settings::VERSION . '; PHP/' . PHP_VERSION;
    }

    /** A debug line of the log, when the admin turned debugging on. */
    public function debug(string $message): void
    {
        if ($this->settings()->flag('debug')) {
            $this->host->log('info', $message);
        }
    }
}
