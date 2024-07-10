# Makefile

.PHONY: generate-swagger

generate-swagger:
	docker-compose exec dj-connect-app php artisan l5-swagger:generate