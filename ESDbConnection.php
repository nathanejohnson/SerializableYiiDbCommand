<?php
class ESDbConnection extends CDbConnection implements Serializable {
    public $connectionName = 'db';

    public function serialize() {
        return NULL;
    }
    public function unserialize($data) {
    }

    public function createCommand($query=NULL) {
        $this->setActive(true);
        return new ESDbCommand($this, $query);
    }
}