<?php

namespace Database\Seeders;

/**
 * Add only missing workbook drinks during container startup.
 *
 * Existing and soft-deleted records are intentionally preserved so restarting
 * Docker never overwrites menu changes made through the admin interface.
 */
class MissingDrinkSeeder extends DrinkSeeder
{
    protected bool $updateExisting = false;
}
