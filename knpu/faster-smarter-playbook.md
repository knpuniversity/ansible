# Faster Smarter Playbook

If you executed the playbook twice in a row, you would see "Changed 2". On *every*
playbook run, we're doing unnecessary work: we're *always* downloading Composer
and then moving it globally... even if it's already there!

That's not a big deal... but it *is* wasteful. Let's make our playbook smarter by
running tasks *conditionally*, based on *facts* that we collect about the host.
In other words: let's *not* download Composer if it already exists!

## Checking if a File Exists: stat

So that's the first mission: figure out if the `/usr/local/bin/composer` file exists.
To do that, we can use a really handy module called `stat`. It's similar to the
Unix `stat` command... which gives you a ton of info about a file.

Before we download composer, add a new task called "Check for Composer". Use the
`stat` module and set the path to `/usr/local/bin/composer`. Then, `register` a
new variable called `composer_stat`. And don't forget the `deploy` tag!

To see what goodies that variable has in it, add a `debug` task to print `composer_stat`.
Give that the `deploy` tag too.

Ok! Change over to your terminal and run the playbook with `-t deploy`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

There it is! Hit CTRL+C to quit.

The new variable has a `stat` key with a lot of info. The key we want is `exists`.
Remove the `debug` task.

## Skipping Tasks

Our goal is to *skip* the next three tasks if that file exists. Like before, use
the `when` key set to `not composer_stat.stat.exists`. In other words, check the
`stat.exists` key, and if that is true, we want to *not* run this.

Copy that and put it below "Move Composer globally", and also "Set Permissions on Composer".

I think we're ready! Try it:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Wait... yes! Skipped! Stop the playbook.

## Upgrading Composer

This is a nice performance improvement... but like with a lot of things in programming,
new features mean new complexity. Thanks to this... the Composer executable will
eventually become *really* old and out-of-date. We need to make sure it's upgraded
to the latest version.

Below the set permissions task, add a new one called
"Make sure Composer is at its latest version". This time we can use the `composer`
module. Set `working_dir` to `{{ symfony_root_dir }}`... though that doesn't matter
in this case... because we'll use `self-update` to upgrade Composer.

We can even add `when: composer_stat.stat.exists` to avoid running this if Composer
was just downloaded. Give it our favorite `deploy` tag.

Ok, run it! But this time, add a `--verbose` flag:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy --verbose
```

This is a *sweet* trick... because... as you can see, it prints the output from
each task... which can save us from needing to add so many `debug` tasks to print
variables.

Boom! The new Composer upgrade task ran! I'll stop with "CTRL+C". And, we can see
the `stdout`: `You are already using composer version 1.4.1`.

So it worked! But like with other tasks... it's reporting as "Changed" even though
it did *not* actually change anything! We already know how to fix that. Scroll down
to the "Create DB" task and copy its `changed_when`. Scroll back up and paste it
under this task. Register a new variable - `composer_self_update` - and use that
in `changed_when`. Then, all we need to do is search for `You are already using composer version`.

If that shows up in the command, then we know nothing changed. Paste that into the
`search` filter.

Test it out!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy --verbose
```

There it is! This time it's Okay - not changed. And on that note, we can even start
skipping tasks based on whether or not other tasks are "changed" or "ok". Let's
try that next!
