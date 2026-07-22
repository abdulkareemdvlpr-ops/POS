<?php

namespace App\Support;

class SimpleQrCode
{
    private const VERSION = 4;
    private const SIZE = 33;
    private const DATA_CODEWORDS = 80;
    private const ECC_CODEWORDS = 20;

    public static function svgDataUri(string $text, int $scale = 4, int $border = 4): string
    {
        $svg = self::svg($text, $scale, $border);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function svg(string $text, int $scale = 4, int $border = 4): string
    {
        $payload = substr($text, 0, 78);
        $codewords = self::createCodewords($payload);
        $modules = self::createMatrix($codewords);
        $pixels = (self::SIZE + $border * 2) * $scale;
        $parts = [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $pixels . ' ' . $pixels . '" width="' . $pixels . '" height="' . $pixels . '" shape-rendering="crispEdges">',
            '<rect width="100%" height="100%" fill="#fff"/>',
        ];

        for ($y = 0; $y < self::SIZE; $y++) {
            for ($x = 0; $x < self::SIZE; $x++) {
                if (!$modules[$y][$x]) {
                    continue;
                }

                $parts[] = sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" fill="#000"/>',
                    ($x + $border) * $scale,
                    ($y + $border) * $scale,
                    $scale,
                    $scale
                );
            }
        }

        $parts[] = '</svg>';

        return implode('', $parts);
    }

    private static function createCodewords(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $bits = [];

        self::appendBits($bits, 0x4, 4);
        self::appendBits($bits, count($bytes), 8);

        foreach ($bytes as $byte) {
            self::appendBits($bits, $byte, 8);
        }

        $capacityBits = self::DATA_CODEWORDS * 8;
        self::appendBits($bits, 0, min(4, $capacityBits - count($bits)));

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $data = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $value = 0;
            foreach ($chunk as $bit) {
                $value = ($value << 1) | $bit;
            }
            $data[] = $value;
        }

        for ($pad = 0; count($data) < self::DATA_CODEWORDS; $pad++) {
            $data[] = $pad % 2 === 0 ? 0xEC : 0x11;
        }

        return array_merge($data, self::reedSolomonRemainder($data));
    }

    private static function createMatrix(array $codewords): array
    {
        $modules = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));
        $function = array_fill(0, self::SIZE, array_fill(0, self::SIZE, false));

        self::drawFunctionPatterns($modules, $function);
        self::drawFormatBits($modules, $function, 0);
        self::drawCodewords($modules, $function, $codewords);
        self::applyMask($modules, $function);
        self::drawFormatBits($modules, $function, 0);

        return $modules;
    }

    private static function drawFunctionPatterns(array &$modules, array &$function): void
    {
        self::drawFinder($modules, $function, 0, 0);
        self::drawFinder($modules, $function, self::SIZE - 7, 0);
        self::drawFinder($modules, $function, 0, self::SIZE - 7);
        self::drawAlignment($modules, $function, 26, 26);

        for ($i = 8; $i < self::SIZE - 8; $i++) {
            self::setFunction($modules, $function, $i, 6, $i % 2 === 0);
            self::setFunction($modules, $function, 6, $i, $i % 2 === 0);
        }

        self::setFunction($modules, $function, 8, 4 * self::VERSION + 9, true);
    }

    private static function drawFinder(array &$modules, array &$function, int $x, int $y): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $xx = $x + $dx;
                $yy = $y + $dy;

                if ($xx < 0 || $xx >= self::SIZE || $yy < 0 || $yy >= self::SIZE) {
                    continue;
                }

                $dark = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6
                    && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));

                self::setFunction($modules, $function, $xx, $yy, $dark);
            }
        }
    }

    private static function drawAlignment(array &$modules, array &$function, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $distance = max(abs($dx), abs($dy));
                self::setFunction($modules, $function, $cx + $dx, $cy + $dy, $distance === 0 || $distance === 2);
            }
        }
    }

    private static function drawFormatBits(array &$modules, array &$function, int $mask): void
    {
        $bits = self::formatBits($mask);

        for ($i = 0; $i <= 5; $i++) {
            self::setFunction($modules, $function, 8, $i, self::getBit($bits, $i));
        }

        self::setFunction($modules, $function, 8, 7, self::getBit($bits, 6));
        self::setFunction($modules, $function, 8, 8, self::getBit($bits, 7));
        self::setFunction($modules, $function, 7, 8, self::getBit($bits, 8));

        for ($i = 9; $i < 15; $i++) {
            self::setFunction($modules, $function, 14 - $i, 8, self::getBit($bits, $i));
        }

        for ($i = 0; $i < 8; $i++) {
            self::setFunction($modules, $function, self::SIZE - 1 - $i, 8, self::getBit($bits, $i));
        }

        for ($i = 8; $i < 15; $i++) {
            self::setFunction($modules, $function, 8, self::SIZE - 15 + $i, self::getBit($bits, $i));
        }

        self::setFunction($modules, $function, 8, self::SIZE - 8, true);
    }

    private static function drawCodewords(array &$modules, array $function, array $codewords): void
    {
        $bits = [];
        foreach ($codewords as $codeword) {
            self::appendBits($bits, $codeword, 8);
        }

        $index = 0;
        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            for ($vert = 0; $vert < self::SIZE; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? self::SIZE - 1 - $vert : $vert;

                    if ($function[$y][$x]) {
                        continue;
                    }

                    $modules[$y][$x] = ($bits[$index] ?? 0) === 1;
                    $index++;
                }
            }
        }
    }

    private static function applyMask(array &$modules, array $function): void
    {
        for ($y = 0; $y < self::SIZE; $y++) {
            for ($x = 0; $x < self::SIZE; $x++) {
                if (!$function[$y][$x] && (($x + $y) % 2 === 0)) {
                    $modules[$y][$x] = !$modules[$y][$x];
                }
            }
        }
    }

    private static function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    private static function reedSolomonRemainder(array $data): array
    {
        $divisor = self::reedSolomonDivisor(self::ECC_CODEWORDS);
        $result = array_fill(0, self::ECC_CODEWORDS, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;

            foreach ($result as $i => $value) {
                $result[$i] = $value ^ self::gfMultiply($divisor[$i], $factor);
            }
        }

        return $result;
    }

    private static function reedSolomonDivisor(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;

        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMultiply($result[$j], $root);

                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }

            $root = self::gfMultiply($root, 0x02);
        }

        return $result;
    }

    private static function gfMultiply(int $x, int $y): int
    {
        $result = 0;

        while ($y > 0) {
            if (($y & 1) !== 0) {
                $result ^= $x;
            }

            $x <<= 1;
            if (($x & 0x100) !== 0) {
                $x ^= 0x11D;
            }

            $y >>= 1;
        }

        return $result & 0xFF;
    }

    private static function formatBits(int $mask): int
    {
        $data = (1 << 3) | $mask;
        $remainder = $data;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder << 1) ^ ((($remainder >> 9) & 1) * 0x537);
        }

        return (($data << 10) | $remainder) ^ 0x5412;
    }

    private static function setFunction(array &$modules, array &$function, int $x, int $y, bool $dark): void
    {
        $modules[$y][$x] = $dark;
        $function[$y][$x] = true;
    }

    private static function getBit(int $value, int $index): bool
    {
        return (($value >> $index) & 1) !== 0;
    }
}
