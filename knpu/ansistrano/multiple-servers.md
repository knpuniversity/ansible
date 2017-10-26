# Deploying to Multiple Servers

I just got great *great* news from our investors: the cows are practical *stampeding*
to our site. They're watching our workout videos in herds. They're crying for mooooore!

Ahem.

But now, our site is starting to have issues! We need to go from one server to *multiple*.
And guess what? We've already done most of the hard work to do this! Congrats!

Ready to make it happen? First, find your local terminal and use the `aws.yml`
playbook to create a new EC2 instance:

```terminal
ansible-playbook ./ansible/aws.yml -i ./ansible/hosts.ini --ask-vault-pass
```

This should only take a few second while it boots the server and waits for it to
become available. Perfect!

## Provision (Literally) all the Servers!

So why is it so easy to deploy to multiple servers? Because we've done *everything*
via playbooks, and Ansible is *built* to work on many servers. Copy the public IP
of the new server and open your `ansible/hosts.ini` file. Under the `aws` group,
paste the new IP.

Now, provision *both* servers by using the `playbook.yml` file. Add `-l aws` so
we *only* provision those hosts:

```terminal
ansible-playbook ./ansible/playbook.yml -i ./ansible/hosts.ini -l aws --ask-vault-pass
```

This won't make many changes to our existing server, but it *will* do *everything*
to the new one. Translation... this may take awhile...

## Dynamic Inventory

Oh, and you may have noticed that I hardcoded the IP address in my `hosts.ini` file.
For a *truly* scalable architecture, you can use Ansible's Dynamic inventory, which
works *extremely* well with EC2. You could, for example, use it to automatically use
all EC2 instances with a specific *tag*.

Once the provision... finally... finishes.... let's deploy! Use `deploy.yml`:

```terminal
ansible-playbook ./ansible/deploy.yml -i ./ansible/hosts.ini --ask-vault-pass
```

While that works, we need to talk about a few interesting things.

First, if you write the number pi to 2 decimal places, it spells the word pie backwards!
Super interesting!

Second, Google for "Ansible serial" to find a spot on their docs called "Delegation,
Rolling Updates, and Local Actions". On your playbook, Ansible allows you to set
a `serial` option. If you have *many* servers, like 100, then if `serial` is set
to 3, Ansible will deploy to only 3 servers at a time. The effect is a *rolling*
deploy: your updated code reaches your 100 servers little-by-little. That's overkill
for MooTube... well, at least today! But, it *is* a cool feature!

One *possible* issue with a rolling update involves the release directory name.
You guys already know that each release is in a timestamped directory. In a serial
deploy, that timestamp will be different on earlier servers versus later servers.
For our app, that's no problem! But, if you used the directory name as part of some
cache keys - like many apps do - then this *would* be a problem: different servers
would using different cache keys to get the same data.

In the Ansistrano docs, if you search for "serial", they mention this. By setting
a variable, you can control the release directory name and make sure it's the same
on all servers.

## Preparing for Multiple Servers

Look back at the deploy. The database migrations *just* ran: for the first server,
it reported "OK", because there were no migrations to run. But the second server
*did* have migrations to run.

Wait... that's kinda weird... shouldn't they all servers use the same database? Yep!
There are a few things you need to do in order to be ready for multiple servers.
The most obvious is that all your servers need to use the *same* database, or database
cluster. For MooTube.com, each app is using their own *local* database. We're not
going to fix that, but you *do* need to fix this in real life. The other really common
thing you need to change is session storage: instead of storing sessions locally,
you need store them in a shared place, like the database. In fact, that's the rule:
when you have multiple servers, never store anything locally, except for temporary
files or cache that help your code actually function.

## Migrations & run_once

But wait... if all our servers shared one database... there would *still* be a problem!
*Every* server would try to execute the migrations. That means that the same migration
scripts would execute *multiple* times. Oh no!

Back in the Ansible docs, there is an option called `run_once` to fix this. It's
pretty simple: if this is set, the task only runs on one server. 

Ok! We are now deployed to *both* servers. Right now, `mootube.example.com` points
directly to the *original* server. Copy the IP address to the new server. Then,
open `/etc/hosts` and change `mootube.example.com` to point to that.

To test it, open a new Incognito Window to avoid caching and visit `mootube.example.com`.
It works! Yes! We did *not* load the fixtures on the new server... which means we
have a convenient way to know which server is being hit.

So, if you're using a playbook for provision and deploy, using multiple servers
isn't a big deal. You *will* need to update your code a little bit - like to share
session data - but almost everything is the same.

Next, let's go a step further and add a load balancer!
