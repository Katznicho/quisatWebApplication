<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('interest_rates')->insert([
            [
                'type' => 'Flat Rate',
                'description' => 'A flat rate interest is applied to the principal loan amount for the entire loan term. The interest is calculated based on the original principal, meaning the interest amount remains the same throughout the loan period.',
                'formula' => 'Interest = Principal × Flat Rate × Loan Term',
                'status' => 'active',
            ],
            [
                'type' => 'Simple Interest',
                'description' => 'Simple interest is calculated only on the principal amount. The interest does not compound, making it easier to compute.',
                'formula' => 'Interest = Principal × Rate × Time',
                'status' => 'active',
            ],
            [
                'type' => 'Compound Interest',
                'description' => 'Compound interest is calculated on both the principal and accumulated interest. This results in higher interest over time as the interest grows exponentially.',
                'formula' => 'A = P(1 + r/n)^(nt)',
                'status' => 'inactive',
            ],
            [
                'type' => 'Reducing Balance',
                'description' => 'Interest is calculated on the remaining loan balance at the end of each period. As the principal decreases, the interest paid also reduces over time.',
                'formula' => 'Interest = Remaining Principal × Rate',
                'status' => 'inactive',
            ],
            [
                'type' => 'Daily Interest',
                'description' => 'Interest is calculated daily on the outstanding loan balance. This is common in short-term loans and credit facilities.',
                'formula' => 'Interest = Principal × Rate / 365 × Days',
                'status' => 'active',
            ],
            [
                'type' => 'Fixed Interest',
                'description' => 'Fixed interest rates remain constant throughout the loan term. This provides predictability in repayment schedules.',
                'formula' => 'Interest = Principal × Fixed Rate × Loan Term',
                'status' => 'active',
            ],
            [
                'type' => 'Floating Interest',
                'description' => 'A floating interest rate changes based on market conditions or a benchmark interest rate. This means the amount paid in interest can vary over time.',
                'formula' => 'Interest = Principal × Floating Rate (varies)',
                'status' => 'inactive',
            ],
            [
                'type' => 'Amortized Interest',
                'description' => 'Amortization spreads interest payments across multiple periods, where a portion of each payment covers both interest and principal reduction.',
                'formula' => 'EMI = [P × r × (1+r)^n] / [(1+r)^n - 1]',
                'status' => 'inactive',
            ],
            [
                'type' => 'Precomputed Interest',
                'description' => 'The total interest is calculated at the start and added to the principal. Borrowers make fixed payments over the loan term.',
                'formula' => 'Total Interest = Principal × Rate × Term',
                'status' => 'inactive',
            ],
            [
                'type' => 'Balloon Interest',
                'description' => 'In a balloon loan, smaller periodic payments are made, with a large final payment at the end.',
                'formula' => 'Balloon Payment = Remaining Principal + Final Interest',
                'status' => 'inactive',
            ],
            [
                'type' => 'Negative Amortization',
                'description' => 'Payments are lower than the accrued interest, leading to increasing loan balance over time.',
                'formula' => 'Unpaid Interest = Interest - Monthly Payment',
                'status' => 'inactive',
            ],
            [
                'type' => 'Tiered Interest',
                'description' => 'Interest rates vary based on loan amounts or balance tiers. Different portions of the loan accrue interest at different rates.',
                'formula' => 'Total Interest = (P1 × R1) + (P2 × R2) + ...',
                'status' => 'inactive',
            ],
            [
                'type' => 'Penalty Interest',
                'description' => 'An extra interest charge applied when a borrower fails to make a payment on time.',
                'formula' => 'Penalty = Outstanding Amount × Penalty Rate × Overdue Days',
                'status' => 'inactive',
            ],
            [
                'type' => 'Prime-Based Interest',
                'description' => 'Interest rate is based on the prime rate set by financial institutions, plus a margin.',
                'formula' => 'Interest = Prime Rate + Margin',
                'status' => 'inactive',
            ],
            [
                'type' => 'Interest-Only Loan',
                'description' => 'Borrowers pay only the interest for a period, with the principal remaining unchanged.',
                'formula' => 'Interest = Principal × Rate',
                'status' => 'inactive',
            ],
            [
                'type' => 'Discounted Interest',
                'description' => 'The interest amount is deducted from the principal upfront, meaning the borrower receives a lower disbursed amount.',
                'formula' => 'Disbursed Amount = Principal - Interest',
                'status' => 'inactive',
            ],
        ]);
    }
}

//php artisan db:seed --class=InterestRatesSeeder

