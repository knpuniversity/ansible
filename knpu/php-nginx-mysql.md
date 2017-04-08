# PHP 7, Nginx & MySQL

Our app is written in PHP... so... we should probably get that installed. Copy the
`git ` block, paste it, and change it to `php5-cli`. Run that playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

And yea... I know PHP 5 is *ancient*. But our Ubuntu distribution doesn't support
version 7. But don't worry, we're going to upgrade to 7 in a minute by using a custom
repository. It's going to be awesome - so stay with me.

While the playbook is running, change over to your terminal tab and make sure you're
still SSH'ed into the VM. Of course, if we try `php -v` right now... it doesn't
work. But as *soon* as ansible finishes, try it again:

```terminal
php -v
```

Yea! Version 5.5.9. Now, let's kill this ancient version of PHP and go to 7.

## Using a Custom apt Repository

Here's the deal: if you research how to install PHP 7 on this version of Ubuntu,
you'll learn about a third-party repository called `ppa:ondrej/php`. If we can add
this to our apt *sources* - usually done by running a few commands - we'll be in
business.

And of course... there's a module for that! It's not `apt`, it's `apt_repository`.
It doesn't have any requirements, and the options look pretty easy - just set
`repository` to the one we want to use.

Let's do it! In the playbook, above the PHP task, add a new one: Add PHP 7 Personal
Package Archive repository. Use the `apt_repository` module and set repository to:
`ppa:ondrej/php`.

And *now*, we can install `php7.1-cli`. Run it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Love it... no errors, and 2 tasks changed. Over in the VM, try:

```terminal
php -v
```

Oh, sweet PHP 7.1 goodness.

## Install Nginx

We're on a role - so let's take care of installing a few more things, like Nginx!
Add the new task: Install Nginx web server. I'm putting this above the PHP install,
but it doesn't matter. Add the normal `become: true`, `apt` and install
`nginx` with `state: latest`.

Run it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

And by the way, in the real world... where you can't fast-forward through Ansible,
running the playbook takes awhile. So, you might want to add multiple tasks before
testing things.

Ok, worked again! In theory, Nginx is now installed and running! Switch over to
the VM and try to hit our server:

```terminal
curl localhost
```

Hey hey! We are rocking! Sure, we still need to add a lot of Nginx configuration,
but it *is* running.

*And* we should be able to see this page from our host machine. Find your browser and
go to `http://192.168.33.10`. Hey again Nginx!

Now eventually, we're going to access the site via `mootube.l` from our host machine.
To handle that, head to your terminal on your *main* machine - so not the VM - and
edit your `/etc/hosts` file. Inside, anywhere, add `192.168.33.10 mootube.l`. Save
that!

Back at the browser test it: `http://mootube.l`. Got it!

## Install MySQL

Before we move on, let's check one more thing off our list: MySQL. Copy the Nginx
configuration to Install MySQL DB Server. Set it to the `mysql-server` package.

Run Ansible again:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Now, in a bigger infrastructure, you might want to keep your database on a separate
server, or host, in Ansible language.

As we've seen from our playbook, each *play* is for a specific host. And there's
nothing stopping us from having one host for our web server, with Nginx and PHP,
and another host for our database, where we install the MySQL server. But for our
example, we'll keep them all together.

Ok! Now MySQL should be installed. Move to your VM terminal tab, and try to connect
to the local server:

```terminal
mysql -u root
```

And we are in! Type exit to get out.

We've got Nginx, MySQL and PHP installed. But, PHP isn't ready yet... we're missing
a bunch of PHP extension and our `php.ini` file needs some changes. Let's crush that!
