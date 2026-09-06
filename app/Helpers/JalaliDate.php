<?php

namespace App\Helpers;

/**
 * Jalali (Solar Hijri) <-> Gregorian conversion.
 * Direct port of jalaali-js (MIT) by Behrang Norouzinia, based on
 * algorithms by Kazimierz M. Borkowski and Reza Roohanian.
 * Reference: http://www.astro.uni.torun.pl/~kb/Papers/EMP/PersianC-EMP.htm
 */
class JalaliDate
{
    private const FA_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    private const EN_DIGITS = ['0','1','2','3','4','5','6','7','8','9'];

    /** Jalaali years starting the 33-year rule. */
    private const BREAKS = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
        1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    ];

    public static function toLatinDigits(string $v): string
    {
        return str_replace(self::FA_DIGITS, self::EN_DIGITS, $v);
    }

    public static function toPersianDigits(string $v): string
    {
        return str_replace(self::EN_DIGITS, self::FA_DIGITS, $v);
    }

    /* JS-style integer helpers: truncating division and modulo */
    private static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    private static function mod(int $a, int $b): int
    {
        return $a - intdiv($a, $b) * $b;
    }

    /**
     * Number of years since the last leap year (0 to 4); 0 = leap year.
     */
    public static function jalCalLeap(int $jy): int
    {
        $bl = count(self::BREAKS);
        $jp = self::BREAKS[0];

        if ($jy < $jp || $jy >= self::BREAKS[$bl - 1]) {
            throw new \InvalidArgumentException('Invalid Jalaali year ' . $jy);
        }

        $jump = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = self::BREAKS[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $jp = $jm;
        }
        $n = $jy - $jp;

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }
        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);
        if ($leap === -1) {
            $leap = 4;
        }
        return $leap;
    }

    /**
     * Leap status + Gregorian year + March day of Farvardin the 1st.
     */
    private static function jalCal(int $jy, bool $withoutLeap = false): array
    {
        $bl = count(self::BREAKS);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = self::BREAKS[0];

        if ($jy < $jp || $jy >= self::BREAKS[$bl - 1]) {
            throw new \InvalidArgumentException('Invalid Jalaali year ' . $jy);
        }

        $jump = 0;
        for ($i = 1; $i < $bl; $i++) {
            $jm = self::BREAKS[$i];
            $jump = $jm - $jp;
            if ($jy < $jm) {
                break;
            }
            $leapJ = $leapJ + self::div($jump, 33) * 8 + self::div(self::mod($jump, 33), 4);
            $jp = $jm;
        }
        $n = $jy - $jp;

        $leapJ = $leapJ + self::div($n, 33) * 8 + self::div(self::mod($n, 33) + 3, 4);
        if (self::mod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ += 1;
        }

        // Same count in the Gregorian calendar (until the year gy).
        $leapG = self::div($gy, 4) - self::div((self::div($gy, 100) + 1) * 3, 4) - 150;

        // Gregorian date of Farvardin the 1st.
        $march = 20 + $leapJ - $leapG;

        if ($withoutLeap) {
            return ['gy' => $gy, 'march' => $march];
        }

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }
        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);
        if ($leap === -1) {
            $leap = 4;
        }

        return ['leap' => $leap, 'gy' => $gy, 'march' => $march];
    }

    /** Gregorian -> Julian Day number. */
    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = self::div(($gy + self::div($gm - 8, 6) + 100100) * 1461, 4)
            + self::div(153 * self::mod($gm + 9, 12) + 2, 5)
            + $gd - 34840408;
        return $d - self::div(self::div($gy + 100100 + self::div($gm - 8, 6), 100) * 3, 4) + 752;
    }

    /** Julian Day number -> Gregorian [gy, gm, gd]. */
    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::div(self::div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::div(self::mod($j, 1461), 4) * 5 + 308;
        $gd = self::div(self::mod($i, 153), 5) + 1;
        $gm = self::mod(self::div($i, 153), 12) + 1;
        $gy = self::div($j, 1461) - 100100 + self::div(8 - $gm, 6);
        return [$gy, $gm, $gd];
    }

    /** Jalali -> Julian Day number. */
    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy, true);
        return self::g2d($r['gy'], 3, $r['march'])
            + ($jm - 1) * 31 - self::div($jm, 7) * ($jm - 7) + $jd - 1;
    }

    /** Julian Day number -> Jalali [jy, jm, jd]. */
    private static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)[0];
        $jy = $gy - 621;
        $r = self::jalCal($jy, false);
        $jdn1f = self::g2d($gy, 3, $r['march']);

        // Days passed since 1 Farvardin.
        $k = $jdn - $jdn1f;
        if ($k >= 0) {
            if ($k <= 185) {
                // First 6 months.
                $jm = 1 + self::div($k, 31);
                $jd = self::mod($k, 31) + 1;
                return [$jy, $jm, $jd];
            }
            // Remaining months.
            $k -= 186;
        } else {
            // Previous Jalaali year.
            $jy -= 1;
            $k += 179;
            if ($r['leap'] === 1) {
                $k += 1;
            }
        }
        $jm = 7 + self::div($k, 30);
        $jd = self::mod($k, 30) + 1;
        return [$jy, $jm, $jd];
    }

    /* ============ public API ============ */

    public static function isLeap(int $jy): bool
    {
        return self::jalCalLeap($jy) === 0;
    }

    public static function jalaaliMonthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }
        return self::isLeap($jy) ? 30 : 29;
    }

    public static function g2j(int $gy, int $gm, int $gd): array
    {
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    public static function j2g(int $jy, int $jm, int $jd): string
    {
        [$gy, $gm, $gd] = self::d2g(self::j2d($jy, $jm, $jd));
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /**
     * MySQL date (Y-m-d) -> Jalali display (۱۴۰۳/۰۹/۱۳).
     */
    public static function format(?string $mysqlDate): string
    {
        $date = is_string($mysqlDate) ? trim($mysqlDate) : '';
        if ($date === '' || $date === '0000-00-00') {
            return '—';
        }
        $d = explode('-', explode(' ', $date)[0]);
        if (count($d) !== 3) {
            return '—';
        }
        [$jy, $jm, $jd] = self::d2j(self::g2d((int)$d[0], (int)$d[1], (int)$d[2]));
        return self::toPersianDigits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    }

    /**
     * Jalali input (1403/09/13 | 1403-09-13 | ۱۴۰۳/۰۹/۱۳ | 1403.09.13)
     * -> MySQL date (Y-m-d), or null when invalid.
     */
    public static function parse(?string $input): ?string
    {
        if (!is_string($input) || trim($input) === '') {
            return null;
        }
        $value = str_replace(['.', '\\', ' ', '-'], '/', self::toLatinDigits(trim($input)));
        $parts = explode('/', $value);
        if (count($parts) !== 3) {
            return null;
        }

        $jy = (int)$parts[0];
        $jm = (int)$parts[1];
        $jd = (int)$parts[2];

        if ($jy < 1200 || $jy > 1500 || $jm < 1 || $jm > 12 || $jd < 1) {
            return null;
        }
        if ($jd > self::jalaaliMonthLength($jy, $jm)) {
            return null;
        }

        [$gy, $gm, $gd] = self::d2g(self::j2d($jy, $jm, $jd));
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
}
