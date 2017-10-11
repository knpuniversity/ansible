# Priming cache.app

Watch closely: our production site is *super* slow! It takes a few *seconds* to
load! What!? It's *especially* weird because, locally in the dev environment, it's
way faster: just a few hundred milliseconds!

Why? Open `src/AppBundle/Controller/DefaultController.php`. On the homepage, we
show the total number of videos and the total number of video views. To get these,
we first look inside a cache: we look for `total_video_uploads_count` and `total_video_views_count`.
If they are *not* in the cache, *then* we calculate those and *store* them in the
cache.

To calculate the number of videos, we call `$this->countTotalVideoUploads()`. That's
a private method in this controller. It generates a random number... but has a `sleep`
in it! I added this to simulate a slow query. The `countTotalVideoViews()` *also*
has a sleep.

So why is our site so slow? Because I put a sleep in our code! I'm sabotaging us!
But more importantly, for some reason, it seems like the cache system is failing.
Let's find out why!

## Hello cache.app

First, look at the `getAppCache()` method. To cache things, we're using a service
called `cache.app`. This service is *awesome*. We already know about the `system.cache`
service: an internal service that's used to cache things that make the site functional.
The `cache.app` service is for *us*: we can use it to cache whatever we want! And
unlike `system.cache`, it is *not* cleared on each deploy.

So why is this service failing? Because, by default, it tries to cache to the *filesystem*,
in a `var/cache/prod/pools` directory. On production, we know that this directory
is *not* writable. So actually, I'm surprised the site isn't broken! This service
should not be able to write its cache!

## Caching Failing is not Critical

To understand what's going on, lets mimic the issue locally. First, run:

```terminal
bin/console cache:clear
```

This will clear and warm up the `dev` cache. Then, run:

```terminal
sudo chmod -R 000 var/cache/dev/pools
```

Now, our *local* site should won't be able to cache either.

Let's see what happens. Refresh! Huh... the site works... but it's *slow*. And
the web debug toolbar is reporting a few warnings. Click to see those.

Woh! There are two warnings:

	Failed to save key total_video_uploads_count

and

	Failed to save key total_video_views_count

Of course! If caching *fails*, it's not fatal... it just makes our site slow.
This is what's happening on production.

Let's fix the permissions for that directory:

```terminal-silent
sudo chmod -R 777 var/cache/dev/pools
```

## Production Caching with Redis

So how can we fix this on production? We *could* make that directory writable, but
there's a much better way: change `cache.app` to *not* use the file system! We
already installed Redis during provision, so let's use that!

How? Open `app/config/config.yml`. Actually, use `config_prod.yml`, to only use this
in production. Add `framework`, `cache` and `app` set to `cache.adapter.redis`.

`cache.adapter.redis` is the id of a *service* that Symfony automatically makes
available. You can also use `cache.adapter.filesystem` - which is the default -
`doctrine`, `apcu`, `memcached` or create your own service. If you need to configure
redis, use the `default_redis_provider` key under app, set to `redis://` and then
your connection info. There are similar config keys to control the other cache adapters.

Since we just changed our code, commit that change. Then, push and dance! And then
deploy!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

When the deploy finishes... try it! The first refresh should be slow: it's creating
the cache. Yep... slow... Try again. Fast! Super fast! Our cache system is fixed!

## Do Not Clear cache.app on Deploy

As we talked about earlier, the `cache.system` cache is effectively cleared on each
deploy automatically. But `cache.app` is *not* cleared on deploy... and that's good!
We're caching items that we do *not* want to automatically remove.

Actually... in Symfony 3.3, that's not true: when you run `cache:clear`, this *does*
empty the `cache.app` cache. This is actually a *bug*, and it's fixed in Symfony
3.4. If you need to fix it for Symfony 3.3, open `app/config/services.yml` and
override a core service.

The details of this aren't important, and if you're using Symfony 3.4 or higher,
you don't need this.

Oh, and if you *do* want to clear `cache.app`, use:

```terminal
bin/console cache:pool:clear cache.app
```
