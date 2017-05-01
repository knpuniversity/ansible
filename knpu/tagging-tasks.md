# Tagging Tasks

Sometimes - *especially* when debugging - you just want to run only *some* of your
playbook. Because... our playbook is getting so awesome... that, honestly, it takes
some serious time to run!

For example, in my VM, I'm going to change the permissions on the `var/` directory:

```terminal
sudo chmod -R 555 var/
```

Definitely use `sudo`. Then, I'll remove the cache files:

```terminal
sudo rm -rf var/cache*
```

If you try the page now, it explodes! Ok, I don't expect my permissions to suddenly
change like this under normal conditions. But, suppose that we had *just* hit this
permission error for the first time and then added the "Fix var permissions" task.
In that case, we would know that re-running the *entire* playbook should fix things.

But... couldn't we run *just* this *one* task? Yep! And a *great* way to do that
is via *tags*.

Below the task, add `tags`, and then `permissions`. Now, from the command line,
tell Ansible to *only* execute tasks with this tag: `-t permissions`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t permissions
```

It still goes through its setup but then... yep! Only one task! Refresh the page.
Permissions fixed!

## Tagging for Deployment

Here's another example. Right now, our playbook has tasks for two separate jobs.
Some tasks setup the server - making sure PHP, Nginx and other stuff is installed
and configured. But others are really more about code deployment: making sure
the project directory exists, cloning the code, installing composer dependencies,
and setting up the database.

In the future - when we make changes to the code,- we might want to *just* deploy
that code... without going through all the server setup tasks. Let's add a new
tag - `deploy` - to every step involved in deployment. See the task that creates
the project directory? Yep, give it the `deploy` tag. Add it to "Checkout Git Repository"
and also to the three tasks that install Composer. Actually, this is debatable:
you might consider Composer as a "Server setup" task, not deployment. It's up to you.

Keep going! I'll add the task to everything that I want to run for *each* code update.
It's not an exact science.

Let's see if it works! In the virtual machine, I'm going to manually edit a file:

```terminal
vim app/Resources/views/default/index.html.twig
```

Let's add a few exclamation points to be *really* excited. Then hit escape, `:wq`
to save. In the browser, that won't show up immediately - because we're in Symfony's
`prod` environment. But if you add `app_dev.php` to the URL... yep! "Filter by Tag!".

By the way, going to `app_dev.php` only works because I've already modified some
security logic in that file to allow me to access it.

Ok, back in our local machine, run the playbook... this time with `-t deploy`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

Oh, much, much faster! Try the browser! Code deployed! You can also use `--skip-tags`
if you want to get crazy and do the opposite.

Next, let's talk about how we can "fix" the fact that some tasks say "Changed" *every*
time we run them. Eventually, this will help us speed up our playbook.
