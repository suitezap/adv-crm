<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            if (! $role->permissions) {
                continue;
            }

            $permissions = json_decode($role->permissions, true) ?? [];
            $updated = false;

            foreach ($permissions as $index => $permission) {
                if (strpos($permission, 'financeiro') === 0) {
                    $permissions[$index] = 'lawfirm.'.$permission;
                    $updated = true;
                }
            }

            if ($updated) {
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($permissions))),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $roles = DB::table('roles')->get();

        foreach ($roles as $role) {
            if (! $role->permissions) {
                continue;
            }

            $permissions = json_decode($role->permissions, true) ?? [];
            $updated = false;

            foreach ($permissions as $index => $permission) {
                if (strpos($permission, 'lawfirm.financeiro') === 0) {
                    $permissions[$index] = preg_replace('/^lawfirm\./', '', $permission);
                    $updated = true;
                }
            }

            if ($updated) {
                DB::table('roles')->where('id', $role->id)->update([
                    'permissions' => json_encode(array_values(array_unique($permissions))),
                ]);
            }
        }
    }
};
