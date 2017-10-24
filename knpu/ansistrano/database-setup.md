# Database Setup

Our homepage is *busted*. Find your terminal and SSH onto the server:

```terminal-silent
ssh -i ~/.ssh/KnpU-Tutorial.pem ubuntu@54.XXX.XX.XXX
```

Let's find out what the problem is. I recognize the error page as one that comes
from *Symfony*... which means Symfony *is* running, and the error will live in *its*
logs.

Check out the `var/logs/prod.log` file:

```terminal-silent
cd /var/www/project/current
tail vars/logs/prod.log
```

Ah, no surprise!

> Unknown database "mootube"

So how do we setup the database? Well, it's up to you! This is just a one-time
thing... so it doesn't need to be part of your deploy. You could go directly to MySQL
and setup it up manually. That's super fine.

## Creating the Database

I'm going to do it in Ansible! Add a new task... but move it above the cache tasks,
in case the database is needed during cache warm up. Name it: "Create DB if not exists":

[[[ code('eaff766703') ]]]

We can use the `doctrine:database:create` command. So, use the `command` module...
and I'll copy one of our other commands. Change it to `doctrine:database:create`
then `--if-not-exists` so it won't explode if the database already exists:

[[[ code('f2fb6ac677') ]]]

If you run this command locally... well... we *do* have a database already.
So it says:

> ... already exists. Skipped.

Copy that text. This last part is optional: we're going to configure this task
to know when it was, or was not *changed*. Register a variable called `create_db_output`.
Then, add `changed_when` set to `not create_db_output.stdout|search()` and
paste that text:

[[[ code('4682f8c9b1') ]]]

## Migrating / Creating the Schema

That'll give us a database. But... we need some schema! Some tables! How do we
get those!? Well... you *should* use migrations in your app. We *do* have migrations:
in `app/DoctrineMigrations`... and these contain *everything*. I mean, these have
all the queries needed to add all the tables... starting from an empty database.
I *highly* recommend creating migrations that can build from scratch like this.

So, to build the schema - or migrate any *new* schema changes on future deploys -
we just need to run our migrations.

Create a new task: "Run migrations". Then cheat and copy the previous task. This
is simple enough: run `doctrine:migrations:migrate` with `--no-interaction`, so
that it won't interactively ask us to confirm before running the migrations.
Interactive prompts are *no* fun for an automated deploy:

[[[ code('c70c9029b7') ]]]

Register another variable - `run_migrations_output` - and use that below:

[[[ code('853a4274c2') ]]]

If you try to migrate and you are already fully migrated, it says:

> No migrations to execute.

Let's search for that text: "No migrations to execute":

[[[ code('8f8cc932bd') ]]]

Oh, before we try this, make sure you don't have any typos: the variable is
`create_db_output`:

[[[ code('56f548dd61') ]]]

Ok, try it!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

After a bunch of setup tasks... if you watch closely... yea! The migrations ran
successfully! We *should* have a database full of tables.

Go back to the site and refresh! It works! Of course... there's no *data*, but it
works!

## Why dev Commands Don't Work

To help bootstrap my data, *just* this once, I'm going to load my fixtures on production.
I'm obviously *not* going to make this part of my deploy: you won't make any friends
if you constantly empty the production database. Believe me.

Find the terminal that is SSH'ed to the server. Move out of the `current/` directory
and then back in:

```terminal-silent
cd ..
cd current/
```

First, try running `bin/console` *without* `--env=prod`:

```terminal-silent
bin/console
```

Error! It can't find a bundle! Why? In the `dev` environment, we use a few bundles -
like `HautelookAliceBundle` - that are in the `require-dev` section of our `composer.json`.
So, these do *not* exist inside `vendor/` right now!

*That* is why you *must* run all commands with `--env=prod`. But, of course, the
fixtures bundle is *only* available in the `dev` environment. So, *just* this one
time... manually... let's install the dev dependencies with:

```terminal
composer install
```

*Now* we can load our fixtures:

```terminal
./bin/console hautelook_alice:doctrine:fixtures:load
```

Beautiful! And *now*, we've got some great data to get us started. Next, let's
talk more about migrations... because if you're not careful, you may temporarily
take your site down! That's not as bad as emptying the production database, but
it still ain't great.
