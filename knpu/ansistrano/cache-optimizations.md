# Optimizing with Cache

Yay! The `var/cache` directory does *not* need to be writable and our life is simple.
But, we *can* do a few things with caching to make our site faster. Zoom!

## APCu for Better Performance

First, let's install `apcu` - it should be a bit faster than using OpCache. I'll do
this during provision. Open up `playbook.yml`. Down a bit, yep! Add a new package:
`php-apcu`. This one is named a bit different than the others.

Let's get the provision started - use `playbook.yml` and add `-l aws` to only provision
the aws host:

```terminal-silent
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass -l aws
```

Use `beefpass` for the password.

## Doctrine Metadata and Query Caching

There's one other way we can boost performance. Open `app/config/config_prod.yml`.
See those Doctrine caches? Uncomment the first and third, and change them to `apcu`,
which our server will *now* have! Woo!

The `metadata_cache_driver` caches Doctrine annotations or YAML... so that they
don't need to be parsed on each request. The `query_cache_driver` is used internally
when querying: it caches the translation from Doctrine's DQL into SQL. Basically,
this is something that should not be done more than once.


---> HERE

That metadata cache drivers what caches your annotations or Gammel so that you
don't have to parse that on every single request. The query cache driver is
what caches you r d q L to Eskew l transformation. Basically. Something that
should not be done over and over and over again. By the way. Speaking about the
Kashdan system.

One really cool thing is that you don't have to worry about clearing any of the
cache. And that's because whenever you do the cache warm up Symphonie generates
a random cache key. So that every time you cache warm up it generates a fresh.
A set of cash for that deploy and the old stuff is automatically not used. For
doctrine. Every time you change your annotations or change your Gammel file
that's going to generate a new Caskey. And so naturally you'll have new. Cash
keys inside of. Issuers that's done.

Let's commit our new config changes. Push those.

And then deploy our actual site. And if you go over to your server right now
you can really run that Pietsch be Dash. I grab a PC you. And ABC is now
installed and enabled and just by doing that the instant you do that. The cache
that system service will start to use APC you it's dynamic and looks for the
best possible way to cache things. And once our deploy finishes we should have
the fastest version of our site yet. Well except that there's still. One.
Little fascinating. Cache issue.
