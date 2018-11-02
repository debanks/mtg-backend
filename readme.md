# Personal Backend

This is the laravel PHP backend for my personal website, memories, and mememachine.
This should be paired with my [personal box](https://github.com/debanks/personal-box.git) for development.

## Setting up

If you are using the personal box you should have cloned this repo into the personal-box folder.
If nothing is running then navigate to your personal-box folder and run:

```
vagrant up
vagrant ssh
cd /var/www/backend
composer install
php artisan migrate:refresh --seed
```

composer might have already ran while setting up the box, the result of this should
be a fully set up backend database ready to test the different frontends.

You will have to update your hosts file, you can follow the instructions here: 
[Windows Host File](https://support.rackspace.com/how-to/modify-your-hosts-file/) and add:

```
192.168.10.10 api.davisbanks.test
```