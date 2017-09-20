## VirtualHost Setup

There is one more immediate problem: the document root of the project is
`/var/www/project/current/web`. But... you might rememebr that our Nginx virtual
host points to  `/var/www/project/web`. This needs to change to
`/var/www/project/current/web`.

That's easy to do: we could just edit this file right now! But since we provisioned
our server with Ansible, let's make this change to our provision playbook... so that
we feel super cool and responsible.

## Sharing Provision Variables

First, in `deploy.yml`, add a new `vars_files` key. Load a file called `vars/vars.yml`.
This *very* small file holds two variables that point to where the project lives.
These are used by the provision playbook: `playbook.yml`. The first tells it where
to create the directory. And the second - `server_document_root` - is used to set
the document `root` in the Nginx virtual host!

Before we chnage that variable, go back to `deploy.yml`. Now that we're including
`vars.yml` here, we can use the `project_deploy_dir` variable.

This doesn't change anything: it just kills some duplication.

## Updating the Document Root

Back in `vars.yml`, we need to change `server_document_root`. But hold on! Let's
get fancy! Ansistrano has a variable called `ansistrano_current_dir`. This is the
*name* of the symlinked directory and - as we know - it defaults to `current`. Put
this inside `vars.yml` and set it to `current`. This won't change how Ansistrano works.
But now, we can safely *use* that variable here. Set `server_document_root` to
`"{{ project_deploy_dir }}/{{ ansistrano_current_dir }}/web"`.

I love it! After all these changes, well, we didn't *actually* change our deploy
playbook at all. But we *did* change the provision playbook.

Find your local terminal and re-provision the server:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -l aws
```

This will take a few minutes to finish... but all it's *really* doing is changing
the virtual host to point to `/var/www/project/current/web`. If you're not using
Ansible to provision, change this however you want!

Done! Move back to your server and open the Nginx config!

```terminal-silent
sudo vim /etc/nginx/sites-available/mootube.example.com.conf
```

Got it! The `root` is set to the correct spot.

## Try out the Site

The ultimate goal is to get our site working at `mootube.example.com`. In the *real*
world, you would configure a DNS record and point it to the server. With AWS, you
can do that with their Route 53 service.

But since this is a *fake* domain, we need to cheat. Open up `/etc/hosts`:

```terminal-silent
sudo vim /etc/hosts
```

Near the bottom, put the IP address to our server - I'll copy it from `hosts.ini` -
and point this to `mootube.example.com`.

At this point, our code is on the server and the Nginx virtual host is pointing
to it. We have the absolute basics finished! Find your browser, and try the
site! http://mootube.example.com.

It works! I'm just kidding - it's totally a 500 error: we're still missing a few steps.

To see what the exact error is, go to the server and check the logs. In the virtual
host, you can see that the `error_log` config is set to
`/var/log/nginx/mootube.example.com_error.log`. Tail that file: `sudo tail` and
then the path:

```terminal-silent
sudo tail /var/log/nginx/mootube.example.com_error.log
```

Ah! Look closely:

> Failed opening required vendor/autoload.php

Of course! We have not run `composer install` yet. In fact, we also haven't configured
our database credentials or any file permissions. All we've done is put our code
on the server. But... that *is* pretty awesome: we already have a system that deploys
in a very cool way: creating a new `releases` directory and symlinking that to
`current`. Our deploy is missing some steps, but it's already pretty awesome.

But before we finish it, let's talk about deploy keys so that we can deploy *private*
repositories.
