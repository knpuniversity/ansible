# Faster Deploy with Shared Files

Right now, each release is independent. And sometimes, that's a problem. For example,
each release has its *own* logs files. There's nothing in `logs/` right now, but
usually you'll find a `prod.log` file. The problem is that if you need to go look
at the log file to find out a problem, you might have to look through 10 separate
`prod.log` files across 10 separate deploys!

## Sharing a Path between Deploys

This is a perfect example of a file that we want to share *between* deployments.
Fortunately - as we talked about earlier - Ansistrano supports this. Check out the
Ansistrano documentation. Ah yes, we need this `ansistrano_shared_paths` variable!
Copy it! Then, in `deploy.yml`, I'll add it near the top.

It's simple, we want to share `var/logs`: that entire directory.

Oh,and now that `var/logs` will be a symlink, in `after-symlink-shared.yml`, under
the permissions task, we need to add `follow: true` so that the permissions change
will follow the symlink.

Oh, and back in `deploy.yml`... my variable didn't paste well. Make sure your
indentation is correct!

Now! Find your local terminal and deploy!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

## Shared Paths & Avoiding Server Storage

Use `beefpass` and deploy to master. So think about what other directories or files\
you might need to share between deploys, like `web/uploads` if you store uploaded
files on your server. Or, `web/imagine` if you use LiipImagineBundle. Otherwise,
all the thumbnails will need to be re-created after each deploy. Lame!

But, also keep in mind that there are some major advantages to *not* storing files
like these on your server. Instead of putting uploaded files in `web/uploads`, you
should store them in S3. If you put *nothing* extra on your server, it makes it
*really* easy to destroy your server and launch a new one... without needing to
worry about copying over a bunch of random files. It also makes using *multiple*
servers a possibility.

Ding! Move over to the terminal that's SSH'ed onto the server. Go out of `current/`
and then back in. Check out `var/logs`: it's now a symlink to `shared/var/logs`.
That directory is empty, but as soon as we log something, a `prod.log` will show
up there.

## Sharing Files for Performance

There's *one* other reason you might want to share some paths: speed! Right now,
our deploy is kinda slow. You may not have noticed because we're fast-forwarding,
but the `Install Node dependencies` task takes about 1.5 minutes to run! Woh!

Why is it so slow? Because it needs to download *all* of the dependencies on *every*
single deploy. But if we *shared* the `node_modules/` directory, then `yarn install`
would start with a *full* directory. It would only need to download any *changes*
since the last deploy! This is an easy win!

Add `node_modules` to the shared paths.

## Cleaning up old Releases

Oh, and before we deploy, it's time to fix one other thing. Back on the server,
go into the `releases/` directory:

```terminal-silent
cd releases
ls
```

Ok! It's getting busy in here! Each deploy creates a new directory... and this
will gone and forever and ever until we run out of disk space. Go back to the
Ansistrano docs and find the `ansistrano_keep_releases` variable. This is the key.
In `deploy.yml`, paste that and set it to 3.

Ok, let's try it! Find your local terminal and deploy:

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

Use `beefpass` and deploy to master. I'll fast-forward... but tell you how long
the deploy took. This *first* deploy will still be slow: the `node_modules/` directory
will *start* empty. By the way, the `composer install` command is also a little
bit slow... but not *nearly* as slow as `yarn`. Why? Because Composer caches the
packages behind the scenes. So even though the `vendor/` directory starts empty,
`composer install` runs *pretty* quickly. We *could* make it faster by sharing
`vendor/`... but that's a bad idea! We don't want to change the `vendor/` directory
of the live site while we're deploying.

Ok, done! I'm deploying to a *tiny*, slow server, so that took 3 and a half minutes.
One and a half of those was for `yarn install`!

Let's deploy a second time:

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

While we're waiting, go back into the server. Yes! There are only 3 releases.
In in `shared/`, `node_modules/` is populated, thanks to the last deploy.

When the deploy finishes... awesome! The `yarn install` task was almost *instant*,
and the deploy was almost two minutes faster!
