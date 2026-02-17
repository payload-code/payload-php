<?php

use PHPUnit\Framework\TestCase;
use Payload\Attr;

final class AttrTest extends TestCase
{
    // --- Constructor and key building ---

    public function test_constructor_with_param_only()
    {
        $attr = new Attr('status');
        $this->assertEquals('status', strval($attr));
    }

    public function test_constructor_with_null_param()
    {
        $attr = new Attr();
        $this->assertEquals('', strval($attr));
    }

    public function test_nested_key_via_parent()
    {
        $parent = new Attr('customer');
        $child = new Attr('name', $parent);
        $this->assertEquals('customer[name]', strval($child));
    }

    public function test_deeply_nested_key()
    {
        $root = new Attr('customer');
        $mid = new Attr('address', $root);
        $leaf = new Attr('city', $mid);
        $this->assertEquals('customer[address][city]', strval($leaf));
    }

    // --- __get magic method ---

    public function test_get_creates_nested_attr()
    {
        $attr = new Attr('customer');
        $nested = $attr->name;
        $this->assertInstanceOf(Attr::class, $nested);
        $this->assertEquals('customer[name]', strval($nested));
    }

    public function test_get_chained_multiple_levels()
    {
        $attr = new Attr('customer');
        $deep = $attr->address->city;
        $this->assertEquals('customer[address][city]', strval($deep));
    }

    // --- eq ---

    public function test_eq_returns_filter_array()
    {
        $attr = new Attr('status');
        $result = $attr->eq('active');
        $this->assertEquals(['status' => 'active'], $result);
    }

    public function test_eq_with_nested_key()
    {
        $attr = new Attr('customer');
        $result = $attr->name->eq('John');
        $this->assertEquals(['customer[name]' => 'John'], $result);
    }

    public function test_eq_with_numeric_value()
    {
        $attr = new Attr('amount');
        $result = $attr->eq(100);
        $this->assertEquals(['amount' => 100], $result);
    }

    // --- ne ---

    public function test_ne_prepends_exclamation()
    {
        $attr = new Attr('status');
        $result = $attr->ne('inactive');
        $this->assertEquals(['status' => '!inactive'], $result);
    }

    // --- lt ---

    public function test_lt_prepends_less_than()
    {
        $attr = new Attr('amount');
        $result = $attr->lt(100);
        $this->assertEquals(['amount' => '<100'], $result);
    }

    // --- le ---

    public function test_le_prepends_less_than_or_equal()
    {
        $attr = new Attr('amount');
        $result = $attr->le(100);
        $this->assertEquals(['amount' => '<=100'], $result);
    }

    // --- gt ---

    public function test_gt_prepends_greater_than()
    {
        $attr = new Attr('amount');
        $result = $attr->gt(50);
        $this->assertEquals(['amount' => '>50'], $result);
    }

    // --- ge ---

    public function test_ge_prepends_greater_than_or_equal()
    {
        $attr = new Attr('amount');
        $result = $attr->ge(50);
        $this->assertEquals(['amount' => '>=50'], $result);
    }

    // --- contains ---

    public function test_contains_wraps_with_wildcards()
    {
        $attr = new Attr('name');
        $result = $attr->contains('john');
        $this->assertEquals(['name' => '?*john*'], $result);
    }

    // --- in ---

    public function test_in_joins_values_with_pipe()
    {
        $attr = new Attr('status');
        $result = $attr->in('active', 'pending');
        $this->assertEquals(['status' => 'active|pending'], $result);
    }

    public function test_in_single_value()
    {
        $attr = new Attr('status');
        $result = $attr->in('active');
        $this->assertEquals(['status' => 'active'], $result);
    }

    public function test_in_multiple_values()
    {
        $attr = new Attr('status');
        $result = $attr->in('active', 'pending', 'closed');
        $this->assertEquals(['status' => 'active|pending|closed'], $result);
    }

    // --- Comparison methods with nested keys ---

    public function test_ne_with_nested_key()
    {
        $attr = new Attr('customer');
        $result = $attr->status->ne('inactive');
        $this->assertEquals(['customer[status]' => '!inactive'], $result);
    }

    public function test_gt_with_nested_key()
    {
        $attr = new Attr('order');
        $result = $attr->total->gt(200);
        $this->assertEquals(['order[total]' => '>200'], $result);
    }

    public function test_contains_with_nested_key()
    {
        $attr = new Attr('customer');
        $result = $attr->email->contains('example');
        $this->assertEquals(['customer[email]' => '?*example*'], $result);
    }

    public function test_in_with_nested_key()
    {
        $attr = new Attr('customer');
        $result = $attr->type->in('business', 'individual');
        $this->assertEquals(['customer[type]' => 'business|individual'], $result);
    }
}
