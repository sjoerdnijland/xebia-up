# Xebia Up

A learning-journey platform that helps Xebians level up through curated modules, paths, and bookings.

Built on Symfony 7, Doctrine ORM, and Twig.

## Requirements

- PHP 8.2+ with `ctype` and `iconv` extensions
- [Composer](https://getcomposer.org/)
- SQLite (default — bundled with PHP)

## Setup

```bash
composer install
cp .env .env.local         # set a real APP_SECRET in .env.local
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction   # optional seed data
```

## Run locally

```bash
php -S 127.0.0.1:8000 -t public
```

Then open <http://127.0.0.1:8000>.

## Layout

- `src/` — Symfony controllers, entities, repositories, fixtures
- `templates/` — Twig views (header / footer in `templates/base.html.twig`)
- `public/` — web root, including `css/app.css`, `js/app.js`, `img/`
- `config/` — Symfony framework + bundle config
- `migrations/` — Doctrine schema migrations

## Environment

The committed `.env` holds non-sensitive defaults. Override anything secret
(`APP_SECRET`, real `DATABASE_URL`, etc.) in a local `.env.local` — that file is
gitignored.

## License

See [LICENSE](LICENSE).
