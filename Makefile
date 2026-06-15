up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

serve:
	php artisan serve

migrate:
	php artisan migrate

fresh:
	php artisan migrate:fresh --seed
