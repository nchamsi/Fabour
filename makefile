db:
	php bin/console doctrine:database:drop --force || php bin/console doctrine:database:create
schema:
	php bin/console doctrine:schema:update --force
	php bin/console doctrine:fixtures:load
schemaUpdate:
	php bin/console doctrine:schema:update --force
schemaDump:
	php bin/console doctrine:schema:update --dump-sql
validate:
	php bin/console doctrine:schema:validate
regenerate:
	bin/console make:entity --regenerate
routes:
	php bin/console fos:js-routing:dump --format=json --target=assets/js/routes.json
server:
	php bin/console server:run	
gos_server:
	bin/console gos:websocket:server
npm:
	npm run watch
router:
	bin/console debug:router
phpstorm:
	nohup phpstorm.sh & 
cache:
	bin/console cache:clear
