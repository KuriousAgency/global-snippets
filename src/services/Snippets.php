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
use kuriousagency\globalsnippets\models\SnippetGroup;
use kuriousagency\globalsnippets\records\Snippets as SnippetRecord;
use kuriousagency\globalsnippets\records\SnippetGroup as SnippetGroupRecord;


use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Command;
use yii\base\Exception;
use craft\events\ConfigEvent;
use craft\helpers\Db;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class Snippets extends Component
{
	const CONFIG_SNIPPET_KEY = 'snippets.snippet';
	const CONFIG_SNIPPET_GROUP_KEY = 'snippets.group';

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
     * Get a snippet by its Handle.
     *
     * @param int $id
     */
    public function getSnippetByHandle($handle)
    {
        $result = $this->_createSnippetQuery()
            ->where(['handle' => $handle])
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
    public function getSnippetsByGroup($groupId): array
    {
        $rows = $this->_createSnippetQuery()
            ->where(['snippetGroup' => $groupId])
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
        $query = (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.handle',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_groups}} groups'])
            ->all();

        $groups = [];
        foreach ($query as $row) {
            $groups[$row['id']] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'handle' => $row['handle']
            );
        }
        // Craft::dd($groups);
        return $groups;
    }

    public function saveSnippetGroup(SnippetGroup $model)
    {
        $isNewGroup = !$model->id;
        if ($model->id) {
            $record = SnippetGroupRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetGroupRecord();
        }
        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->save(false);

        if ($isNewGroup) {
            $model->id = $record->id;
		}
		
		$projectConfig = Craft::$app->getProjectConfig();
		$configData = [
			'name' => $record->name,
			'handle' => $record->handle,
		];
		$configPath = self::CONFIG_SNIPPET_GROUP_KEY . '.' . $record->uid;
		$projectConfig->set($configPath, $configData);

        return true;
    }
            
    /**
     * Save a snippet.
     *
     * @param Snippet $model
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
		
		$projectConfig = Craft::$app->getProjectConfig();
		$configData = [
			'name' => $record->name,
			'snippetGroup' => $record->snippetGroup,
			'content' => $record->content,
			'handle' => $record->handle,
			'instruction' => $record->instruction,
		];
		$configPath = self::CONFIG_SNIPPET_KEY . '.' . $record->uid;
		$projectConfig->set($configPath, $configData);

        return true;
    }

    public function deleteSnippetById($id)
    {
        $snippet = SnippetRecord::findOne($id);

        if ($snippet) {
			Craft::$app->getProjectConfig()->remove(self::CONFIG_SNIPPET_KEY . '.' . $snippet->uid);
            return true;//$snippet->delete();
        }

        return false;
    }
    public function deleteSnippetsByGroup($groupId)
    {
        $snippets = $this->getSnippetsByGroup($groupId);
        foreach ($snippets as $model){
            if ($model){
                $record = SnippetRecord::findOne($model->id);
                $record->delete();
            }
        }
    }

    public function deleteSnippetGroupById($id)
    {
        $group = SnippetGroupRecord::findOne($id);
        if ($group) {
			Craft::$app->getProjectConfig()->remove(self::CONFIG_SNIPPET_GROUP_KEY . '.' . $group->uid);
            //$this->deleteSnippetsByGroup($id);
            return true;//$group->delete();
        }
        return false;
	}
	


	public function handleChangedSnippet(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$data = $event->newValue;

		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$record = $this->_getSnippetRecord($uid);

			$record->name = $data['name'];
        	$record->snippetGroup = $data['snippetGroup'];
        	$record->content = $data['content'];
        	$record->handle = $data['handle'];
			$record->instruction = $data['instruction'];
			$record->uid = $uid;

			$record->save(false);
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleDeleteSnippet(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$record = $this->_getSnippetRecord($uid);

		if (!$record->id) {
			return;
		}

		$db = Craft::$app->getDb();
		$transaction = $db->beginTransaction();

		try {
			$record->delete();
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleChangedSnippetGroup(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$data = $event->newValue;

		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$record = $this->_getSnippetGroupRecord($uid);

			$record->name = $data['name'];
        	$record->handle = $data['handle'];
			$record->uid = $uid;

			$record->save(false);
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleDeleteSnippetGroup(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$record = $this->_getSnippetGroupRecord($uid);

		if (!$record->id) {
			return;
		}

		$db = Craft::$app->getDb();
		$transaction = $db->beginTransaction();
		
		try {
			$snippets = SnippetRecord::find(['snippetGroup' => $record->handle])->limit(null)->all();
			foreach ($snippets as $snippet)
			{
				$this->deleteSnippetById($snippet->id);
			}

			$record->delete();
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
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

    /**
     * Gets a snippet group record or creates a new one.
     * 
     * @param SnippetGroup $group
     * @return SnippetGroupREcord
     */
    private function _getGroupRecord(SnippetGroup $group)
    {
        if ($group->id) {
            $groupRecord = SnippetGroupRecord::findOne($group->id);

            if (!$groupRecord) {
                // throw new Exception("No field group exists with the ID '{$group->id}'");
            }
        } else {
            $groupRecord = new SnippetGroupRecord();
        }

        return $groupRecord;
	}
	
	private function _getSnippetRecord(string $uid): SnippetRecord
	{
		return SnippetRecord::findOne(['uid' => $uid]) ?? new SnippetRecord();
	}

	private function _getSnippetGroupRecord(string $uid): SnippetRecord
	{
		return SnippetGroupRecord::findOne(['uid' => $uid]) ?? new SnippetGroupRecord();
	}
}
