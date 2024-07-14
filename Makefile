.PHONY: build up down restart shell composer artisan migrate fresh test setup
generate-swagger:
	docker-compose exec dj-connect-app php artisan l5-swagger:generate

build:
	docker-compose build

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

shell:
	docker-compose exec dj-connect-app bash

composer:
	docker-compose exec dj-connect-app composer $(filter-out $@,$(MAKECMDGOALS))

artisan:
	docker-compose exec dj-connect-app php artisan $(filter-out $@,$(MAKECMDGOALS))

migrate:
	docker-compose exec dj-connect-app php artisan migrate

fresh:
	docker-compose exec dj-connect-app php artisan migrate:fresh --seed

test:
	docker-compose exec dj-connect-app php artisan test

setup: 
	docker-compose exec dj-connect-app cp .env.example .env
	docker-compose exec dj-connect-app composer install
	docker-compose exec dj-connect-app php artisan key:generate
	docker-compose exec dj-connect-app php artisan migrate

update-prod: stop build up setup

%:
	@: