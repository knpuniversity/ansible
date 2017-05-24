# Variables and parameters.yml

How could we handle *sensitive* variables - like a database password? Well, committing
them to our playbook is probably *not* a good idea. Nope, we need something better!

## Organizing Vars into a File

First, let's reorganize a little bit! Create a new `vars/` directory with a `vars.yml`
file inside. Now, copy *all* of the variables, add the `---`, paste them here, and -
you know the drill - un-indent them:

[[[ code('6660a44126') ]]]

Ansible gives us a way to import variables from a file... called `vars_files`. Point
it to `./vars/vars.yml`:

[[[ code('39bc960b36') ]]]

Cool! Believe it or not, we're one step closer to being able to handle *sensitive*
configuration.

## Adding the secret Variable

In your VM move to `/var/www/project`:

```terminal
cd /var/www/project
```

I want to look at the `app/config/parameters.yml` file:

```terminal
cat app/config/parameters.yml
```

This file holds config for the Symfony project, like the database password. Notice
one is called `secret`. This is *supposed* to be a unique string that's used for
creating some random strings. Right now ours is... not so secret: that's the default
value from Symfony.

Let's set this for real! In the vars.yml file, create a new variable: `symfony_secret`
set to `udderly secret $tring`:

[[[ code('cb0f8fe7f9') ]]]

Now, in `symfony-bootstrap.yml`, we can use that variable to modify `parameters.yml`.
Create a new task: "Set Symfony secret in parameters.yml". Use our favorite `lineinfile`
module with `dest` set to `{{ symfony_root_dir }}` - that's a variable from our vars
file - `{{ symfony_root_dir }}/app/config/parameters.yml`:

[[[ code('') ]]]

For `regexp`, use `^    secret:`. Yep, we're looking for 4 spaces then `secret:`.
For `line`, 4 spaces again then `secret: {{ symfony_secret }}`:

[[[ code('28e4f92bd3') ]]]

Don't forget to give this the `deploy` tag!

This *will* work... but don't even try it! Nope, we need to go further: having sensitive
keys committed to my `vars.yml` file is *not* a good solution. We need the vault.
