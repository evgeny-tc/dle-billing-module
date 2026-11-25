<?php

namespace CoinsPay\Dle;

use RuntimeException;

/**
 * Polling of CoinsPay invoices when a webhook did not come: by the cron address, on the invoice
 * page of the user and on the settings page of the admin. From the FOSSBilling module (Poller.php).
 *
 * @copyright Copyright (c) 2026 CoinsPay
 * @license   MIT; see LICENSE
 */
final class Poller
{
    public const BATCH = 25;

    /** Seconds one run may take: the cron; pages get less. */
    public const BUDGET = 20;

    /** Seconds to the next poll of an invoice the API failed on. */
    public const BACKOFF = 300;

    private Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Checks the invoices that are due, within a limit of count and time.
     *
     * @return int How many were checked.
     */
    public function run(int $budget = self::BUDGET, int $batch = self::BATCH): int
    {
        $started = time();
        $failures = 0;
        $checked = 0;
        foreach ($this->module->repo()->due($batch) as $row) {
            if (time() - $started >= $budget || $failures >= 3) {
                break;
            }
            if ($this->check($row)) {
                $checked++;
            } else {
                $failures++;
            }
        }
        $this->module->repo()->kvSet('poll_last', (string) time());

        return $checked;
    }

    /** One CoinsPay invoice now, whatever its next poll: the "Check" link of the panel. */
    public function checkNow(array $row): bool
    {
        return $this->check($row);
    }

    /** @return bool False when the API failed. */
    private function check(array $row): bool
    {
        // Made with a key of the other mode: the admin switched keys, and this one does not know
        // the invoice. Asked again later, when the key of its mode may be back.
        if ((bool) $row['test'] !== $this->module->settings()->isTestMode()) {
            $this->postpone($row);

            return true;
        }
        try {
            if ($row['status'] === 'open' && $this->unwanted($row)) {
                // Paid otherwise or deleted: DLE-Billing deletes old unpaid invoices without
                // events, so polling finds it. Cancelling also applies its state.
                $this->module->payment()->cancelRow($row);

                return true;
            }
            $this->module->sync()->apply($this->module->client()->invoice($row['invoice_id']));

            return true;
        } catch (ApiError $e) {
            $this->module->host()->log('error', 'polling ' . $row['invoice_id'] . ': ' . $e->summary());
            if ($e->isNotFound()) {
                // Unknown to this key: a key of another shop now. Not dropped: the old key may come back.
                $this->postpone($row);

                return true;
            }
            // A few minutes later, so an invoice that keeps failing does not hold up the others.
            $this->postpone($row, self::BACKOFF);

            return false;
        } catch (RuntimeException $e) {
            // A busy invoice or money DLE-Billing did not add: the next run tries again.
            $this->module->host()->log('error', 'polling ' . $row['invoice_id'] . ': ' . $e->getMessage());

            return true;
        }
    }

    /** Whether an open CoinsPay invoice no longer has an invoice to pay. */
    private function unwanted(array $row): bool
    {
        $dle = Sync::same($this->module->host()->invoice((int) $row['dle_invoice']), $row);

        return !$dle || $dle['paid'] || (string) $dle['user'] !== (string) $row['user_name'];
    }

    /** The next poll later (30 minutes by default), within the polling time of the invoice. */
    private function postpone(array $row, int $seconds = Sync::POLL_CLOSED): void
    {
        $next = time() + $seconds;
        $this->module->repo()->update($row['invoice_id'], ['next_poll' => $next < (int) $row['poll_until'] ? $next : null]);
    }
}
