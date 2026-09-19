<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Trang chủ',
                'slug' => '',
                'target' => '_self',
                'sort_order' => 1,
                'children' => [],
            ],
            [
                'name' => 'Sản phẩm',
                'slug' => 'san-pham',
                'target' => '_self',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Căn hộ chung cư', 'slug' => 'can-ho-chung-cu', 'sort_order' => 1],
                    ['name' => 'Nhà phố & Biệt thự', 'slug' => 'nha-pho-biet-thu', 'sort_order' => 2],
                    ['name' => 'Đất nền dự án', 'slug' => 'dat-nen-du-an', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Dự án nổi bật',
                'slug' => 'du-an-noi-bat',
                'target' => '_self',
                'sort_order' => 3,
                'children' => [],
            ],
            [
                'name' => 'Tin tức & Cẩm nang',
                'slug' => 'tin-tuc-cam-nang',
                'target' => '_self',
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Tin tức thị trường', 'slug' => 'tin-tuc-thi-truong', 'sort_order' => 1],
                    ['name' => 'Chính sách & Quy hoạch', 'slug' => 'chinh-sach-quy-hoach', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Liên hệ',
                'slug' => 'lien-he',
                'target' => '_self',
                'sort_order' => 5,
                'children' => [],
            ],
        ];

        foreach ($menus as $item) {
            $parent = Menu::firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'target' => $item['target'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($item['children'] as $child) {
                Menu::firstOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'parent_id' => $parent->id,
                        'name' => $child['name'],
                        'target' => '_self',
                        'sort_order' => $child['sort_order'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
