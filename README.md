##Requirements
Yii 1.1.x, php 5.3+

## Yii Serializable Database Command

This is a Yii extension that allows a user to freeze and thaw a CDbCommand.  This could be useful for, as an example, being able to "freeze" a report that might take a while, send the job to a queueing system and have it picked up by a worker process (perhaps a Yii command line process) that would then "thaw" the job and execute it asynchronously.  This would give you the ability to have one method for creating reports, and makes it trivial to either process it synchronously within the request or farm it out.

##ESDbConnection

This class extends CDbConnection, and adds the following parameter:

    connectionName

This parameter is used to signal what the connection name is on the thawing side.  Defaults to 'db'.  As an example:

        'db' => array(
            'class' => 'ESDbConnection',
            'connectionName' => 'db',
            'connectionString' => 'mysql:host=big.database.server.com;dbname=webscale',
            'emulatePrepare' => false,
            'username' =>'bob',
            'password' => 'dobbs',
            'charset' => 'latin1',
            'enableProfiling' => true,
            'enableParamLogging' => true,
        ),

It also overrides the createCommand method to return an ESDbCommand object versus an CDbCommand object.

Which brings us to:

##ESDbCommand

This is where the "magic" happens.  This class is where the actual serialization takes place.  This is a drop-in replacement for CDbCommand, with the exception that this will survive a serialize and unserialize round trip.  It uses the ESDbconnection 'connectionName' to know what connection to get from the config, preventing redundant connections to the database.

As long as you've configured a database connection with the ESDbConnection as above, to use ESDbCommand, you simply:


    $command = Yii::app()->db->createCommand();


