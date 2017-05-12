# Idempotency, changed_when & Facts

I just ran the playbook with the `deploy` tag:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Notice that several tasks say "Changed"... but that's a lie! The first two
are related to Composer - we'll talk about those later. Right now, I want to focus
on the last 4: fixing the directory permissions and the 3 `bin/console` commands:

[[[ code('d5de537e83') ]]]

## Changed and Idempotency

But first... why do we care? I mean, sure, it says "Changed" when nothing *really*
changed... but who cares? First, let me give you a fuzzy, philosophical reason.
Tasks are meant to be *idempotent*... which is a hipster tech word to mean that it
should be safe to run a task over and over again without any side effects.

And in reality, our tasks *are* idempotent. If we run this "Fix var directory permissions"
task over and over and over again... that's fine! Nothing weird will happen. It's
simply that the tasks are *reporting* that something is changing each time... when
really... it's not!

I know, I know... this seems like *such* a silly detail. But soon, we're going to
start making decision in our playbook *based* on whether or not a task reports
as "changed".

Actually, this is already happening:

[[[ code('40e8cd2a54') ]]]

The "Restart Nginx" handler is *only* called when this task changes:

[[[ code('07de4b0390') ]]]

So, as a best practice - as much as we can - we want our tasks to correctly report
whether or not they changed.

## Using changed_when: false

How do we fix this? Well, the first task - fixing var directory permissions - is
a little surprising. This is a core module... so, shouldn't it be correctly reporting
whether or not the permissions *actually* changed? Well yes... but when you set
`recurse` to `yes`, it *always* says changed:

[[[ code('71a2ea4a41') ]]]

The easiest way to fix this is to add `changed_when` set to `false`:

[[[ code('a827d993c8') ]]]

That's not *perfect*, but it's fine here:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

## Dynamic changed_when

But what about the other tasks... like creating the database?

[[[ code('369772148f') ]]]

Technically, the first time we run this, it *will* create the database. But each
time after, it does nothing! If we want this task to be smart, we need to *detect*
whether it did or did *not* create the database.

And there's a really cool way to do that. First, add a `register` key under the
task set to `db_create_result`:

[[[ code('a73583ad26') ]]]

This will create a new variable containing info about the task, including its output.
This is called a *fact*, because we're collecting facts about the system.

To see what it looks like, below this, temporarily add a `debug` task:

[[[ code('316ab2c27b') ]]]

This is a shorthand way of using the `debug` module. Add `var: db_create_result`
to print that. Oh, and below, give it the `deploy` tag:

[[[ code('4012cb3662') ]]]

Ok, try it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Whoa, awesome! Check this out. That variable shows when the task started, when it
ended *and* most importantly, its output! It says:

> Database `symfony` for connection named default already exists. Skipped.

Ah, ha! Copy the "already exists. Skipped" part. We can use this in our playbook
to know whether or not the task did anything.

How? Instead of saying `changed_when: false`, use an expression:
`not {{ db_create_result.stdout }}` - `stdout` is the key in the variable - 
`not {{ db_create_result.stdout|search('already exists. Skipped') }}`:

[[[ code('b81e0036d8') ]]]

If you use Twig, this will look familiar: we're reading a variable and piping it
through some `search` filter, which comes from Jinja.

For the migration task, we can do the same. Register the variable first: `db_migrations_result`.
Copy the `changed_when` and paste that below:

[[[ code('3c4b60deb6') ]]]

So what language happens when there are *no* migrations to execute? Go to your virtual
machine and run the migrations to find out:

```terminal
./bin/console doctrine:migrations:migrate --no-interaction
```

Yes! It says:

> No migrations to execute

That's the key! Copy that language. Now, the same as before: paste that into the
expression and update the variable name to `db_migration_results`:

[[[ code('3c4b60deb6') ]]]

Awesome! Finally, the last task loads the fixtures. This is tricky because... technically,
this task fully empties the database and re-adds the fixture each time. Because
of that, you could say this is *always* changing something on the server.

So, you can let this say "changed" or set `changed_when: false` if you want all
your tasks to show up as not changed. Unless we start relying on the changed state
of this task to trigger other actions, it doesn't really matter.

Moment of truth: let's head to our terminal and try the playbook:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Yes! The last 4 tasks are *all* green: not changed. Now, let's do something *totally*
crazy - like run `doctrine:database:drop --force` on the virtual machine:

```terminal
./bin/console doctrine:database:drop --force
```

Try the playbook now: we *should* see some changes. Yes! Both the database create
task and migrations show up as changed.

Ok, let's do more with facts by introducing environment variables.
