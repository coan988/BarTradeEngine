<?php

function stockmarketcrash(): array
{
    return [
        'name' => 'stockmarketcrash',
        'type' => 'all_drinks',
        'factor' => 0.5,
    ];
}

function drinksubvention(): array
{
    return [
        'name' => 'drinksubvention',
        'type' => 'random_drink',
        'factor' => 0.5,
    ];
}

function alltimehigh(): array
{
    return [
        'name' => 'alltimehigh',
        'type' => 'all_prices',
        'factor' => 1.5,
    ];
}

function mackebachwassergreateagain(): array
{
    return [
        'name' => 'mackebachwassergreateagain',
        'type' => 'fix_drink',
        'factor' => 0.5,
        'DrinkId' => 1,
    ];
}