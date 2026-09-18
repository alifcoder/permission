<?php

/**
 * Created by Shukhratjon Yuldashev on 2025-05-16
 * Contact: https://t.me/alif_coder
 * Time: 4:38 PM
 */

return [

        'models'        => [
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
         * Cache the roles and permissions of every user.
         *
         * ATTENTION! The cache store must support tags (redis, memcached, array).
         * When the store is not taggable, the package silently works without cache.
         */
        'cacheable'     => true,

        /**
         * The cache store used by the package. Null means the default store.
         * Set it when your default store does not support tags.
         */
        'cache_store'   => null,

        /**
         * Lifetime (in seconds) of a cached entry. Null means forever.
         * The cache is invalidated automatically on every role/permission change.
         */
        'cache_ttl'     => null,

        /**
         * Mark the models as UUID.
         * This is used to set the UUID for the models.
         *
         * ATTENTION! If you use the bigint as a primary key, you need to set this to false.
         */
        'is_model_uuid' => true,

];