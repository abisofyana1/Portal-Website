<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PortalSetting;

class PortalSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing settings
        PortalSetting::truncate();

        $settings = [
            // Background
            ['section' => 'background', 'category' => 'Background', 'title' => 'Portal Background', 'image_name' => 'Blue Polygon.png', 'order_no' => 0],

            // Row 1
            ['section' => 'main_layout', 'category' => 'Menu 1', 'title' => 'Tentang BNI', 'url' => 'http://10.50.144.23/portalBank/DetailNews?id=44', 'order_no' => 1],
            ['section' => 'main_layout', 'category' => 'Menu 2', 'title' => 'App List', 'url' => '#', 'image_name' => 'bars', 'order_no' => 2],
            ['section' => 'main_layout', 'category' => 'Menu 3', 'title' => 'Portal Menu', 'url' => '#', 'image_name' => 'layer-group', 'order_no' => 3],
            ['section' => 'main_layout', 'category' => 'Menu 4', 'title' => 'Menu Menu', 'url' => '#', 'image_name' => 'list', 'order_no' => 4],
            ['section' => 'main_layout', 'category' => 'Menu 5', 'title' => 'News', 'url' => '#', 'image_name' => 'newspaper', 'order_no' => 5],

            // Row 2 & 3
            ['section' => 'main_layout', 'category' => 'Menu 6', 'title' => 'Sharing Moment', 'url' => 'http://10.50.144.23/portalBank/DetailNews?id=3943', 'order_no' => 6],
            ['section' => 'main_layout', 'category' => 'Menu 7', 'title' => 'News Banner', 'url' => '#', 'order_no' => 7],
            ['section' => 'main_layout', 'category' => 'Menu 8', 'title' => 'Suku Bunga', 'image_name' => 'persen.jpg', 'url' => 'http://10.50.144.23/portalBank/DetailNews?id=25', 'order_no' => 8],
            ['section' => 'main_layout', 'category' => 'Menu 9', 'title' => 'Giro Perusahaan', 'image_name' => 'screen-0.jpg', 'url' => 'http://10.50.10.154:8080/giro/proview', 'order_no' => 9],

            // Row 4
            ['section' => 'main_layout', 'category' => 'Menu 10', 'title' => 'Promo Tapcash', 'image_name' => 'tapcash_29jan20.png', 'url' => 'http://10.50.144.23/portalBank/DetailNews?id=45', 'order_no' => 10],
            ['section' => 'main_layout', 'category' => 'Menu 11', 'title' => 'Jadwal BCO', 'url' => 'http://10.50.144.23/portalBank/DetailNews?id=4956', 'order_no' => 11],
            ['section' => 'main_layout', 'category' => 'Menu 12', 'title' => 'Troubleshoot', 'url' => 'https://linktr.ee/BNIServiceAssurance', 'order_no' => 12],
            ['section' => 'main_layout', 'category' => 'Menu 13', 'title' => 'Standar Script', 'url' => '/portalBank/DetailNews?id=4961', 'order_no' => 13],
            ['section' => 'main_layout', 'category' => 'Menu 14', 'title' => 'Ticketing IT MIS', 'url' => '#', 'order_no' => 14],
            ['section' => 'main_layout', 'category' => 'Menu 15', 'title' => 'CX Comm', 'image_name' => 'Capture.JPG', 'url' => 'http://crm.bni.co.id/', 'order_no' => 15],
        ];

        foreach ($settings as $setting) {
            PortalSetting::create($setting);
        }
    }
}
