<?php

/**
 * This file is part of MoneyType, a PHP Experts, Inc., Project.
 *
 * Copyright © 2019 PHP Experts, Inc.
 * Author: Theodore R. Smith <theodore@phpexperts.pro>
 *  GPG Fingerprint: 4BF8 2613 1C34 87AC D28F  2AD8 EB24 A91D D612 5690
 *  https://www.phpexperts.pro/
 *  https://github.com/phpexpertsinc/MoneyType
 *
 * This file is licensed under the MIT License.
 */

namespace PHPExperts\MoneyType\Tests;

use PHPExperts\MoneyType\Money;
use PHPExperts\MoneyType\MoneyCalculationStrategy;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    private static function assertCalcStrategy(Money $moneyType, string $expected)
    {
        $actual = $moneyType->getStrategy();

        self::assertSame($expected, $actual);
    }

    public static function buildMockCalcStrategy(): MoneyCalculationStrategy
    {
        return new class implements MoneyCalculationStrategy
        {
            public function getWithFullPrecision(): string
            {
                return '0';
            }

            public function __toString()
            {
                return '1';
            }

            public function add($rightOperand)
            {
                return '2';
            }

            public function subtract($rightOperand)
            {
                return '3';
            }

            public function multiply($rightOperand)
            {
                return '4';
            }

            public function divide($rightOperand)
            {
                return '5';
            }

            public function modulus($modulus)
            {
                return '6';
            }

            public function compare($rightOperand)
            {
                return '7';
            }
        };
    }

    public function testWillReportWhatStrategyIsBeingUsed()
    {
        $moneyType = new Money(5);
        self::assertCalcStrategy($moneyType, 'BCMathCalcStrategy');
    }

    public function testWillUseBCMathIfItIsAvailable()
    {
        $hasBCMath = function() {
            return true;
        };

        $moneyType = new Money(5, null, $hasBCMath);
        self::assertCalcStrategy($moneyType, 'BCMathCalcStrategy');
    }

    public function testWillFallBackToNativePhpIfNecessary()
    {
        $hasBCMath = function() {
            return false;
        };

        $moneyType = new Money(5, null, $hasBCMath);
        self::assertCalcStrategy($moneyType, 'NativeCalcStrategy');
    }

    public function testProxiesEverythingToItsCalculationStrategy()
    {
        $mockCalcStrat = self::buildMockCalcStrategy();
        $moneyType = new Money(5, $mockCalcStrat);

        $calcStratOps = get_class_methods($mockCalcStrat);
        foreach ($calcStratOps as $index => $op) {
            self::assertSame((string) $index, $moneyType->$op(0));
        }
    }

    public function testConfirmThatTheReadMeDemoWorks()
    {
        $money = new Money(5.22);

        $money->add(0.55);
        self::assertSame('5.77', $money.'');

        # It keeps precision much much better than mere cents.
        $money->subtract(0.0001);
        self::assertSame('5.77', $money.'');

        $money->subtract(0.004);
        self::assertSame('5.77', $money.'');

        $money->subtract(0.001);
        self::assertSame('5.76', $money.'');

        $money->multiply(55.777355);
        self::assertSame('321.55', $money.'');
        self::assertSame('321.5508738395', $money->getWithFullPrecision());

        $money->divide('1.000005');
        self::assertSame('321.55', $money.'');
        self::assertSame('321.5492660931', $money->getWithFullPrecision());

        self::assertSame(0, $money->compare('321.5492660931'));  //  0 = equal
        self::assertSame(-1, $money->compare('321.5492660930')); // -1 = less
        self::assertSame(1, $money->compare('321.5492660932'));  //  1 = more
    }
}
