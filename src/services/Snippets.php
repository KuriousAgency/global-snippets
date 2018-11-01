<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\services;

use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\models\Snippet;
use kuriousagency\globalsnippets\records\Snippets as SnippetRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Command;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class Snippets extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get a snippet by its ID.
     *
     * @param int $id
     */
    public function getSnippetById($id)
    {
        $result = $this->_createSnippetQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new Snippet($result) : null;
    }
    /**
     * Get all snipepts.
     *
     */
    public function getAllSnippets(): array
    {
        $rows = $this->_createSnippetQuery()->all();

        $snippets = [];
        foreach ($rows as $row) {
            $snippets[] = new Snippet($row);
        }

        return $snippets;
    }
    /**
     * Get snippets by group.
     *
     */
    public function getSnippetsByGroup($group): array
    {
        $rows = $this->_createSnippetQuery()
            ->where(['snippetGroup' => $group])
            ->all();

        $snippets = [];
        foreach ($rows as $row) {
            $snippets[] = new Snippet($row);
        }

        return $snippets;
    }
    /**
     * Get all snippet groups.
     *
     */
    public function getAllSnippetGroups(): array
    {
        $query =(new Query())
            ->select('snippetGroup')->distinct()
            ->orderBy('name')
            ->from(['{{%globalsnippets_snippets}} snippets'])
            ->all();

        $groups = [];
        foreach ($query as $row) {
            $groups[] = ucfirst($row['snippetGroup']);
        }

        return $groups;
    }
    /**
     * Save new Snippet Group.
     *
     */
    // public function saveNewSnippetGroup($newGroup): array
    // {
    //     // Add enum value to snippetGroup column

    //     return true;
    // }
    /**
     * Modify Snippet Group.
     *
     */
    public function saveSnippetGroup($newGroup,$oldGroup = null)
    {
        if ($oldGroup != null){
            $command = (new Query())
                ->createCommand()
                ->replace('globalsnippets_snippets','snippetGroup',$oldGroup,$newGroup);
            Craft::dd($command);
            return true;
        }
        Craft::dd('NEW NAME');
    }
            
    /**
     * Save an email.
     *
     * @param Email $model
     * @return bool
     * @throws \Exception
     */
    public function saveSnippet(Snippet $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = SnippetRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetRecord();
        }

        $record->name = $model->name;
        $record->snippetGroup = $model->snippetGroup;
        $record->content = $model->content;
        $record->handle = $model->handle;
        $record->instruction = $model->instruction;

        $record->save(false);

        // Now that we have a record ID, save it on the model
        $model->id = $record->id;

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving Emails.
     *
     * @return Query
     */
    private function _createSnippetQuery(): Query
    {
        return (new Query())
            ->select([
                'snippets.id',
                'snippets.name',
                'snippets.content',
                'snippets.handle',
                'snippets.snippetGroup',
                'snippets.instruction',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_snippets}} snippets']);
    }
}
