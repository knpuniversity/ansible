# vars_prompt & Environment Variables

We missed a deploy step! We're not clearing Symfony's cache. That sounds like
a job for a new task! Call it "Clear Cache". It's easy: we just need to run a command:
`{{ symfony_console_path }} cache:clear --env=prod`

Easy! Except... that `--env=prod` part is interesting. *Sometimes* I might want
to deploy our app in the `prod` environment - like if we're deploying to production.
But other times, like if we're deploying to some test machine, I might want to deploy
our app in the `dev` environment.

What I'm saying is: I want that environment to be configurable. And actually,
this is important in *two* places: here, and when installing the Composer dependencies.

## Symfony Composer Deps in the prod Environment

Google for "Symfony deploy" to find a page on Symfony.com. Scroll down to a section
about installing and updating your vendors. Ah, so it recommends that when you deploy,
you use:

```terminal
composer install --no-dev
```

That's actually what the `composer` module tried to do by default. But then we -
via a `no_dev` option - told it to stop that nonsense! We *had* to do that because
some of the Composer post-install commands in a Symfony app *require* the packages
in the `require-dev` section.

In reality, this `--no-dev` flag is not a big deal. But, we *can* use it... as long
as we set an environment variable: `SYMFONY_ENV=prod`. Yep, those problematic post-install
commands are setup to look for this environment variable, and *not* to do certain
things rely on the `require-dev` dependencies.

So this is our mission: make the environment configurable, use it in the "Clear Cache"
task *and* set a new environment variable.

## Prompting for Input?

How? Start at the top: add a new `vars_prompt` key with `name` set to `symfony_env`.
Then, `prompt: Enter the environment for your Symfony app (prod|dev|test)`. As you're
probably guessing, Ansible will now *ask* us what environment we want to use. Set
a default value to `prod` and `private: no` - you can set that to yes to obscure
passwords as you type them.

Cool!

## Setting an Environment Variable

Next question: how can we use this variable to set an environment variable? How about...
an `environment` key! Yep, setting environment variables is a native task for
Ansible. Set `SYMFONY_ENV` to `{{ symfony_env|lower }}`. This uses the variable
we just set... but pipes it through a `lower` filter... just in case we get crazy
and use upper-case letters.

To see what this *all* looks like, at the top, let's debug some variables. First,
debug one called `ansible_env`. This is a built-in variable that has a lot of info
about the "host" environment - meaning, the machine (or machines) that you're running
Ansible against. It *should* also contain our environment variable.

Let's also debug the `symfony_env` variable that we set above.

Oh, and down on the "Clear Cache" task, I forgot to add the tag for `deploy`.

Change over to your terminal and run the playbook - but take off the `-t` option
so that *everything* runs:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Yes! *Right* at the start, it asks us for the environment. I'll leave it blank
to use `prod`. Then, hit ctrl+c to quit: we can already see the variables!

First, it printed `ansible_env`... which has some pretty cool stuff! It has a `HOME`
key for the home directory, `PWD` for the current directory and other goodies. AND,
it has `SYMFONY_ENV` set to `prod`. Not surprisingly, the `symfony_env` variable
also prints `prod`.

Try this again.. but be tricky... with an uppercase `PROD`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Yep! The environment variable was lowercased, but not the `symfony_env` variable.
That's no surprise... but if we want to guard against this, it *will* be a problem
in a minute when we try to use this in more places... like down on my "Clear Cache"
task.

We *could* keep using the `lower` filter. But, there's a cooler way: a "pre task".
