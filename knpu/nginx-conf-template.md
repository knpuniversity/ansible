# Nginx Conf Template

We just cloned the Symfony Standard Edition onto the server, but obviously NGINX is still just pointing at the generic page so we need to configure NGINX to point to our code, and also to proxy things to your PHP-FPM. Now, since our project is built in Symfony, I'm going to Google for Symfony NGINX. There's documentation about configuring a web server. Down below I'm going to find the NGINX section because it gives me a really nice, at least a minimum configuration to get started. Here's the trick; We need this configuration to go up onto our server and we obviously need to customize a few parts of it. Specifically, with Ubuntu, we need this to go into sites-available as a new file. How can we do that?

Well, Ansible does have ways to copy files locally up to a remote location, but in this case, there's actually a module that's even more perfect for this called the template module. Which you can see, it says "Templates a file out to remote server", which is a little bit unclear. What this basically does is it copies a file locally to the remote server, but it allows us to use a Jinja inside of it and that allows us to print variables. Super, super useful. Instead of the Ansible directory, I'm going to create a templates directory, and inside a Symphony.comf file and I'll paste those raw contents.

Now, a few things need to change in here. First of all, the server name needs to change to mootube.l, and we need to point the root to our actual correct root location.

This is actually correct by chance, but I don't want to leave that to chance. The top of our playbook, we're going to take advantage of variables yet again. Inside the new variable called server_name: mootube.l, and also a Symfony_web_dir, which is going to be set to the {{ symfony_root_dir }}/web. That's because the web directory inside the Symfony project is the document root, so that's actually where we want to point NGINX at.

Since we can use variables inside of templates, it now means that up here, we can change server name to {{ server_name }}, and the root to, same thing, {{ symfony_web_dir }}.

The only other thing that needs to change inside of here right now is down at the bottom. I'm going to change the word project here to {{ server_name }} in both the error and access logs, so that that ends up in the correct location.

Oh, and while we're here, if you notice you, you look up, everything references php5-fpm, so change that to php7.1-fpm.sock in those two places. Php-fpm should already be configured to run via a socket file at that location.

Next to actually tell Ansible to move this up to the server, we'll just use that template module. So now at the bottom we'll create a new task called "Add Symfony config template to the NGINX available sites". It's very simple. Of course need become: true, template, and then the two big options on template are going to be sourced ... Destination and source. So source is easy, point that to templates/symfony.com, and destination is also easy. "/etc/nginx/site-available/{{ server_name }}.conf" So we should end up with a mootube.l.conf inside that directory.

Once it's inside that directory, to actually enable it, we need to create a symbolic link from sites-enabled over to sites-available, and we already know even though we haven't done it for this use yet, but the files module is perfect for that. So below, we'll create a new thing that says, "Enable Symfony config template from NGINX available sites." We still need become: true, use the file module, in this case we'll say source, it's going to be the same thing that was the destination. And we want to point that to the same thing, except for sites-enabled.conf, and this time we're going to use state: link. Remember earlier when we wanted to create a directory, use state: directory, now we're going to use state: link.

The last thing I'm going to do here, which is optional, is I'm going to add a task called, "Add enabled NGINX site /etc/hosts ... What I'll do here is use the lineinfile ... to modify the /etc/hosts, to look for a line that starts with {{ server_name }} and replaces it with 127.0.0.1, and then the {{ server_name }}.

What that's going to do, is it's going to guarantee that we have an /etc/hosts entry for mootube.l. That points to our local 127.0.0.1 of course, on our virtual machine. That will just let us refer to mootube.l from the virtual machine, and it will point to itself. Of course, the first time this runs, it won't find a line in that file that matches the right expression, so it will add a line at the bottom.

Before you try this, make sure you have sites-available and sites-enabled, and flip over, and run your play book.

Awesome, no errors, so let's check them out. There's a couple of things we're looking for. First, that our files and sites available, perfect, we'll even look at it by catting that file. And awesome, it's got the correct root and server name, and in sites-enabled, we'll use ls-la for that. You can see that the symbolic link is there as well, and finally, the etc/hosts file has the local mootube.l.

So in theory then, we should be able to go over here and go to mootube.l and see our site. Instead, we still see the same NGINX configuration. You're probably screaming at me, "Why?". We just did lots of great NGINX configuration, but we have not yet restarted NGINX, which of course is easy to do right on the server, but we don't want to rely on running things manually. We need to configure our playbook to know when it should restart NGINX, and also php-fpm.

