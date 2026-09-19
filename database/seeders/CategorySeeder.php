<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $courseCategories = [
            [
                'name' => 'General English',
                'description' => 'Comprehensive English courses for all proficiency levels',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Beginners (A1-A2)', 'sort_order' => 1],
                    ['name' => 'Intermediate (B1-B2)', 'sort_order' => 2],
                    ['name' => 'Advanced (C1-C2)', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'IELTS Preparation',
                'description' => 'Academic and General Training IELTS exam preparation courses',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'IELTS Foundation', 'sort_order' => 1],
                    ['name' => 'IELTS Intensive (6.5+)', 'sort_order' => 2],
                    ['name' => 'IELTS Speaking & Writing Mastery', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Business English',
                'description' => 'Professional workplace communication and business English skills',
                'sort_order' => 3,
                'children' => [
                    ['name' => 'English for Workplace', 'sort_order' => 1],
                    ['name' => 'Business Communication', 'sort_order' => 2],
                ],
            ],
        ];

        foreach ($courseCategories as $item) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'type' => Category::TYPE_COURSE,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($item['children'] as $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child['name'])],
                    [
                        'type' => Category::TYPE_COURSE,
                        'parent_id' => $parent->id,
                        'name' => $child['name'],
                        'sort_order' => $child['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $newsCategories = [
            [
                'name' => 'Tin tức thị trường',
                'description' => 'Thông tin thị trường mới nhất',
                'sort_order' => 1,
            ],
            [
                'name' => 'Chính sách & Quy hoạch',
                'description' => 'Cập nhật chính sách và ưu đãi',
                'sort_order' => 2,
            ],
            [
                'name' => 'Cẩm nang học tập',
                'description' => 'Kinh nghiệm và mẹo học tiếng Anh hiệu quả',
                'sort_order' => 3,
            ],
        ];

        foreach ($newsCategories as $item) {
            Category::firstOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'type' => Category::TYPE_NEWS,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
