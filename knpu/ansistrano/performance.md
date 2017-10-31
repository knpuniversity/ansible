# Optimizing Performance!

What about performance? Is our server optimized? Could our deploy somehow make our
code faster? Why actually... yes!

Google for Symfony performance to find an [article in the docs](https://symfony.com/doc/current/performance.html)
*all* about this.

## Optimized Autoloader

Scroll down a little: I want to start at the section about Composer's Class Map.
It turns out... autoloading classes - which happens *hundreds* of times on each
request... is kinda slow! Composer even has its own documentation about optimizing
the autoloader. Fortunately, making it fast is super simple: we just need to pass
a few extra flags to `composer install`.

Open up the Ansible documentation for the `composer` module. It has an option called
`optimize_autoloader` which actually *defaults* to true! In other words, thanks
to the module, we're already passing the `--optimize` flag as well as the `--no-dev`
flag.

The only missing option is `--classmap-authoritative`, which gives a minor performance
boost. But hey, I *love* free performance!

Open up `after-symlink-shared.yml` and find the Composer install task. Add `arguments`
set to `--classmap-authoritative`. I'll also set `optimize_autoloader` to true...
but that's the default anyways.

Oh, and there *is* one *other* way to optimize the autoloader: with a `--apcu-autoloader`
flag. This is meant to be used instead of `--classmap-authoritative`... but I'm
not sure if the performance will be much different. If you *really* care, you can
test it, and let me know.

## Guarantee OPCache

Back on the performance docs, at the top, the *first* thing it mentions is using
a byte code cache... so OPcache. If you ignore *everything* else I say, at *least*
make sure you have this installed. We already do. But, to be sure, we can open `playbook.yml`
and - under the extensions - add `php7.1-opcache`.

## opcache.max_accelerated_files

Ok, what other performance goodies are there? Ah yes, `opcache.max_accelerated_files`.
This defines how many files OPcache will store. Since Symfony uses a lot of files, we
recommend setting this to a higher value.

On *our* server, the default is 10,000, but the docs recommend 20,000. So let's
change it!

We already have some code that changes the `date.timezone` php.ini setting. In
that case, we modified *both* the cli *and* fpm config files. But because this
is just for performance, let's only worry about fpm. Copy the previous task and
create a new one called: `Increase OPcache limit of accelerated files`.

The `section` will be `opcache`. Why? On the server, open up the `php.ini` file
and hit `/` to search for `max_accelerated_files`. This is the setting we want to
modify. And if you scroll up... yep! It's under a section called `[opcache]`.

Tell the `ini_file` module to set the `option` `opcache.max_accelerated_files`
to a value of 20000.

## The Mysterious realpath_cache_size

There is just *one* last recommendation I want to implement: increasing
`realpath_cache_size` and `realpath_cache_ttl`. Let's change them first... and
explain later.

Go back to the `php.ini` file on the server and move *all* the way to the top.
The standard PHP configuration all lives under a section called `PHP`. If you looked
closely enough, you would find out that the two "realpath" options *indeed* live
here.

Copy the previous task and paste. Oh, and I'll fix my silly typo. Name the new
task "Configure the PHP realpath cache". This time, we want to modify *two*
values. So, for `option`, use the handy `{{ item.option }}`. And for `value`,
`{{ item.value }}`.

Hook this up by using `with_items`. Instead of simple strings, set the first option
to an array with `option: realpath_cache_size` and `value`, which should be `4096K`.
Copy that and change the second line: `realpath_cache_ttl` to 600.

## All about the realpath_cache

We *did* make one small change to our deploy playbook, but let's just trust it
works. The more interesting changes were to `playbook.yml`. So let's re-provision
the servers:

```terminal-silent
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass -l aws
```

While that works, I want to explain all this `realpath_cache` stuff... because I don't
think many of us *really* know how it works. Actually, Benjamin Eberlei even wrote
a [blog post about these settings](https://tideways.io/profiler/blog/how-does-the-php-realpath-cache-work-and-how-to-configure-it).
Read it to go deeper.

But here's the tl;dr: each time you require or include a file - which happens *many*
times on each request - the "real path" to that file is cached. This is useful for
symlinks: if a file lives at a symlinked location, then PHP figures out the "real"
path to that file, then *caches* a map from the original, symlinked path, to the
final, real path. That's the "realpath cache".

But even if you're *not* using symlinks, the cache is *great*, because it prevents
IO operations: PHP does not even need to *check* if the path is a symlink.

The point is: the realpath cache rocks and makes your site faster. And that's
*exactly* why we're making sure that the cache is big enough for the number of
files that are used in a Symfony app.

The `realpath_cache_ttl` is where things get *really* interesting. We're using a
symlink strategy in our deploy. And *some* sources will tell you that this strategy
plays *really* poorly with the "realpath cache". Why? Well, just think about: suppose
your `web/app.php` file requires `app/AppKernel.php`. Internally. `app.php` will
ask:

	Hey realpath cache! What is the *real* path to `/var/www/project/current/app/AppKernel.php`?

If we've recently deployed, then that path *may* already exist in the "realpath cache"...
but still point to the *old* release directory! In other words, the "realpath cache"
will continue to think that a bunch of our files still *truly* live in the *old*
release directory! This will happen until all the cache entries hit their TTL.

So here's the big question: if the "realpath cache" is such a problem... then why
are we *increasing* the TTL to an even *higher* value!?

Because... I lied... a little. Let me show you why the "realpath cache" is *not*
a problem. On your server, open up `/etc/nginx/sites-available/mootube.example.com.conf`.
Search for "real". Ah yes:

```conf
fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
```

This line helps pass information to PHP. The *key* is the `$realpath_root` part.
Thanks to this, by the time PHP executes, our code - like `web/app.php` *already*
knows that it is in a `releases` directory. That means that when it tries to require
a file - like `app/AppKernel.php`, it *actually* says:

	Hey realpath cache! What is the *real* path to `/var/www/project/releases/2017XXXXX/app/AppKernel.php`?

The *symlink* directory - `current/` is *never* included in the "realpath cache"...
because our own code thinks that it lives in the resolved, "releases" directory.
This is a long way of explaining that the "realpath cache" just works... as long
as you have this line.

Check on the provision. Perfect! It just finished updating the `php.ini` file.
Go check that out on the server and look for the changes. Yep! `max_accelerated_files`
looks perfect... and so do the `realpath` settings.

Next! Let's talk about rolling back a deploy. Oh, we of course *never* make mistakes...
but... ya know... let's talk about rolling back a deploy anyways... in case someone
*else* messes up.
