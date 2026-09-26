<?php

namespace App\Menu;

use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

/**
 * AdminLTE translates menu "text" and "header" but not the footer label of a navbar-notification dropdown.
 */
class TranslateDropdownFooterFilter implements FilterInterface
{
    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function transform($item)
    {
        if (isset($item['dropdown_flabel']) && is_string($item['dropdown_flabel'])) {
            $item['dropdown_flabel'] = __($item['dropdown_flabel']);
        }

        return $item;
    }
}
