<?php
namespace App\Helpers;

class Formatter
{
    public static function price($amount)
    {
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }
    
    public static function date($date, $format = 'd/m/Y')
    {
        if (empty($date)) return '';
        $dt = new \DateTime($date);
        return $dt->format($format);
    }
    
    public static function stockStatus($current, $min)
    {
        if ($current == 0) return ['class' => 'stock-out', 'text' => 'RUPTURE'];
        if ($current <= $min) return ['class' => 'stock-low', 'text' => 'STOCK BAS'];
        return ['class' => 'stock-normal', 'text' => 'NORMAL'];
    }
}