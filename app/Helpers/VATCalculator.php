<?php

namespace App\Helpers;

use App\Models\Preference;

class VATCalculator
{
    /**
     * Set scale for BCMath operations (2 decimal places for currency)
     */
    private static int $bcScale = 10; // Use 10 for intermediate calculations, round to 2 at the end

    private static function hasBcMath(): bool
    {
        return function_exists('bcadd')
            && function_exists('bcsub')
            && function_exists('bcmul')
            && function_exists('bcdiv');
    }

    private static function normalizeNumber(string $value, int $scale): string
    {
        return number_format((float) $value, $scale, '.', '');
    }

    private static function add(string $left, string $right): string
    {
        if (self::hasBcMath()) {
            return bcadd($left, $right, self::$bcScale);
        }

        return self::normalizeNumber((string) ((float) $left + (float) $right), self::$bcScale);
    }

    private static function sub(string $left, string $right): string
    {
        if (self::hasBcMath()) {
            return bcsub($left, $right, self::$bcScale);
        }

        return self::normalizeNumber((string) ((float) $left - (float) $right), self::$bcScale);
    }

    private static function mul(string $left, string $right): string
    {
        if (self::hasBcMath()) {
            return bcmul($left, $right, self::$bcScale);
        }

        return self::normalizeNumber((string) ((float) $left * (float) $right), self::$bcScale);
    }

    private static function div(string $left, string $right): string
    {
        if ((float) $right === 0.0) {
            return self::normalizeNumber('0', self::$bcScale);
        }

        if (self::hasBcMath()) {
            return bcdiv($left, $right, self::$bcScale);
        }

        return self::normalizeNumber((string) ((float) $left / (float) $right), self::$bcScale);
    }

    /**
     * Calculate VAT and totals based on VAT calculation mode
     * Uses BCMath for precise decimal arithmetic
     * 
     * @param float $price The price (interpretation depends on mode)
     * @param float|null $vatPercentage Optional VAT percentage (defaults to preference)
     * @return array ['net' => float, 'vat' => float, 'gross' => float]
     */
    public static function calculate(float $price, ?float $vatPercentage = null): array
    {
        $vatPercentage = $vatPercentage ?? Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');
        
        // Convert to strings for BCMath
        $price = (string)$price;
        $vatPercentage = (string)$vatPercentage;
        
        if ($vatMode === 'inclusive') {
            // Price includes VAT, extract net and VAT
            // Formula: net = gross / (1 + vat%)
            // Example: 120€ with 20% VAT → net = 120 / 1.20 = 100€, VAT = 20€
            $gross = $price;
            
            // Calculate divisor: 1 + (vatPercentage / 100)
            $divisor = self::add('1', self::div($vatPercentage, '100'));
            $net = self::div($gross, $divisor);
            $vat = self::sub($gross, $net);
        } else {
            // Price is net (VAT exclusive), add VAT
            // Formula: VAT = net * vat%, gross = net + VAT
            // Example: 100€ net with 20% VAT → VAT = 20€, gross = 120€
            $net = $price;
            $vat = self::mul($net, self::div($vatPercentage, '100'));
            $gross = self::add($net, $vat);
        }
        
        return [
            'net' => round((float)$net, 2),
            'vat' => round((float)$vat, 2),
            'gross' => round((float)$gross, 2),
        ];
    }
    
    /**
     * Calculate VAT amount only
     * Uses BCMath for precise decimal arithmetic
     * 
     * @param float $netAmount Net amount (VAT exclusive)
     * @param float|null $vatPercentage Optional VAT percentage
     * @return float VAT amount
     */
    /**
     * VAT on a net (tax-exclusive) base amount.
     * Callers that use inclusive list prices must first convert gross → net via getNetFromGross(),
     * then pass that net here. Do not pass gross into this method.
     */
    public static function calculateVATAmount(float $netAmount, ?float $vatPercentage = null): float
    {
        $vatPercentage = $vatPercentage ?? Preference::get('vat_percentage', 20);

        $netAmount = (string) $netAmount;
        $vatPercentage = (string) $vatPercentage;

        $vat = self::mul($netAmount, self::div($vatPercentage, '100'));

        return round((float) $vat, 2);
    }
    
    /**
     * Get net amount from gross (when VAT is inclusive)
     * Uses BCMath for precise decimal arithmetic
     * 
     * @param float $grossAmount Gross amount (includes VAT)
     * @param float|null $vatPercentage Optional VAT percentage
     * @return float Net amount
     */
    public static function getNetFromGross(float $grossAmount, ?float $vatPercentage = null): float
    {
        $vatPercentage = $vatPercentage ?? Preference::get('vat_percentage', 20);
        
        // Convert to strings for BCMath
        $grossAmount = (string)$grossAmount;
        $vatPercentage = (string)$vatPercentage;
        
        $divisor = self::add('1', self::div($vatPercentage, '100'));
        $net = self::div($grossAmount, $divisor);
        return round((float)$net, 2);
    }
    
    /**
     * Get gross amount from net (when VAT is exclusive)
     * Uses BCMath for precise decimal arithmetic
     * 
     * @param float $netAmount Net amount (VAT exclusive)
     * @param float|null $vatPercentage Optional VAT percentage
     * @return float Gross amount
     */
    public static function getGrossFromNet(float $netAmount, ?float $vatPercentage = null): float
    {
        $vatPercentage = $vatPercentage ?? Preference::get('vat_percentage', 20);
        
        // Convert to strings for BCMath
        $netAmount = (string)$netAmount;
        $vatPercentage = (string)$vatPercentage;
        
        $vat = self::mul($netAmount, self::div($vatPercentage, '100'));
        $gross = self::add($netAmount, $vat);
        return round((float)$gross, 2);
    }
}
