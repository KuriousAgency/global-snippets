<?php
namespace kuriousagency\globalsnippets\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SnippetQuery extends ElementQuery
{
    public $name;
    public $handle;
    public $snippetGroupId;
    public $instruction;

    public function name($value)
    {
        $this->name = $value;

        return $this;
    }

    public function handle($value)
    {
        $this->handle = $value;

        return $this;
    }
    
    public function snippetGroupId($value)
    {
        $this->snippetGroupId = $value;

        return $this;
    }

    public function instruction($value)
    {
        $this->instruction = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the emaileditor table
        $this->joinElementTable('globalsnippets_snippets');

        // select the columns
        $this->query->select([
            'globalsnippets_snippets.name',
            'globalsnippets_snippets.handle',
            'globalsnippets_snippets.snippetGroupId',
            'globalsnippets_snippets.instruction'    
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam('globalsnippets_snippets.name', $this->name));
        }
        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('globalsnippets_snippets.handle', $this->handle));
        }
        if ($this->snippetGroupId) {
            $this->subQuery->andWhere(Db::parseParam('globalsnippets_snippets.snippetGroupId', $this->snippetGroupId));
        }
        if ($this->instruction) {
            $this->subQuery->andWhere(Db::parseParam('globalsnippets_snippets.instruction', $this->instruction));
        }
        return parent::beforePrepare();
    }
}