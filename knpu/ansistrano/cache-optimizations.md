# Optimizing with Cache

Yay! The `var/cache` directory does *not* need to be writable and our life is simple
and all our cache dreams are fulfilled. Well actually, we can do more with caching to
make our site *screaming* fast. Zoom!

## APCu for Better Performance

First, let's install `apcu` - this should be a bit faster than OpCache. I'll do
this during provision. Open up `playbook.yml`. Down a bit, yep! Add a new package:
`php-apcu`:

[[[ code('b52dc0ac21') ]]]

This package is named a bit different than the others.

Let's get the provision started - use `playbook.yml` and add `-l aws` to only provision
the aws host:

```terminal-silent
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass -l aws
```

Use `beefpass` for the password.

## Doctrine Metadata and Query Caching

There's one other way we can boost performance. Open `app/config/config_prod.yml`:

[[[ code('35256b38b5') ]]]

See those Doctrine caches? Uncomment the first and third, and change them to `apcu`,
which our server will *now* have! Woo!

[[[ code('aca3545453') ]]]

The `metadata_cache_driver` caches the Doctrine annotations or YAML mapping... this
is not stuff we need to be parse on every request. The `query_cache_driver` is used
when querying: it caches the translation from Doctrine's DQL into SQL. This is
something that does not need to be done more than once.

So... now... I have the same question as before: do we need to clear this cache
on each deploy? Nope! Internally, Symfony uses a cache *namespace* for Doctrine
that includes the *directory* of our project. Since Ansistrano always deploys into
a new `releases/` directory, each deploy has its own, unique namespace.

When provisioning finishes, commit the new config changes:

```terminal-silent
git add -u
git commit -m "Doctrine caching"
```

Push them!

```terminal-silent
git push origin master
```

Now, deploy the site:

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

While we're waiting, find the server again and run:

```terminal
php -i | grep apcu
```

Yes! *Now* APCu is installed and working. Without doing *anything* else, Symfony's
`cache.system` service is already using it. And when the deploy finishes, thanks
to the Doctrine caching, we should have the fastest version of our site yet.

Except... for one more, fascinating cache issue. Actually, let's not call it a cache
issue. Let's call it a cache opportunity!
