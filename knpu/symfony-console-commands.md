# Symfony Console Commands

The project is working! Except... that it's not actually *our* project: this is the
Symfony Standard Edition. Our cow customers are waiting: let's install MooTube!

Head over to https://github.com/knpuniversity/ansible to find the code behind this
project. Copy the clone URL and open your editor. Find the spot where we clone the
repo and use *our* new URL. You know the drill: run the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Our repository is public... which makes life easy. If you have a *private* repository,
you'll need to make sure that your server has access to it. They talk about that
a bit in the `git` module docs. You can also use a deploy key.

## Using the Console

Once we have the code, we need to setup a few other things, like the database.
The `README.md` file talks about these: after you download the composer dependencies,
you can set up the database by running these three commands. Each runs through Symfony's
console: an executable file in the `bin/` directory.

This is a perfect situation for the `command` module... because... well, we literally
just need to run 3 commands. Head to your playbook. Right above the handlers, add
a comment: "Symfony Console Commands". We'll start with a task called "Create DB
if not exists". Use the `command` module. For the value... we need to know the path
to that `bin/console` file.

This is another good spot for a variable! Create a new one called `symfony_console_path`
set to `{{ symfony_root_dir }}/bin/console`.

Use that in the command: ``{{ symfony_console_path }} doctrine:database:create --if-not-exists``.
That last flag prevents an error if the database is already there.

Awesome! Copy that task to create the second one: "Execute migrations". Use
`doctrine:migrations:migrate --no-interaction`. And add one more: "Load data fixtures".
This is something that we only want to run if this is a development machine, because
it resets the database. We'll talk about controlling that later.

For this command, use `hautelook_alice:doctrine:fixtures:load --no-interaction`.

Ok! The 3 commands are ready! Head back to the terminal. Woh! It exploded!

And actually... the reason is not that important: it says an error occurred during
the `cache:clear --no-warmup` commmand. After we run `composer install`, Symfony
runs several post install commands. One clears the cache. Changing from one project
to an entirely *different* project temporarily put things in a weird state. This one
time, in the virtual machine, just remove the cache manually:

```terminal
rm -rf var/cache/*
```

Try the playbook now:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

This time `composer install` should work and *hopefully* our new commands will setup
the database. By the way! A Symfony 3 app reads its configuration from a `parameters.yml`
file... which is *not* committed to the repository. So... in theory, that file should
*not* yet exist... and none of this should work. But that file *does* exist! Why?
Thanks to a special line in `composer.json`, after `composer install` finishes, the
`parameters.yml.dist` file is copied to `parameters.yml`. And thanks to that dist
file, Symfony will try to connect to MySQL using the `root` user and no password.
If that's *not* right, just modify the file on the VM directly for now. Later, we'll
talk about how we could properly update this file.

Yes! It worked! Notice: the three new tasks all say *changed*. That's because the
`command` module isn't smart enough to know whether or not these *actually* changed
anything. But, more on that soon!

Find your browser and refresh! Welcome to MooTube! The fact that it's showing these
videos means our database is working. Now, let's talk about *tags*: a cool way to
help us run only *part* of our playbook.
