<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\LedgerEntry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Store Owner',
            'email' => 'owner@simplepos.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cashier = User::create([
            'name' => 'Jane Cashier',
            'email' => 'cashier@simplepos.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
            'permissions' => ['view_inventory', 'view_ledger', 'process_sales'],
        ]);

        $inactiveCashier = User::create([
            'name' => 'John Smith',
            'email' => 'john@simplepos.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => false,
            'permissions' => ['view_inventory'],
        ]);

        $categories = [
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['name' => 'Snacks', 'description' => 'Chips, candy, and quick bites'],
            ['name' => 'Groceries', 'description' => 'Everyday household items'],
            ['name' => 'Stationery', 'description' => 'Office and school supplies'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $products = [
            ['category_id' => 1, 'name' => 'Bottled Water 500ml', 'sku' => 'BEV-001', 'price' => 1.50, 'cost' => 0.50, 'stock_quantity' => 48],
            ['category_id' => 1, 'name' => 'Cola Can 330ml', 'sku' => 'BEV-002', 'price' => 1.75, 'cost' => 0.75, 'stock_quantity' => 36],
            ['category_id' => 1, 'name' => 'Orange Juice 1L', 'sku' => 'BEV-003', 'price' => 3.50, 'cost' => 2.00, 'stock_quantity' => 8],
            ['category_id' => 2, 'name' => 'Potato Chips', 'sku' => 'SNK-001', 'price' => 2.00, 'cost' => 1.00, 'stock_quantity' => 24],
            ['category_id' => 2, 'name' => 'Chocolate Bar', 'sku' => 'SNK-002', 'price' => 1.25, 'cost' => 0.60, 'stock_quantity' => 3],
            ['category_id' => 2, 'name' => 'Granola Bar', 'sku' => 'SNK-003', 'price' => 1.80, 'cost' => 0.90, 'stock_quantity' => 15],
            ['category_id' => 3, 'name' => 'Rice 1kg', 'sku' => 'GRO-001', 'price' => 4.50, 'cost' => 3.00, 'stock_quantity' => 20],
            ['category_id' => 3, 'name' => 'Cooking Oil 1L', 'sku' => 'GRO-002', 'price' => 5.75, 'cost' => 4.00, 'stock_quantity' => 2],
            ['category_id' => 4, 'name' => 'Ballpoint Pen', 'sku' => 'STA-001', 'price' => 0.75, 'cost' => 0.25, 'stock_quantity' => 50],
            ['category_id' => 4, 'name' => 'Notebook A5', 'sku' => 'STA-002', 'price' => 2.50, 'cost' => 1.20, 'stock_quantity' => 18],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $today = Carbon::today();

        LedgerEntry::create([
            'entry_date' => $today,
            'type' => 'income',
            'description' => 'Cash sales',
            'amount' => 125.50,
            'user_id' => $cashier->id,
        ]);

        LedgerEntry::create([
            'entry_date' => $today,
            'type' => 'expense',
            'description' => 'Restock - beverages',
            'amount' => 45.00,
            'user_id' => $owner->id,
        ]);

        LedgerEntry::create([
            'entry_date' => $today,
            'type' => 'income',
            'description' => 'Card sales',
            'amount' => 82.25,
            'user_id' => $cashier->id,
        ]);
    }
}
