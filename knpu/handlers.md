# Handlers: For Handling Serious Biz

Nginx *is* setup! But we need to restart or at least reload Nginx for our site
to, ya know, *actually* work. Surprise! There's a module for that... called `service`.
This module is all business: give it the `name` of the service and the `state` you
want, like `started` or `restarted`.

So we should add a new task to restart Nginx, right? Wait, no! Ansible has a different
option... a more *awesome* option.

## Hello Handlers

At the bottom of the playbook, add a new section called `handlers`. A handler is
written *just* like any task: give it a name - "Restart Nginx" - use `become: true`,
and then choose the module we want: `service`. Set the name to `nginx` and `state`
to `restarted`.

**TIP
We could also just reload Nginx - that's enough for most changes.
***

Here's the deal with handlers: unlike tasks, they are *not* automatically called.
Nope, instead, you find a task - like the task where we create the symbolic link
to `sites-enabled` and, at the bottom, add `notify: Restart Nginx`.

Now, when this task runs, it will "notify" the "Restart Nginx" handler so that it
will execute. Actually, that's kind of a lie - but just go with it for now.

There are a few reasons why this is better as a handler than a task. First, handlers
run *after* all of the tasks, and if multiple tasks notify the same handler, that handler
only runs once. Cool! I'll mention a second advantage to handlers in a minute.

Change over to our local machine's terminal and run the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

We're expecting Ansible to execute all of the tasks... and *then* call the handler
at the end. Wait! There's nothing at the end: it did *not* call the handler!

To prove it, refresh the browser: yep, we're still staring at the boring Nginx test
page.

## Handler and Changed State

So... this didn't work... and this is the *second* reason why handlers are great!
A handler *only* runs if the task that's notifying it *changed*. If you look at the
output, not surprisingly, almost every task says "Ok"... which means that they did
*not* make a change to the server. The only two that *did* change are related to Composer...
and honestly... those aren't really changing the server either. They just aren't
smart enough - *yet* - to correctly report that they have not made a change.

The important one in our case is "Enable Symfony Config". The symbolic link was
already there, so it did *not* change and so it did *not* notify the handler.

So let's delete that link and see what happens! In the VM, run:

```terminal
sudo rm /etc/nginx/sites-enabled/mootube.l.conf
```

Try the playbook now!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Watch for the "changed" state... got it! And... yes! Running Handlers: Restart Nginx.
*Now* try your browser. Woh! Ok, 502 bad gateway. Ya know, I'm going to say that's
progress: Nginx *did* restart... but now something else is busted! Before we put
on our fancy debuggin' hat, let's add the "Restart Nginx" notify everywhere else
it's needed.

For example, if we decide to change the template, we need Nginx to restart, or reload
if you prefer. Up further, when we *first* install Nginx, if this changes because
of a new Nginx version, we also want to restart.

## Restarting PHP-FPM

The *other* thing we might need to restart is PHP-FPM, like when we update `php.ini`.
At the bottom, copy the handler and make a new one called "Restart PHP-FPM". Then,
just replace the name - `nginx` with `php7.1-fpm`.

Copy the name of the handler.

We *definitely* need to run this if any PHP extensions are installed or updated.
And also if we update `php.ini`.

Beautiful! Since we have *not* restarted php-fpm yet, I'll go to my VM so we can
make one of these tasks change. Open `php.ini`:

```terminal
sudo vim /etc/php/7.1/fpm/php.ini
```

I'll search for `timezone` and set this back to an empty string. Now, re-run the
playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Watch for it.... yes! Restart PHP-FPM.

Let's try it! Refresh the browser! Bah! Still a 502 bad gateway!? That's bad news.
Debuggin' time! In the VM, tail the log:

```terminal
tail /var/log/nginx/mootube.l_error.log
```

Ah ha! It doesn't see our socket file! That's because I messed up! The *true* socket
path is `/var/run/php/php7.1-fpm.sock`.

Easy fix: in `symfony.conf`, add `/php` in both places. Start up the playbook again!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

This is a *perfect* example of why handlers are so cool! Since the template task
is "changed", Nginx should be restarted. That's *exactly* how it should work.

Try the browser now! Ah, 500 error! Again, I'm counting that as progress!

Tail the log once more:

```terminal
tail /var/log/nginx/mootube.l_error.log
```

Ah, so Symfony is unable to create the `cache` directory. That's an easy... but
interesting fix. Let's do it next.
