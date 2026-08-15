<?php

use Billing\Tbank;
use PHPUnit\Framework\TestCase;

final class TbankTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(Tbank::class)) {
            require_once MODULE_PATH . '/payments/tbank/adm.settings.php';
        }

        Tbank::resetPayload();
    }

    public function testMakeTokenMatchesOfficialExample(): void
    {
        $token = Tbank::makeToken([
            'TerminalKey' => '1234567890DEMO',
            'OrderId' => '000000',
            'Success' => true,
            'Status' => 'AUTHORIZED',
            'PaymentId' => '0000000',
            'ErrorCode' => '0',
            'Amount' => '1111',
            'CardId' => '000000',
            'Pan' => '200000******0000',
            'ExpDate' => '1111',
            'RebillId' => '000000',
            'Token' => 'must-be-ignored',
            'DATA' => ['ignored' => 'yes'],
        ], '11111111111');

        $this->assertSame(
            '1c0964277d0213349243065a0d5b838b8e90d2d25f740d0f2767836e710e80c8',
            $token
        );
    }

    public function testCheckOutConfirmsOnlyMatchingPayment(): void
    {
        $payment = new Tbank();
        $config = [
            'terminal_key' => 'DemoTerminal',
            'password' => 'secret',
        ];
        $invoice = ['invoice_pay' => '10.00'];

        $authorized = [
            'TerminalKey' => 'DemoTerminal',
            'OrderId' => '15',
            'Success' => true,
            'Status' => 'AUTHORIZED',
            'PaymentId' => '99',
            'ErrorCode' => '0',
            'Amount' => 1000,
        ];
        $authorized['Token'] = Tbank::makeToken($authorized, 'secret');

        $this->assertSame('OK', $payment->check_out($authorized, $config, $invoice));

        Tbank::resetPayload();

        $confirmed = $authorized;
        $confirmed['Status'] = 'CONFIRMED';
        $confirmed['Token'] = Tbank::makeToken($confirmed, 'secret');

        $this->assertTrue($payment->check_out($confirmed, $config, $invoice));
    }

    public function testCheckOutRejectsBadTokenAndSum(): void
    {
        $payment = new Tbank();
        $config = [
            'terminal_key' => 'DemoTerminal',
            'password' => 'secret',
        ];

        $data = [
            'TerminalKey' => 'DemoTerminal',
            'OrderId' => '15',
            'Success' => true,
            'Status' => 'CONFIRMED',
            'ErrorCode' => '0',
            'Amount' => 1000,
            'Token' => 'deadbeef',
        ];

        $this->assertSame('Error hash', $payment->check_out($data, $config, ['invoice_pay' => '10.00']));

        Tbank::resetPayload();

        $data['Token'] = Tbank::makeToken($data, 'secret');
        $this->assertSame(
            'Error sum: expected 2500 kopecks, got 1000',
            $payment->check_out($data, $config, ['invoice_pay' => '25.00'])
        );
    }
}
