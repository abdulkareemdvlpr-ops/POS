<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            ['name' => 'Admin', 'role' => 'admin', 'password' => Hash::make('password')]
        );

        // Demo cashier
        User::firstOrCreate(
            ['email' => 'cashier@pos.com'],
            ['name' => 'Cashier Demo', 'role' => 'cashier', 'password' => Hash::make('password')]
        );

        // Categories
        $cats = ['Electronics', 'Beverages', 'Groceries', 'Clothing', 'Stationery', 'Medicine'];
        foreach ($cats as $c) {
            Category::firstOrCreate(['name' => $c], ['description' => "$c category", 'status' => true]);
        }

        // Suppliers
        $suppliers = [
            ['name' => 'Ahmed Khan',   'company_name' => 'AK Traders',     'phone' => '0300-1111111', 'city' => 'Karachi'],
            ['name' => 'Bilal Sheikh', 'company_name' => 'BS Distributors', 'phone' => '0301-2222222', 'city' => 'Lahore'],
        ];
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['phone' => $s['phone']], array_merge($s, ['status' => true]));
        }

        // Customers
        $customers = [
            ['name' => 'Rizwan Ali',   'phone' => '0311-1234567', 'city' => 'Karachi'],
            ['name' => 'Sara Malik',   'phone' => '0322-7654321', 'city' => 'Lahore'],
            ['name' => 'Hassan Raza',  'phone' => '0333-9876543', 'city' => 'Islamabad'],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(['phone' => $c['phone']], array_merge($c, ['status' => true]));
        }

        // Products
        $cat1 = Category::where('name', 'Electronics')->first();
        $cat2 = Category::where('name', 'Beverages')->first();
        $sup1 = Supplier::first();

        $products = [
            ['name' => 'USB Cable',         'category_id' => $cat1->id, 'buy_price' => 80,  'price' => 150,  'stock' => 100, 'unit' => 'pcs'],
            ['name' => 'Mobile Charger',    'category_id' => $cat1->id, 'buy_price' => 250, 'price' => 450,  'stock' => 50,  'unit' => 'pcs'],
            ['name' => 'Mineral Water 1L',  'category_id' => $cat2->id, 'buy_price' => 25,  'price' => 40,   'stock' => 5,   'unit' => 'pcs'],
            ['name' => 'Cola 500ml',        'category_id' => $cat2->id, 'buy_price' => 45,  'price' => 70,   'stock' => 30,  'unit' => 'pcs'],
        ];
        foreach ($products as $p) {
            Product::firstOrCreate(['name' => $p['name']], array_merge($p, [
                'supplier_id'         => $sup1->id,
                'low_stock_threshold' => 10,
                'status'              => true,
            ]));
        }
    }
}
