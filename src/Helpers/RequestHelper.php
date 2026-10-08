<?php

namespace Amarenkov\MutableContentFilament\Helpers;

class RequestHelper
{
    public static function getPath()
    {
        $path = [];

        $components = request()->json('components', []);
        foreach ($components as $component)
            if (@$component['snapshot'])
            {
                $component['snapshot'] = json_decode($component['snapshot'], true);
                
                $path[] = @$component['snapshot']['memo']['path'];
                $path[] = @$component['snapshot']['data']['activeTab'];
            }

        $path = array_filter($path);
        $path = array_unique($path);
        $path = implode('/', $path);

        return $path;
    }
}