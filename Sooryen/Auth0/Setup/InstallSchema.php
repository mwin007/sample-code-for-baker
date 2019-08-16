<?php

namespace Sooryen\Auth0\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()->newTable(
                        $installer->getTable('sooryen_auth0')
                )
                ->addColumn(
                        'id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true], 'auth0_auth0'
                )
                ->addColumn(
                        'auth0_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'auth0_id'
                )
                ->addColumn(
                        'customer_email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'customer_email'
                )
                ->addColumn(
                        'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, '', [], 'created_at'
                )
                ->addColumn(
                        'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME, '', [], 'updated_at'
                )
                ->addIndex( $installer->getIdxName( $installer->getTable('sooryen_auth0'),
                                                    ['customer_email'],
                                                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                                                    ),
            ['customer_email'],
            ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
        )
                ->setComment(
                'Sooryen Auth0 auth0_auth0'
        );

        $installer->getConnection()->createTable($table);
        /* {{CedAddTable}} */

        $installer->endSetup();
    }

}
