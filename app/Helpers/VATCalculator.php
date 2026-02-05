<?php

namespace App\Helpers;

use App\Models\Preference;

class VATCalculator
{
    /**
     * Set scale for BCMath operations (2 decimal places for currency)
     */
    private static int $bcScale = 10; // Use 10 for intermediate calculations, round to 2 at the end

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
            bcscale(self::$bcScale);
            $divisor = bcadd('1', bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
            $net = bcdiv($gross, $divisor, self::$bcScale);
            $vat = bcsub($gross, $net, self::$bcScale);
        } else {
            // Price is net (VAT exclusive), add VAT
            // Formula: VAT = net * vat%, gross = net + VAT
            // Example: 100€ net with 20% VAT → VAT = 20€, gross = 120€
            bcscale(self::$bcScale);
            $net = $price;
            $vat = bcmul($net, bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
            $gross = bcadd($net, $vat, self::$bcScale);
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
    public static function calculateVATAmount(float $netAmount, ?float $vatPercentage = null): float
    {
        $vatPercentage = $vatPercentage ?? Preference::get('vat_percentage', 20);
        $vatMode = config('app.vat_calculation_mode', 'exclusive');
        
        // Convert to strings for BCMath
        $netAmount = (string)$netAmount;
        $vatPercentage = (string)$vatPercentage;
        
        bcscale(self::$bcScale);
        
        if ($vatMode === 'inclusive') {
            // If prices are inclusive, VAT is already included in the price
            // We need net to calculate VAT, but if we only have gross, extract VAT
            // This method assumes netAmount is actually gross when mode is inclusive
            $gross = $netAmount;
            $divisor = bcadd('1', bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
            $net = bcdiv($gross, $divisor, self::$bcScale);
            $vat = bcsub($gross, $net, self::$bcScale);
            return round((float)$vat, 2);
        } else {
            // Exclusive mode: VAT = net * vat%
            $vat = bcmul($netAmount, bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
            return round((float)$vat, 2);
        }
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
        
        bcscale(self::$bcScale);
        $divisor = bcadd('1', bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
        $net = bcdiv($grossAmount, $divisor, self::$bcScale);
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
        
        bcscale(self::$bcScale);
        $vat = bcmul($netAmount, bcdiv($vatPercentage, '100', self::$bcScale), self::$bcScale);
        $gross = bcadd($netAmount, $vat, self::$bcScale);
        return round((float)$gross, 2);
    }
}
