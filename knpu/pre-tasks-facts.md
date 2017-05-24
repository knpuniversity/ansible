# pre_tasks and set_fact

I want to *guarantee* that the `symfony_env` variable is *always* lowercased so that
I can safely use. A cool way to do this is with a "pre task".

Add a new key called... well... `pre_tasks` and a new task called "Convert entered Symfony
environment to lowercase":

[[[ code('4733a149f5') ]]]

Pre-tasks are exactly like tasks... they just run *first*.

Wait... then what the heck is the difference between a "pre task" and just putting
the task at the top of the `task` section?

Nothing! Well, nothing yet. But later, when we talk about *roles* - ooh roles are
fancy - there *will* be a difference: pre tasks run before roles.

Anywho, in this pre task, we basically want to re-set the `symfony_env` variable
to a lowercased version of itself. To do that, we'll use a new module: `set_fact`.
We already know that we can set variables in 3 different ways: with `vars`, `vars_prompt`
or by using the `register` key below a task. The `set_fact` module is yet *another*
way.

## Facts versus Variables

But wait... why `set_fact` and not `set_variable`? So... here's the deal: you'll
hear the words "facts" and "variables" in Ansible... basically interchangeably. Both
facts and variables can be set, and are referenced in exactly the same way. And while
there *do* seem to be some subtle differences between the two, if you think of a
fact and a variable as the same thing, it'll make your life easier. When you run
the playbook, the first task is called "setup", and it prepares some *facts* about
the host machine. That's usually where you hear the word facts: it's info about each
host.

So, we're using `set_fact`... to set a fact... or a variable... Set `symfony_env`
to `{{ symfony_env|lower }}`:

[[[ code('0b8961c762') ]]]

Love it! Oh, and just like with `tasks`, these can be tagged. We'll probably want
this to run *all* the time... so let's use a special tag called `always`:

[[[ code('f8af0d4fc2') ]]]

Try the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Be difficult and use "PROD" in all capital letters again.

Ha, ha! This time, our playbook outsmarts us and lowercases the value.

## Environment Variables and Pre Tasks

Remove the two debug tasks:

[[[ code('6b001f0cd2') ]]]

Can we also remove the `|lower` from the environment variable? Actually... no!
The `environment` section runs *before* tasks, even `pre_tasks`. And this has nothing
to do with the order we have things in this file - `environment` always runs first.
So, keep the `|lower`.

## Using the symfony_env Variable

Ok, time to use our fancy `symfony_env` variable! First, when we install the composer
dependencies, we currently have `no_dev: no`. But now, if the environment is `prod`,
this can be `yes`. Let's use a fancy expression! `{{ 'yes' if (prod == symfony_env) else 'no' }}`:

[[[ code('26fc672f52') ]]]

Don't forget your curly braces. Weird, but cool! This is a special Jinja syntax.

## Conditionally Running Tasks

Next, find the fixtures task. Hmm. If we're deploying in the `prod` environment...
we might not want to load the data fixtures at *all*. But so far, we don't have any
way to *conditionally* run a task: tasks *always* run.

Well guess what? We *can* tell a task to *not* run with the `when` key. In this case,
say `when: 'symfony_env != "prod"`:

[[[ code('035e84b807') ]]]

Finally, down in the `Clear cache` task, instead of `prod`, use `{{ symfony_env }}`:

[[[ code('bbaa3d09fb') ]]]

Let's try this thing! Re-run the playbook, but use `-t deploy`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Use the `prod` environment. As we just saw, the `vars_prompt` runs *always*: it
doesn't need a tag:

[[[ code('0936f8a0c4') ]]]

Then, our "pre task" should run thanks to the `always` tag:

[[[ code('9bb91f7a34') ]]]

By the time the "Composer Install" task executes, it should run with `no_dev: yes`,
and then hopefully it'll skip data fixtures and change "Clear cache":

[[[ code('50c7ca80de') ]]]

The "Install Composer's dependencies" *does* show as *changed*: that's a good sign:
it should have installed less packages than before. And yea! It's skipping the fixtures!

In the VM, try to `ls vendor/sensio`.  Ok cool! One of the `require-dev` dependencies
is `sensio/generator-bundle`. That is *not* here, proving that the dev dependencies
did NOT install this time. We are in business!

And before we continue, under the "Clear Cache" task, add `changed_when: false`:

[[[ code('6fba45983b') ]]]

That's not critical, it'll just prevent it from showing up as changed on every run.

Now, let's create a faster, smarter playbook by skipping some redundant tasks!
