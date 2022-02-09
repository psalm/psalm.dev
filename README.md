# psalm.dev

Has the source for psalm.dev

## Initial setup
- `docker-compose build --pull`
- `docker-compose up -d`
- `docker-compose exec php-apache composer install`
- Navigate to http://localhost:8080

## To build docs (for local preview)

- Run `composer update` (requires [Composer](https://getcomposer.org))
- Run `mkdocs build` (requires Python & [MkDocs](https://www.mkdocs.org/))

## To build styles (these files get committed)

- Run `npx webpack` (requires Node)
