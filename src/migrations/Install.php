<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\migrations;

use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\records\Snippets as SnippetRecord;
use kuriousagency\globalsnippets\records\SnippetGroup as SnippetGroupRecord;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\helpers\DateTimeHelper;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
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
                    'content' => $this->longText()->notNull()->defaultValue(''),
                ]
            );
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
    }

    /**
     * CAN BE REMOVED WHEN APPROVED
     * @return void
     */
    protected function insertDefaultData()
    {
        $snippets = [
            ['name' => 'Sign In Text',
                    'handle' => 'signInText',
                    'snippetGroupId' => 1,
                    'instruction' => 'This message is displayed on log in screens',
                    'content' => 'Please sign in to access your account.',
            ],
            ['name' => 'Registration',
                    'handle' => 'registration',
                    'snippetGroupId' => 1,
                    'instruction' => 'This message is displayed on sign up screens',
                    'content' => 'Create an account with us to be able to view your order history, track orders and store details to be able to quickly pass through the checkout in future.',
            ],
            ['name' => 'Checkout Registration',
                    'handle' => 'checkoutRegistration',
                    'snippetGroupId' => 1,
                    'instruction' => 'This message is displayed on when people create an account on checkout',
                    'content' => 'If you already have an account you can login now, if not it\'s simple to create one for the future.',
            ],
        ];

        $snippetTable = SnippetRecord::tableName();
        $todayDateTime = DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s');
        $siteId = Craft::$app->sites->currentSite->id;

        $groupTable = SnippetGroupRecord::tableName();
        $groupRecord = [
            'siteId' => $siteId,
            'dateCreated' => $todayDateTime,
            'dateUpdated' => $todayDateTime,
            'name' => 'Generic',
            'handle' => 'generic',
        ];
        $this->insert($groupTable,$groupRecord);


        foreach ($snippets as $snippet){
            
            $snippetRecord = [
                //'id' => $this->db->getLastInsertID($snippetTable),
                'siteId' => $siteId,
                'dateCreated' => $todayDateTime,
                'dateUpdated' => $todayDateTime,
                'name' => $snippet['name'],
                'handle' => $snippet['handle'],
                'snippetGroupId' => $snippet['snippetGroupId'],
                'instruction' => $snippet['instruction'],
                'content' => $snippet['content'],
            ];
            $this->insert($snippetTable,$snippetRecord);
        }
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
