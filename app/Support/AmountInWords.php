<?php

namespace App\Support;

/**
 * Amount in words, Indian numbering.
 *
 * Crore / lakh / thousand rather than million / billion, because every document
 * in Module D is an Indian statutory or commercial form — a salary certificate
 * reading "one million two hundred thousand" would be wrong on a Form 16.
 *
 *   1234567.89 → "Rupees Twelve Lakh Thirty Four Thousand Five Hundred
 *                 Sixty Seven and Eighty Nine Paise Only"
 */
class AmountInWords
{
    private const ONES = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    private const TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    /**
     * The full currency phrase for a document.
     *
     * @param  string  $currency  the unit name — "Rupees" on Indian forms
     * @param  string  $fraction  the sub-unit name — "Paise"
     */
    public static function currency(
        float|int|string|null $amount,
        string $currency = 'Rupees',
        string $fraction = 'Paise',
    ): string {
        $amount = (float) ($amount ?? 0);
        $negative = $amount < 0;
        $amount = abs($amount);

        // Round to paise first, so 0.999 reads as one rupee rather than
        // "Zero Rupees and Ninety Nine Paise".
        $rounded = round($amount, 2);
        $whole = (int) floor($rounded);
        $paise = (int) round(($rounded - $whole) * 100);

        // Rounding can carry into the rupee, e.g. 9.999 → 10.00.
        if ($paise === 100) {
            $whole++;
            $paise = 0;
        }

        $words = $currency.' '.(self::number($whole) ?: 'Zero');

        if ($paise > 0) {
            $words .= ' and '.self::number($paise).' '.$fraction;
        }

        return ($negative ? 'Minus ' : '').$words.' Only';
    }

    /**
     * A whole number in words, Indian grouping.
     *
     * Returns an empty string for zero so callers can decide whether "Zero"
     * belongs in their sentence.
     */
    public static function number(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        if ($number < 0) {
            return 'Minus '.self::number(abs($number));
        }

        $parts = [];

        // Indian grouping: crore (10^7), lakh (10^5), thousand, then the last
        // three digits as hundreds + tens.
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $divisor => $label) {
            if ($number >= $divisor) {
                $count = intdiv($number, $divisor);
                $parts[] = self::number($count).' '.$label;
                $number %= $divisor;
            }
        }

        if ($number >= 100) {
            $parts[] = self::ONES[intdiv($number, 100)].' Hundred';
            $number %= 100;
        }

        if ($number > 0) {
            $parts[] = self::underHundred($number);
        }

        return implode(' ', array_filter($parts));
    }

    /** 1–99 — the teens are irregular, so they get their own lookup. */
    private static function underHundred(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        $tens = self::TENS[intdiv($number, 10)];
        $ones = self::ONES[$number % 10];

        return trim($tens.' '.$ones);
    }
}
