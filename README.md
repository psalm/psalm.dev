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

## Updating the DB schema

psalm.dev uses [phinx migrations](https://phinx.org/) to manage the database schema. To create a new migration, run:

```bash
docker compose exec php-apache vendor/bin/phinx create MyNewMigration
```

Migrations are applied automatically when psalm.dev updates from the master branch.

See https://book.cakephp.org/phinx/0/en/migrations.html#creating-a-new-migration to learn more about creating migrations.
