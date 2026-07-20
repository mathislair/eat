<?php

namespace App\Enums;

/**
 * Shared contract for the small, fixed vocabularies that describe an item
 * (price, diet, style, …). Each is a backed enum grouped under an
 * {@see AttributeType}, so future tables can accept "any attribute" uniformly
 * and store its backing value in a column.
 */
interface Attribute
{
    /**
     * The type this attribute belongs to.
     */
    public function type(): AttributeType;

    /**
     * Human-friendly label for display.
     */
    public function label(): string;
}
