# Anatomy of an Ansistrano Deploy

Go back to the Ansistrano GitHub page. Find the table of contents near the top,
and click [Deploying][deploying]. Excellent! This tells us how to use this role
*and* how it works! Ansistrano is based off of Capistrano... which means it creates
a really cool directory structure on your server. In this example, we're deploying
to a `/var/www/my-app.com` directory on the server. Each time we deploy, it creates
a new, timestamped, directory inside `releases/` with our code. Then, when the deploy
finishes, it creates a `symlink` from a `current/` directory to this release directory.

Next time you deploy? Yep, the same thing happens: it will create a *new* release
directory and update the symbolic link to point to it instead. This means that
our web server should use the `current/` directory as its document root.

This is *amazing*. Why? With this setup, we can patiently do *all* the steps needed
to prepare the new release directory. *No* traffic hits this directory until the
very end of deployment, when the symbolic link is changed.

There's also a `shared/` directory, which allows you to share some files between
releases. We'll talk more about that later.

## Set the Deploy Directory then Deploy!

To start with Ansistrano... well... the *only* thing we need to do is tell it *where*
to deploy to on the server! How? The same way you control any role: by overriding
*variables* that it exposes.

Scroll up a little on their docs to find a *giant* box of variables. Yes! This tells
you *every* single possible variable that you can override to control how Ansistrano
works. This is documentation gold!

The first variable we need `ansistrano_deploy_to`. Copy that. Inside `deploy.yml`,
add a `vars` key and paste. Set this to the directory that's already waiting
on our server: `/var/www/project`:

[[[ code('631bd54f69') ]]]

Ok... well... we haven't done much... but let's see if it works! In your local terminal,
run the same command as before, but without the `--list-tasks` flag:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

Ok... it looks like it's working. A few of the tasks mention `rsync`. That's because,
by default, Ansistrano uses rsync to get the files from your local machine up to
your server. We'll change to a different strategy in a few minutes.

Ding! It finished! Let's go see what it did! Change to the terminal where you're
SSH'ed onto your server. Inside `/var/www/project`, run `ls`.

```terminal-silent
ls
```

Awesome! We have the Ansistrano directory structure: `current/`, `releases/` and
`shared/`. So far, we only have one directory in `releases/` and `current/` is a
symlink to it.

Now, move into the `current/` directory and look inside:

```terminal-silent
cd /var/www/project/current
ls
```

Woh! There's almost nothing here: just a `REVISION` file that Ansistrano created
and an `ansible/` directory... which is a copy of our local `ansible/` directory.

This looks weird... but it makes sense! Right now, Ansistrano is using `rsync` to
deploy *only* the directory where the playbook lives... so, `ansible/`. This is not
what we want. So next, let's change our deployment strategy to `git` so that Ansistrano
pulls down our *entire* repository.


[deploying]: https://github.com/ansistrano/deploy#deploying
