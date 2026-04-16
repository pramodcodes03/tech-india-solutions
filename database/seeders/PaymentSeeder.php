<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $accountsAdminId = 5; // Lakshmi - Accounts

        // Get all invoices
        $invoices = DB::table('invoices')->get()->keyBy('id');

        $paymentCounter = 0;
        $payments = [];

        // Invoice 1 (paid) - 2 payments covering full amount
        $inv1 = $invoices[1];
        $half1 = round($inv1->grand_total * 0.6, 2);
        $rest1 = round($inv1->grand_total - $half1, 2);
        $payments[] = ['invoice_id' => 1, 'customer_id' => $inv1->customer_id, 'amount' => $half1, 'mode' => 'bank_transfer', 'reference_no' => 'NEFT-20260115-001', 'days_ago' => 70];
        $payments[] = ['invoice_id' => 1, 'customer_id' => $inv1->customer_id, 'amount' => $rest1, 'mode' => 'bank_transfer', 'reference_no' => 'NEFT-20260125-002', 'days_ago' => 60];

        // Invoice 2 (paid) - 1 full payment
        $inv2 = $invoices[2];
        $payments[] = ['invoice_id' => 2, 'customer_id' => $inv2->customer_id, 'amount' => $inv2->grand_total, 'mode' => 'upi',           'reference_no' => 'UPI-20260205-003',  'days_ago' => 50];

        // Invoice 3 (paid) - 2 payments
        $inv3 = $invoices[3];
        $half3 = round($inv3->grand_total * 0.5, 2);
        $rest3 = round($inv3->grand_total - $half3, 2);
        $payments[] = ['invoice_id' => 3, 'customer_id' => $inv3->customer_id, 'amount' => $half3, 'mode' => 'cheque',        'reference_no' => 'CHQ-445678',        'days_ago' => 40];
        $payments[] = ['invoice_id' => 3, 'customer_id' => $inv3->customer_id, 'amount' => $rest3, 'mode' => 'bank_transfer', 'reference_no' => 'NEFT-20260225-004', 'days_ago' => 30];

        // Invoice 4 (partial) - 1 partial payment (~40%)
        $inv4 = $invoices[4];
        $partial4 = round($inv4->grand_total * 0.4, 2);
        $payments[] = ['invoice_id' => 4, 'customer_id' => $inv4->customer_id, 'amount' => $partial4, 'mode' => 'cash',          'reference_no' => null,               'days_ago' => 22];

        // Invoice 5 (partial) - 2 partial payments (~60% total)
        $inv5 = $invoices[5];
        $p5a = round($inv5->grand_total * 0.3, 2);
        $p5b = round($inv5->grand_total * 0.3, 2);
        $payments[] = ['invoice_id' => 5, 'customer_id' => $inv5->customer_id, 'amount' => $p5a, 'mode' => 'upi',            'reference_no' => 'UPI-20260318-005',  'days_ago' => 15];
        $payments[] = ['invoice_id' => 5, 'customer_id' => $inv5->customer_id, 'amount' => $p5b, 'mode' => 'card',           'reference_no' => 'CARD-20260322-006', 'days_ago' => 10];

        // Invoice 6 (unpaid) - no payments
        // Invoice 7 (unpaid) - no payments

        // Invoice 8 (overdue) - 1 small payment
        $inv8 = $invoices[8];
        $overduePay = round($inv8->grand_total * 0.15, 2);
        $payments[] = ['invoice_id' => 8, 'customer_id' => $inv8->customer_id, 'amount' => $overduePay, 'mode' => 'cash', 'reference_no' => null, 'days_ago' => 35];

        // Track totals per invoice
        $invoicePaid = [];

        foreach ($payments as $pay) {
            $paymentCounter++;
            $payNum = sprintf('PAY-2026-%04d', $paymentCounter);
            $payDate = $now->copy()->subDays($pay['days_ago']);

            DB::table('payments')->insert([
                'payment_number' => $payNum,
                'invoice_id' => $pay['invoice_id'],
                'customer_id' => $pay['customer_id'],
                'payment_date' => $payDate->toDateString(),
                'amount' => $pay['amount'],
                'mode' => $pay['mode'],
                'reference_no' => $pay['reference_no'],
                'notes' => null,
                'created_by' => $accountsAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $payDate,
                'updated_at' => $payDate,
                'deleted_at' => null,
            ]);

            if (! isset($invoicePaid[$pay['invoice_id']])) {
                $invoicePaid[$pay['invoice_id']] = 0;
            }
            $invoicePaid[$pay['invoice_id']] += $pay['amount'];
        }

        // Update invoice amount_paid and balance_due
        foreach ($invoicePaid as $invId => $totalPaid) {
            $invoice = $invoices[$invId];
            $balanceDue = round($invoice->grand_total - $totalPaid, 2);

            DB::table('invoices')->where('id', $invId)->update([
                'amount_paid' => round($totalPaid, 2),
                'balance_due' => max($balanceDue, 0),
            ]);
        }
    }
}
