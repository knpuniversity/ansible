# Installing Composer Deps

Ok, let's install our Composer dependencies already! Go back to the Ansible `composer`
module for reference. Then, find your playbook and add a new task with a poetic
and flowery name: "Install Composer's dependencies":

[[[ code('70022f94fc') ]]]

Ok, boring name, but clear! Use the `composer` module, and set the one required
option - `working_dir` - to `{{ symfony_root_dir }}`:

[[[ code('9861a890c4') ]]]

Hey, that variable is coming in handy!

Run that playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

It's running... running, installing Composer's dependencies and... explosion! Ah!
So much red! Run!

Then... come back. Let's see what's going on. It *looks* like it was downloading
stuff... if we move to `/var/www/project` on the VM and `ls vendor/`, yep, it *was*
populated.

The problem was later - when one of Symfony's post-install tasks ran:

> Fatal error: Uncaught exception, SensioGeneratorBundle does not exist.

Oh yea. By default, the `composer` module runs composer like this:

```terminal
composer install --no-dev
```

This means that your `require-dev` dependencies from `composer.json` are *not*
installed:

[[[ code('374f67d11a') ]]]

If you're deploying to production, you may want that: it gives you a slight
performance boost. But in a Symfony 3 application, it makes things blow up!
You *can* fix this by setting an environment variable... and we *will* do that
later.

But, since this is a development machine, we probably *do* want the dev dependencies.
To fix that, in the playbook, set `no_dev` to `no`:

[[[ code('42457eba30') ]]]

Try the playbook now.

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

This time, I personally guarantee it'll work. In fact, I'm *so* confident, that if
it doesn't work this time, I'll buy you a beer or your drink of choice if we meet
in person. Yep, it's *definitely* going to work - I've never been so sure of anything
in my entire life.

Ah! No! It blew up again! Find the culprit!

> Attempted to load class "DOMDocument" from the global namespace.

Uh oh. I skipped past something I shouldn't have. When you download a new Symfony
project, you can make sure your system is setup by running:

```terminal
php bin/symfony_requirements
```

> Your system is not ready to run Symfony projects.

Duh! The message - about the SimpleXML extension - means that we're missing an extension!
In our playbook, find the task where we install PHP. Add another extension: `php7.1-xml`:

[[[ code('ad93085d5a') ]]]

Run that playbook - hopefully - one last time:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Ya know, this is the *great* thing about Ansible. Sure, we might have forgotten to
install an extension. But instead of installing it manually and forgetting all about
it next time, it now lives permanently in our playbook. We'll never forget it again.

Phew! It worked! Go back to the VM and check out requirements again:

```terminal
php bin/symfony_requirements
```

We're good! And most importantly, we can boot up our Symfony app via the console:

```terminal
php bin/console
```

Our app is working! And there's just one last big step to get things running:
configure NGINX with PHP-FPM and point it at our project. Let's go!
