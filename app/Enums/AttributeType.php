<?php

namespace App\Enums;

/**
 * The families that group the individual attribute vocabularies together.
 *
 * Add a new family by creating its backed enum (implementing {@see Attribute})
 * and wiring it into the match arms below.
 */
enum AttributeType: string
{
    case Price = 'price';
    case Diet = 'diet';
    case Style = 'style';

    /**
     * Human-friendly label for display.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Every attribute case that belongs to this type.
     *
     * @return list<Attribute>
     */
    public function options(): array
    {
        return match ($this) {
            self::Price => Price::cases(),
            self::Diet => Diet::cases(),
            self::Style => Style::cases(),
        };
    }

    /**
     * The full catalogue — every type with its selectable options, shaped for
     * the front-end preference pickers (event ballots and personal
     * preferences alike).
     *
     * @return list<array{type: string, label: string, options: list<array{value: string, label: string}>}>
     */
    public static function catalogue(): array
    {
        return array_map(fn (self $type) => [
            'type' => $type->value,
            'label' => $type->label(),
            'options' => array_map(
                fn (Attribute $option) => ['value' => $option->value, 'label' => $option->label()],
                $type->options(),
            ),
        ], self::cases());
    }

    /**
     * The backing values of this type's options — handy for validation
     * (e.g. Rule::in(AttributeType::Price->values())).
     *
     * @return list<string>
     */
    public function values(): array
    {
        return match ($this) {
            self::Price => array_column(Price::cases(), 'value'),
            self::Diet => array_column(Diet::cases(), 'value'),
            self::Style => array_column(Style::cases(), 'value'),
        };
    }
}
