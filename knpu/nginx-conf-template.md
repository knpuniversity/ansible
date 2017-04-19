# Nginx Configuration & Ansible Templates

Nginx is still using its generic, boring, but very polite default HTML page. You're
welcome for using you Nginx!

It's time to point Nginx at *our* code! Google for "Symfony Nginx" to find a documentation
page about [Configuring a Web Server](http://symfony.com/doc/current/setup/web_server_configuration.html).

Scroll down to the Nginx section: it has a great block of Nginx config that'll work
with our app. Here's the trick: we somehow need to put this configuration up onto
the server *and* customize it. How can we do that?

Ansible *does* have a module for copying files from your local machine to the remote
host. But in this case, there's a different module that's even *more* perfect:
the `template` module. It says:

> Templates a file out to a remote server

In normal-person English, this means that it executes a file through Jinja - allowing
us to print dynamic variables - and *then* copies it up to the remote server. I
*love* this module.

Create a `templates/` directory and inside, a `symfony.conf` file. Paste in the
raw Nginx configuration.

Now, a few things need to change in here. Like, `server_name` should be `mootube.l`
and `root` should point to the correct directory. Actually, `root` is already correct
by chance... but we can do better!

## Variables in your Template

All variables available in your playbook are available in a template. So at the
top of our playbook, let's add a few more: `server_name: mootube.l` and
`symfony_web_dir` set to `{{ symfony_root_dir }}/web`. The `web` directory is our
document root.

Let's put those variables to work! First, use `{{ server_name }}` and then set the
root to `{{ symfony_web_dir }}`.

At the bottom, tweak the logs paths - instead of the word `project`, use `{{ server_name }}`.
Do it for the `access_log` too.

Oh, and while we're here, see those `php5-fpm` references? Change them to `php7.1-fpm.sock`
in both places. FPM will already be configured to put its sock file here.

## Using the template Module

Let's add our task to use the template: "Add Symfony config template to the Nginx
available sites". Add `become: true` and use the `template` module. The two important
options are `dest` and `src`. Set `src` to, well `templates/symfony.conf`, and `dest`
to `/etc/nginx/sites-available/{{ server_name }}.conf`.

## Enabling the Nginx Site

Cool! Once it's in that directory, we need to enable it... which means we need to
create a symbolic link from `sites-enabled` to that file in `sites-available`.
And we already know the *perfect* module for this: `file`!

Add the new task: "Enable Symfony config template from Nginx available sites".
We still need `become: true` and use the `file` module. This time, for `src`, copy
the `sites-available` line from `dest` above. For `dest`, just change it to `sites-enabled`.
To create the symbolic link, use `state: link`. Earlier we created a directory with
`state: directory`.

## Updating /etc/hosts

The *last* thing I'll do - which is optional - is to add a task named
"Add enabled Nginx site /etc/hosts". I'll use `lineinfile` to modify `/etc/hosts`
and look for a line that contains `{{ server_name }}`. Then, I'll replace it with
`127.0.0.1 {{ server_name }}`.

This will guarantee that we have a `/etc/hosts` entry for `mootube.l` that points
to `127.0.0.1` inside the VM. It's not a big deal - but it'll let us refer to `mootube.l`
in the VM.

Of course, the first time this runs, it will *not* find a line in that file that matches
`mootube.l`. But that's no problem: `lineinfile` will guarantee that the `line`
value exists by adding it at the bottom.

Before you try this, make sure you have `sites-available` and `sites-enabled`. Ok,
run it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Woohoo! No errors! Let's check it out! First, the file *does* live in `sites-available`.
And yea, awesome! The variables inside worked. Our `sites-enabled` directory *does*
have the link, and `/etc/hosts` has our new `mootube.l` line at the bottom.

Wow, we just killed it!

So, in theory, we should be able to go to our browser and refresh `http://mootube.l`
to see our site. Refresh! Um.... we still see the same, boring - but polite - Nginx
Configuration page.

Why? Some of you are probably screaming the answer at your computer... and also
scaring your co-workers. It's because, we have *not* restarted or reloaded Nginx.
Yea, this is easy to do manually - but come on! We need to teach our playbook to
know when Nginx - and also PHP FPM - need to be restarted. We can do that with...
handlers!
