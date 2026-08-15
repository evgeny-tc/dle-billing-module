<?php

use Billing\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    private FakeDb $db;
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new FakeDb();
        $this->database = new Database($this->db, 'user_balance', 1_700_000_100);
    }

    public function testClaimInvoiceIsAtomic(): void
    {
        $this->db->invoices[7] = [
            'invoice_id' => 7,
            'invoice_date_pay' => 0,
            'invoice_date_creat' => 1_700_000_000,
            'invoice_paysys' => '',
        ];

        $this->assertTrue($this->database->claimInvoice(7, 'yoomoney', 'wallet-1'));
        $this->assertSame(1_700_000_100, $this->db->invoices[7]['invoice_date_pay']);
        $this->assertFalse($this->database->claimInvoice(7, 'yoomoney', 'wallet-1'));
    }

    public function testExpiredInvoiceReturnsCouponThenDeletes(): void
    {
        $this->db->coupons[3] = [
            'coupon_id' => 3,
            'coupon_use' => 'alice',
            'coupon_key' => 'SAVE10',
        ];

        $this->db->invoices[11] = [
            'invoice_id' => 11,
            'invoice_date_pay' => 0,
            'invoice_date_creat' => 1_699_000_000,
            'invoice_payer_info' => json_encode([
                'coupon' => ['coupon_id' => 3, 'coupon_key' => 'SAVE10'],
            ], JSON_UNESCAPED_UNICODE),
        ];

        $this->db->invoices[12] = [
            'invoice_id' => 12,
            'invoice_date_pay' => 0,
            'invoice_date_creat' => 1_700_000_050,
            'invoice_payer_info' => '',
        ];

        $this->database->purgeExpiredInvoices(1_700_000_000);

        $this->assertSame('', $this->db->coupons[3]['coupon_use']);
        $this->assertArrayNotHasKey(11, $this->db->invoices);
        $this->assertArrayHasKey(12, $this->db->invoices);
    }

    public function testUseCouponIsIdempotent(): void
    {
        $this->db->coupons[4] = [
            'coupon_id' => 4,
            'coupon_use' => '',
        ];

        $invoice = [
            'invoice_id' => 0,
            'invoice_user_name' => 'alice',
        ];

        $this->assertTrue($this->database->useCoupon(['coupon_id' => 4], $invoice));
        $this->assertSame('alice', $this->db->coupons[4]['coupon_use']);
        $this->assertFalse($this->database->useCoupon(['coupon_id' => 4], $invoice));
    }
}
