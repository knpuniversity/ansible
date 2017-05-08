# Organizing into Roles

Using `include` isn't the only way to organize your playbook. In fact, the *best*
way is with roles... which are a *really* important concept in Ansible.

If you think of a package of functionality - like bootstrapping Symfony, or getting
Nginx set up - it involves a number of things. In the case of Nginx, sure, we definitely
need to run some tasks. But we also need to set a variable that's used by those
tasks *and* register the "Restart Nginx" handler!

So a collection of functionality is more than just tasks: it's tasks, variables,
and sometimes other things like handlers. A role is a way to organize all of that
into a pre-defined directory structure so that Ansible can automatically discover
everything.

## Creating the Role

Let's turn *all* of our Nginx setup into an Nginx role. In the `ansible/` directory,
create a new directory called `roles`... and inside that, another directory called
`nginx`.

In a moment, we're going to point our playbook at this directory. When we do that,
Ansible will automatically discover the tasks, variables, handlers and other things
that live inside of it. This will work because roles *must* have a very specific
structure.

## Tasks in the Role

First, we know that a few tasks need to live in the role. So, create a directory
called `tasks` and inside, a new file called `main.yml`. Start with the `---`.

Below - *just* like with `symfony-bootstrap.yml` - we add tasks. In the playbook,
search for "Install Nginx web server". We need that! Move it into `main.yml`. Let's
copy a few others: like "Add Symfony config template to the Nginx available sites"
and the two tasks below it. Move those to the role. Then, select everything and
un-indent them.

Beautiful!

## Role templates

Okay, what else does this role need? Well, this task refers to `templates/symfony.conf`...
which lives at the root of the `ansible/` directory. Drag the `templates/` directory
into the role.

## Role Variables

The tasks also use a variable - `server_name`. This is set at the top of our playbook,
but it's only used by the Nginx tasks. Let's move it into the role.

This time, create a `vars/` directory and - once again - a `main.yml` file.

Remove the variable from `playbook.yml` and paste it here.

Notice that it's *not* under the `vars` keyword anymore: Ansible knows its a variable
because it's in the `vars/` directory.

## Role Handlers

Finally, if you look back at the tasks, the last thing they reference is the
"Restart Nginx" handler. Go find that at the bottom of our playbook. Copy it, remove
it, then create - surprise! - a `handlers` directory with a `main.yml` file inside.
Put the 3 dashes, paste it and un-indent!

## Using the Role

Phew! This is the file structure for a role, and as long as you follow it, Ansible
will take care of including and processing everything. All *we* need to do is activate
the role in our playbook. At the top, add `roles`, then `- nginx`.

That's it! Time to try it out. Run the *entire* playbook:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Deploy to the `dev` environment this time - I'll show you why in a minute. Hey,
it looks good! The Nginx stuff happens *immediately*.

Looking good... looking good... woh! The Nginx stuff was happy, but we have a huge
error at the end. Go back to your browser and load the site with `http://mootube.l/app_dev.php`.
This is that same error!

## Don't be too Smart

What's going on? Well, I made our playbook too smart and it became self-aware. Ok,
not quite - but we do have a mistake... and it's unrelated to roles. Inside
`symfony-bootstrap.yml`, we only install composer dependencies when `code_changed`.
Well, remember that the composer dependencies are a little different for the `dev`
environment versus the `prod` environment. The last time I ran Ansible I used the
`prod` environment. This time I used `dev`... but the task that should have installed
the dependencies for the `dev` environment was skipped!

Shame on me! Find your balance between speed and being too clever. I'll comment
out the `when`. Run the playbook again in the `dev` environment:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

This time "Install Composer dependencies" is marked as "changed" because it *did*
download the `dev` dependencies. And the page works!

## Role and Task Ordering

Go back to your terminal and scroll up a little. Ah, there's one slight problem:
Nginx was installed *before* updating the apt repositories cache. That means that
if there's a new version of Nginx, it might install the old one first. We didn't
intend for them to run in this order!

In fact, only *one* thing ran before Nginx - other than the `setup` task - our
pre-task! In the playbook, I've put `pre_tasks` first, then `roles` and then `tasks`.
And that's the order they run it. But it's not because of how I ordered them in
my YAML file: Ansible always executes `pre_tasks`, then `roles` then `tasks`.

So how can we update the apt repository cache first? Just move those two tasks
into `pre_tasks`.

Done! Next, let's download a third-party role for free playbook functionality!
