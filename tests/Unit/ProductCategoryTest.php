<?php

namespace Tests\Unit;

use App\Support\ProductCategory;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    public function test_aliases_map_to_canonical_category(): void
    {
        $this->assertSame('Clothing', ProductCategory::normalize('clothes'));
        $this->assertSame('Clothing', ProductCategory::normalize('Clothing'));
        $this->assertSame('Clothing', ProductCategory::normalize('  CLOTHES  '));
        $this->assertSame('School Supplies', ProductCategory::normalize('stationery'));
        $this->assertSame('Other', ProductCategory::normalize('random category'));
    }

    public function test_null_and_empty_values_return_null(): void
    {
        $this->assertNull(ProductCategory::normalize(null));
        $this->assertNull(ProductCategory::normalize(''));
        $this->assertNull(ProductCategory::normalize('   '));
    }
}
