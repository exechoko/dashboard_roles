<?php

use App\Services\Optimizer;

if (!function_exists('optimize')) {
    /**
     * Helper para optimizar queries
     */
    function optimize($model)
    {
        return Optimizer::model($model);
    }
}
