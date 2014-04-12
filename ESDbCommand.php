<?php

/**
 * This file contains the ESDbCommand class.
 * Serializable CDbCommand derivative.
 * This depends on ESDbConnection as well.
 * @author Nathan Johnson <nathan@nathanjohnsopn.info>
 * @link http://nathanjohnson.info/
 * @license http://www.yiiframework.com/license/
 */

class ESDbCommand extends CDbCommand implements Serializable {

    protected $_bindParams = array();
    protected $_bindValues = array();

    /**
     * Binds a parameter to the SQL statement to be executed.
     * @param mixed $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form :name. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value Name of the PHP variable to bind to the SQL statement parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @param integer $length length of the data type
     * @param mixed $driverOptions the driver-specific options (this is available since version 1.1.6)
     * @return CDbCommand the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam($name, &$value, $dataType = null, $length = null,
        $driverOptions = null) {
        /* here we have to "fake" it for the serialized form,
         * upon unserialize bindParams become bindValues
         */
        $this->_bindParams[$name] =
            array(
                'value' => &$value,
                'dataType' => $dataType
            );

        return parent::bindParam($name, $value, $dataType, $length,
            $driverOptions
        );
    }

    /**
     * Binds a value to a parameter.
     * @param mixed $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form :name. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @return CDbCommand the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value, $dataType = null) {
        $this->_bindValues[$name] =
            array('value' => $value, 'dataType' => $dataType,);
        return parent::bindValue($name, $value, $dataType);
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to {@link bindValue} except that it binds multiple values.
     * Note that the SQL data type of each value is determined by its PHP type.
     * @param array $values the values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values.
     * For example, <code>array(':name'=>'John', ':age'=>25)</code>.
     * @return CDbCommand the current command being executed
     * @since 1.1.5
     */
    public function bindValues($values) {
        foreach ($values as $name => $value) {
            $this->_bindValues[$name] =
                array('value' => $value, 'dataType' => NULL,);
        }
        return parent::bindValues($values);
    }

    /**
     * This is some silliness to get around some private variable silliness.
     * @see Serializable::serialize()
     */
    public function serialize() {

        /* Reflection to get around private variable visibility of _query in
         * parent class
         */
        $refObj = new ReflectionClass('CDbCommand');
        $queryProp = $refObj->getProperty('_query');
        $queryProp->setAccessible(true);
        $_query = $queryProp->getValue($this);
        $connectionName = $this->getConnection()->connectionName;
        // convert bindParams to bindValues, taking values of variables
        // from the time of serialization.
        foreach ($this->_bindParams as $name => $props) {
            $this->_bindValues[$name] = array(
                'value' => $props['value'],
                'dataType' => $props['dataType']
                );
        }

        return serialize(
            array(
                '_query' => $_query,
                'connectionName' => $connectionName,
                '_bindValues' => $this->_bindValues,
                'params' => $this->params
            )
        );

    }

    /**
     * re-create a command using connection description.
     * @see Serializable::unserialize()
     */
    public function unserialize($data) {
        $obj = unserialize($data);
        parent::__construct(Yii::app()->getComponent($obj['connectionName']));
        $refObj = new ReflectionClass('CDbCommand');

        /* Reflection to get around private variable visibility of _query in
         * parent class
         */

        $queryProp = $refObj->getProperty('_query');
        $queryProp->setAccessible(true);
        $queryProp->setValue($this, $obj['_query']);

        foreach ($obj['_bindValues'] as $name => $params) {
            $this->bindValue($name, $params['value'], $params['dataType']);
        }
        $this->params = $obj['params'];

    }
}
