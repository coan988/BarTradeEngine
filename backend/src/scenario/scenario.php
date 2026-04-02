<?php

function stockmarketcrash(): array
{
    return [
        'name' => 'stockmarketcrash',
        'type' => 'all_prices_factor',
        'factor' => 0.5,
    ];
}

function drinksubvention(): array
{
    return [
        'name' => 'drinksubvention',
        'type' => 'random_drink_factor',
        'factor' => 0.5,
    ];
}

function alltimehigh(): array
{
    return [
        'name' => 'alltimehigh',
        'type' => 'all_prices_factor',
        'factor' => 1.5,
    ];
}