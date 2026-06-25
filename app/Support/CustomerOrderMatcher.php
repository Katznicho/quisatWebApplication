<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ParentGuardian;
use App\Models\User;

class CustomerOrderMatcher
{
    public static function customerOwnsOrder(User|ParentGuardian $user, Order $order): bool
    {
        $email = strtolower(trim((string) ($user->email ?? '')));
        $orderEmail = strtolower(trim((string) ($order->customer_email ?? '')));

        if ($email !== '' && $orderEmail !== '' && $email === $orderEmail) {
            return true;
        }

        $phoneSuffix = self::phoneMatchSuffix($user->phone ?? null);
        if ($phoneSuffix === null || ! $order->customer_phone) {
            return false;
        }

        $orderDigits = preg_replace('/\D+/', '', (string) $order->customer_phone);

        return $orderDigits !== '' && str_ends_with($orderDigits, $phoneSuffix);
    }

    public static function orderEligibleForReview(Order $order): bool
    {
        if ($order->status === 'cancelled') {
            return false;
        }

        if ($order->customer_received_at !== null || $order->status === 'delivered') {
            return true;
        }

        return $order->payment_status === 'paid'
            && in_array($order->status, ['confirmed', 'processing', 'shipped'], true);
    }

    public static function phoneMatchSuffix(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) >= 9 ? substr($digits, -9) : $digits;
    }
}
