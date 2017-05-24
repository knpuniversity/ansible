# Using a 3rd Party to Install Redis

The *best* part about roles is they're *shareable*. There are a *ton* of third-party
roles that you can download to give your playbook free stuff! I love free stuff!

Refresh the page in the "dev" environment. The page took over *2* seconds to load!
Why? In `DefaultController`, our app is trying to use Redis:

[[[ code('87a41bb9b3') ]]]

But, it's not installed! So, this fails... and just for a good example, our code rescues
thing but sleeps for 2 seconds to "fake" the page being really slow:

[[[ code('9f83993b64') ]]]

In other words, without Redis, our site is slow. But after we install it, the page
should be super quick!

## Installing the Redis Role

We already have all the skills needed to install Redis. But... maybe someone already
did the work for us? Google for "Redis Ansible role". Hello `DavidWittman/ansible-redis`!
This role helps install Redis... and it looks fairly active. And check it out: it
looks like *our* role, with `templates`, `vars`, `handlers` and a few other things.

So... if we could download this into our project, we could activate the role and
get free stuff! The way to do that is by using a command called `ansible-galaxy`.
Copy it! Then, find your terminal and paste!

```terminal
ansible-galaxy install DavidWittman.redis
```

In my case, it's already installed.

By default, `ansible-galaxy` downloads *roles* to a global directory. When you tell
Ansible to load a role, it looks in your local directory but also looks in that
global spot to find possible roles.

You could also download the role *locally* in your project. Add `--help` to the
command:

```terminal
ansible-galaxy install DavidWittman.redis --help
```

The `-p` option is the key! Downloading the role locally *might* be even better than
downloading it globally. When it's in your project, you can commit it to your repository
and manage its version.

## Activate & Configure the Role

With the role downloaded, all we need to do is activate it! Easy! Copy the role name.
Under our `roles`, I'll use a longer syntax: `role: DavidWittman.redis` then
`become: true`:

[[[ code('834787a299') ]]]

If you tried the role, you'd find out you need that. We didn't need it for the `nginx`
role because we had the `become: true` lines internally.

Ok team, run the *entire* playbook in the `dev` environment:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

While we're waiting for someone else to install Redis for us - thanks David - head
back to the documentation. The main way to control how a role works is via variables,
like `redis_bind` in this example:

```yaml
---
- hosts: redis01.example.com
  vars:
    - redis_bind: 127.0.0.1
  roles:
    - DavidWittman.redis
```

It's cool how it works: internally, the role sets some variables and then uses them.
We, of course, can override them. Simple, but powerful.

There *is* one downside to third-party roles: they can add some serious bloat to
your playbook. Yea, it's running a *lot* of tasks. Often, a role is designed to work
on multiple operating systems and versions. Some of these tasks are determining facts
about the environment, like what Ubuntu version we have or what utilities are available.
The role is really flexible, but takes extra time to run.

And, this first execution will take a *long* time: it's installing Redis!

Finally... it gets into our stuff.

Ding! Let's try it! Refresh MooTube. Then, refresh again. Yes! 29 milliseconds!
Amazing! So much faster!

This works because our application is already configured to look for Redis on localhost.
So as soon as it was installed, our app picked it up.

Next, let's talk more about configuration and how you might control things like
the Redis host or database password.
