# Cache Permission Secrets

Why are the `cache` files writable by everyone? The answer is inside our code.

Open up `bin/console`. In my project, I uncommented out a `umask(0000)` line. I
also added this in `web/app.php`. Thanks to this, whenever Symfony creates a file -
like some cache files - the permissions default to be writable by everyone.

## No umask: Making Cache not Writable

I added these *precisely* to avoid permissions problems. But it's time to fix them
properly. In `app.php`, comment that out. In `console`, comment it out too. But
then copy it and move it inside the debug if statement.

During development, the `umask()` makes our life really easy... so I want to keep
it. In fact, in `web/app_dev.php`, we also have a `umask()`.

Find your local terminal, commit those changes and push them. Deploy!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

Let's see what happens without `umask` on production. When it finishes, find your
server terminal, move out of `current/` and then back in. Check the permissions:

```terminal
ls -l var/cache/prod
```

There it is! The files are writable by the user and group, but *not* by everyone.
Our web server user - `www-data` - is *not* in the same group as our terminal user.
Translation: the cache files are *not* writable by the web server.

So... will the sit work? Moment of Truth. Refresh! It works! Woh! This is huge!

## The Magical cache:warmup

How is this possible? How can the site work if our Symfony app can't write to the
`cache/` directory? The key is the `cache:warmup` task.

I'm going to tell you a *little* lie first. The `cache:warmup` command creates
*every* single cache file that your application will *every* need. Thanks to this,
the `cache` directory can *totally* be read-only after running this command.

## Some Cache Cannot be Warmed Up

Great, right? Now, here is the *true* story. The `cache:warmup` task creates *almost*
all of the cache files that you will ever need. But there are certain types of
things that simply *can't* be cached during warmup: they *must* be cached at the
moment they're needed. These include the serializer and validation cache, for example.

Knowing this, our site works now, but *should* break as soon as we try to use the
serializer or validation system... because Symfony won't be able to cache those.
Well, let's try it!

I created an API endpoint: `/api/videos` that uses the serializer. Try it! Woh!
It works! But... the serializer cache *shouldn't* be able to save. What the heck
is going on?

## The Dynamic cache.system Service

Here is the secret: whenever Symfony needs to cache something *after* `cache:warmup`,
it uses a service called `cache.system` to do this. This is not a service you should
use directly, but it's *critical* internally. This service is special, because
it automatically tries *several* ways of caching. First, if APCu is available, it
uses that. On the server, check for it:

```terminal
php -i | grep apcu
```

Right now, we don't have that. No problem, the service then checks to see if Opcache
is installed:

```terminal-silent
php -I | grep opcache
```

We *do* have this installed, and you should to. Thanks to this, instead of trying
to write to the `var/cache` directory, Symfony uses a temporary file storage and
a super fast caching mechanism.

If APCu *and* OpCache are not installed, *then* it finally falls back to trying to
write to the `cache/` directory. So basically, in order for the `cache` directory
to be read only... we don't need to do anything! Just, install OpCache - which you
should *always* have - or APCu.

Great! But, I do have one more question: if we use APCu or OpCache, do we need to
somehow clear these caches when we deploy? If some validation config was cached
to APCu and that config is *changed*... don't we need to clear the old cache when
we deploy? Actually, no! Each time you deploy, well, each time you run `cache:warmup`,
Symfony chooses a new, random cache key to use for `system.cache`. This effectively
clears the system cache on each deploy.

This is a *long* way of saying that... well... the cache directory simply does *not*
need to be writable. But, we *can* do a few things to improve performance!
