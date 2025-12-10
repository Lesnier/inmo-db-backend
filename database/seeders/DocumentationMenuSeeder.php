<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;

class DocumentationMenuSeeder extends Seeder
{
    public function run()
    {
        $menu = Menu::where('name', 'admin')->first();

        if ($menu) {
            $menuItem = MenuItem::firstOrNew([
                'menu_id' => $menu->id,
                'title'   => 'Documentation',
                'url'     => '',
                'route'   => 'voyager.documentation.index',
            ]);

            if (!$menuItem->exists) {
                $menuItem->fill([
                    'target'     => '_self',
                    'icon_class' => 'voyager-documentation', // Default icon
                    'color'      => '#76838f', // Distinct color to stand out
                    'parent_id'  => null,
                    'order'      => 99, // Last item
                ])->save();
            }
        }
    }
}
