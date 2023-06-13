<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Stripe\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @inheritDoc
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $configsToDrop = [
            'payment/tnw_stripe/%',
            'payment/tnw_stripe_vault/%',
        ];

        $tablesToDrop = [];
        $columnsToDrop = [
            'sales_order' => [
                'guest_order_exported',
            ],
        ];
        $indexesToDrop = [];
        $constraintsToDrop = [];

        $this->dropConfigs($setup, $configsToDrop);
        $this->dropSchema($setup, $constraintsToDrop, $indexesToDrop, $columnsToDrop, $tablesToDrop);
    }

    private function dropConfigs(SchemaSetupInterface $setup, array $configs): void
    {
        array_map(
            function (string $config) use ($setup) {
                $setup->getConnection()->delete(
                    $setup->getTable('core_config_data'),
                    $setup->getConnection()->quoteInto('path like ?', $config)
                );
            },
            $configs
        );
    }

    private function dropSchema(
        SchemaSetupInterface $setup,
        array                $constraintsToDrop,
        array                $indexesToDrop,
        array                $columnsToDrop,
        array                $tablesToDrop
    ): void {
        $this->dropForeignKey($setup, $constraintsToDrop);
        $this->dropIndexes($setup, $indexesToDrop);
        $this->dropColumns($setup, $columnsToDrop);
        $this->dropTables($setup, $tablesToDrop);
    }

    private function dropForeignKey(SchemaSetupInterface $setup, array $constraintsData): void
    {
        $filteredData = array_filter(
            $constraintsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $constraints, string $table) use ($setup) {
                array_map(
                    function (string $constraint) use ($setup, $table) {
                        $setup->getConnection()->dropForeignKey($setup->getTable($table), $constraint);
                    },
                    $constraints
                );
            }
        );
    }

    private function dropIndexes(SchemaSetupInterface $setup, array $indexesData): void
    {
        $filteredData = array_filter(
            $indexesData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $indexes, string $table) use ($setup) {
                array_map(
                    function (string $index) use ($setup, $table) {
                        $setup->getConnection()->dropIndex($setup->getTable($table), $index);
                    },
                    $indexes
                );
            }
        );
    }

    private function dropColumns(SchemaSetupInterface $setup, array $columnsData): void
    {
        $filteredData = array_filter(
            $columnsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $columns, string $table) use ($setup) {
                array_map(
                    function (string $column) use ($setup, $table) {
                        $setup->getConnection()->dropColumn($setup->getTable($table), $column);
                    },
                    $columns
                );
            }
        );
    }

    private function dropTables(SchemaSetupInterface $setup, array $tables): void
    {
        array_map(
            function (string $table) use ($setup) {
                $setup->getConnection()->dropTable($setup->getTable($table));
            },
            $tables
        );
    }
}
