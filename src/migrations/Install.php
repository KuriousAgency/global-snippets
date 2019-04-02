<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\migrations;

use kuriousagency\globalsnippets\GlobalSnippets;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%globalsnippets_snippets}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%globalsnippets_snippets}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->notNull()->defaultValue(Craft::$app->sites->currentSite->id),
                    'name' => $this->string(255)->notNull()->defaultValue(''),
                    'handle' => $this->string(255)->notNull()->defaultValue(''),
                    'snippetGroupId' => $this->integer()->notNull()->defaultValue(1),
                    'instruction' => $this->string(255)->notNull()->defaultValue(''),
                ]
            );
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%globalsnippets_groups}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%globalsnippets_groups}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'siteId' => $this->integer()->notNull()->defaultValue(Craft::$app->sites->currentSite->id),
                    'name' => $this->string(255)->notNull()->defaultValue(''),
                    'handle' => $this->string(255)->notNull()->defaultValue(''),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%globalsnippets_snippets}}','handle',true),'{{%globalsnippets_snippets}}','handle',true);
        // $this->createIndex(null, '{{%emaileditor_email}}', 'id', false);
        $this->createIndex($this->db->getIndexName('{{%globalsnippets_groups}}','handle',true),'{{%globalsnippets_groups}}','handle',true);
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey($this->db->getForeignKeyName('{{%globalsnippets_snippets}}', 'siteId'),'{{%globalsnippets_snippets}}','siteId','{{%sites}}','id','CASCADE','CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%globalsnippets_groups}}', 'siteId'),'{{%globalsnippets_groups}}','siteId','{{%sites}}','id','CASCADE','CASCADE');
        $this->addForeignKey(null, '{{%globalsnippets_snippets}}', ['snippetGroupId'], '{{%globalsnippets_groups}}', ['id'], 'CASCADE', null);
        $this->addForeignKey($this->db->getForeignKeyName('{{%globalsnippets_snippets}}','id'),'{{%globalsnippets_snippets}}','id','{{%elements}}','id','CASCADE','CASCADE');
        
    }

    /**
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%globalsnippets_snippets}}');

        $this->dropTableIfExists('{{%globalsnippets_groups}}');
    }
}
