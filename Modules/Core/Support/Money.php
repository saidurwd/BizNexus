<?php

namespace Modules\Core\Support;

use InvalidArgumentException;
use Stringable;

/**
 * An amount in one currency, held as a decimal string and always rounded half-up to the currency's minor
 * units. All arithmetic is exact (bcmath); floats are never used for money.
 */
final class Money implements Stringable
{
    /**
     * Precision of intermediate results (e.g. amount × exchange rate) before rounding.
     */
    private const WORKING_SCALE = 12;

    private function __construct(
        public readonly string $amount,
        public readonly string $currency,
    ) {}

    public static function of(string|int|float $amount, string $currency): self
    {
        $currency = strtoupper($currency);

        return new self(bcround(self::normalise($amount), CurrencyPrecision::for($currency)), $currency);
    }

    public static function zero(string $currency): self
    {
        return self::of(0, $currency);
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of(bcadd($this->amount, $other->amount, self::WORKING_SCALE), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of(bcsub($this->amount, $other->amount, self::WORKING_SCALE), $this->currency);
    }

    public function multipliedBy(string|int|float $factor): self
    {
        return self::of(bcmul($this->amount, self::normalise($factor), self::WORKING_SCALE), $this->currency);
    }

    /**
     * Convert at a rate expressed as target units per one unit of this currency.
     */
    public function convertedTo(string $currency, string|int|float $rate): self
    {
        return self::of(bcmul($this->amount, self::normalise($rate), self::WORKING_SCALE), $currency);
    }

    public function negated(): self
    {
        return self::of(bcmul($this->amount, '-1', self::WORKING_SCALE), $this->currency);
    }

    public function abs(): self
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, self::WORKING_SCALE);
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->compareTo($other) === 0;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::WORKING_SCALE) === 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::WORKING_SCALE) === -1;
    }

    public function isPositive(): bool
    {
        return bccomp($this->amount, '0', self::WORKING_SCALE) === 1;
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot combine {$this->currency} with {$other->currency}.");
        }
    }

    private static function normalise(string|int|float $value): string
    {
        if (is_float($value)) {
            $value = rtrim(rtrim(sprintf('%.'.self::WORKING_SCALE.'F', $value), '0'), '.');
        }

        $value = trim((string) $value);

        if (! is_numeric($value) || str_contains(strtolower($value), 'e')) {
            throw new InvalidArgumentException("Invalid decimal amount [{$value}].");
        }

        return $value;
    }
}
