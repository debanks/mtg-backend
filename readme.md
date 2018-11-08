# Magic the Gathering: Arena Helper Backend

This is the laravel PHP backend for Magic the Gathering: Arena Helper (http://mtg.davisbanks.com).
This should be paired with my [mtg-frontend](https://github.com/debanks/mtg-frontend.git) for development.

## Setting up

If you are using the personal box you should have cloned this repo into the personal-box folder.
If nothing is running then navigate to your personal-box folder and run:

```
cd /path-to-project
composer install
php artisan migrate:refresh --seed
```

composer might have already ran while setting up the box, the result of this should
be a fully set up backend database ready for user/development.