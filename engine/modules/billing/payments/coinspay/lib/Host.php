<?php

namespace CoinsPay\Dle;

/**
 * What the module needs of DLE and DLE-Billing. DleHost is the real one; the unit tests have a
 * fake.
 *
 * DLE-Billing keeps a balance per user and invoices ("квитанции"): a top-up adds its amount to
 * the balance, a service invoice (a plugin of DLE-Billing behind it: a paid group, paid content)
 * runs the handler of its plugin when paid. The module adds what a CoinsPay invoice brought to
 * the balance, then closes the invoice from it.
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
interface Host
{
    /** SQL on the connection of DLE. */
    public function db(): Db;

    /** Prefix of the DLE tables (PREFIX). */
    public function prefix(): string;

    /** The balance history table of DLE-Billing. */
    public function historyTable(): string;

    /**
     * What tells this site from another on the same CoinsPay shop or database server, the same
     * for every request: the address of the site and the database.
     */
    public function siteKey(): string;

    /** Language of what the module writes down (the log, the notes of the history): the language of the site. */
    public function adminLanguage(): string;

    /** Language of the person on the page now. */
    public function userLanguage(): string;

    /** Whether the person on the page now is an administrator of DLE (group 1). */
    public function isAdmin(): bool;

    /**
     * The user signed in on the page now, or null.
     *
     * @return array|null {id, name}
     */
    public function currentUser(): ?array;

    /**
     * A DLE-Billing invoice, fresh from the database.
     *
     * @return array|null {id, user (login), get (to the balance), pay, paid (bool), handler
     *                    ("plugin:file" of a service, '' for a top-up), info}
     */
    public function invoice(int $invoiceId): ?array;

    /**
     * A user, fresh from the database.
     *
     * @return array|null {id, name, balance}
     */
    public function user(int $userId): ?array;

    /** What the module added to the balance for one CoinsPay invoice (its row), in total. */
    public function credited(int $rowId): string;

    /**
     * Adds an amount to the balance of a user for one CoinsPay invoice: a row of the balance
     * history with the plugin "coinspay" and the row id, as DLE-Billing writes its own payments.
     */
    public function addFunds(int $userId, string $amount, string $comment, int $rowId): void;

    /**
     * Marks an unpaid top-up paid: the money is on the balance already.
     *
     * @return bool Whether this call marked it (false: paid or gone meanwhile).
     */
    public function markPaid(int $invoiceId): bool;

    /**
     * Pays an unpaid service invoice from the balance of its user, as DLE-Billing does when the
     * user picks the balance: the amount leaves the balance, the invoice is marked paid and the
     * handler of its plugin runs, in one transaction.
     *
     * @param string $cost What it costs in units of the balance (less a coupon, if one was used).
     * @return bool Whether it is paid now; false when the balance does not cover it.
     */
    public function payFromBalance(array $invoice, int $userId, string $cost): bool;

    /** An amount of the balance with its unit, for notes: "100 рублей". */
    public function money(string $amount): string;

    /** The invoice page of the user: back from the payment page. */
    public function invoiceUrl(int $invoiceId): string;

    /** Address of the payment handler of DLE-Billing for the module: the webhook. */
    public function handlerUrl(): string;

    /**
     * A line of the log.
     *
     * @param string $level info, warning or error.
     */
    public function log(string $level, string $message): void;
}
