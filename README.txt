//TODO in development instance
sudo apt-get install awscli
sudo apt-get install php7.0
// for AWS PHP SDK
sudo apt-get install php7.0-xml
// CREDENTIALS FOR PHP SDK (Get Keys)
aws configure

//TODO in production instance

#git clone https://github.com/mdifelice/notejam application
sudo chown www-data:www-data * -R
cd /var/www/html/application/cakephp/notejam
sudo curl -s https://getcomposer.org/installer | sudo php
sudo php composer.phar install
sudo bin/cake migrations migrate

'Datasources' => [
        'default' => [
            'driver' => 'Cake\Database\Driver\Mysql',
            'username' => 'root',
            'host' => '<HOST>';
            'password' => '<DB_PASS>',
            'database' => '<SYSTEM_NAME>',
	]
]
