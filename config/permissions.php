<?php

/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:38 PM
 */

return [

        'models'    => [
            /**
             * When using the "HasRolesTrait" trait from this package, we need to know which
             * Eloquent model should be used to retrieve your roles. Of course, it
             * is often just the "Role" model but you may use whatever you like.
             *
             * The model you want to use as a Role model needs to implement the
             * `\Alif\Permissions\Models\Role` contract.
             */
            'role'       => \Alif\Permissions\Models\Role::class,

            /**
             * When using the "HasRolesTrait" trait from this package, we need to know which
             * Eloquent model should be used to retrieve your permissions. Of course, it
             * is often just the "Permission" model but you may use whatever you like.
             *
             * The model you want to use as a Permission model needs to implement the
             * `\Alif\Permissions\Models\Permission` contract.
             */
            'permission' => \Alif\Permissions\Models\Permission::class,
        ],


        /**
         * The cache key for the user roles.
         * You can use the `cacheable` method in your User model to set the cache key.
         *
         * ATTENTION! When you use ['redis', 'memcached'] cache drivers.
         */
        'cacheable' => true,

];