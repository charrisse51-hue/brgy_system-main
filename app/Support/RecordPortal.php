<?php
namespace App\Support;

/** Route context for the shared Admin and Staff record views and controllers. */
class RecordPortal
{
    public static function routeName(string $name): string
    {
        return (request()->routeIs('staff.*') ? 'staff.' : 'admin.').$name;
    }
}
