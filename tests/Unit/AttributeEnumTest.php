<?php

namespace Tests\Unit;

use App\Enums\Attribute;
use App\Enums\AttributeType;
use App\Enums\Diet;
use App\Enums\Price;
use App\Enums\Style;
use PHPUnit\Framework\TestCase;

class AttributeEnumTest extends TestCase
{
    public function test_price_uses_symbols_and_is_ordinal(): void
    {
        $this->assertSame('€', Price::Cheap->value);
        $this->assertSame('€€', Price::Moderate->value);
        $this->assertSame('€€€', Price::Expensive->value);

        $this->assertSame([1, 2, 3], [
            Price::Cheap->level(),
            Price::Moderate->level(),
            Price::Expensive->level(),
        ]);
    }

    public function test_each_attribute_reports_its_own_type(): void
    {
        $this->assertSame(AttributeType::Price, Price::Cheap->type());
        $this->assertSame(AttributeType::Diet, Diet::Vegan->type());
        $this->assertSame(AttributeType::Style, Style::Spicy->type());
    }

    public function test_a_type_lists_exactly_the_options_that_belong_to_it(): void
    {
        foreach (AttributeType::cases() as $type) {
            $this->assertNotEmpty($type->options());

            foreach ($type->options() as $option) {
                $this->assertInstanceOf(Attribute::class, $option);
                $this->assertSame(
                    $type,
                    $option->type(),
                    "{$option->value} should belong to {$type->value}",
                );
            }
        }
    }

    public function test_values_returns_the_backing_strings_for_a_type(): void
    {
        $this->assertSame(['€', '€€', '€€€'], AttributeType::Price->values());
        $this->assertContains('vegan', AttributeType::Diet->values());
        $this->assertContains('spicy', AttributeType::Style->values());
    }

    public function test_every_attribute_has_a_non_empty_label(): void
    {
        foreach (AttributeType::cases() as $type) {
            $this->assertNotSame('', $type->label());

            foreach ($type->options() as $option) {
                $this->assertNotSame('', $option->label());
            }
        }
    }
}
