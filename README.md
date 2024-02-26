# psalm.dev

Has the source for psalm.dev

## Initial setup
- `docker-compose build --pull`
- `docker-compose up -d`
- `docker-compose exec php-apache composer install`
- Navigate to http://localhost:8080

## To build docs (for local preview)

- Or `docker-compose run --rm mkdocs build`

## To build styles (these files get committed)

- Run `docker-compose exec node npx webpack`
