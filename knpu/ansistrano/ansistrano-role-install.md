# Ansistrano Role Installation

We already have an `ansible/` directory, which has a bunch of files to support
two playbooks: `aws.yml` that boots new EC2 servers and `playbook.yml` that can
*provision* those servers, installing things like Nginx, PHP, and anything else
we need. Now, we're going to create a *third* playbook: `deploy.yml` that will
deploy our code.

But! There's one *really* important thing I want you to understand: this new playbook
will not use *any* of the files inside of the `ansible/` directory. So, don't worry
or think about them: pretend that the `ansible/` directory is *completely* empty,
except for `deploy.yml`. If you *do* need any other files, we will talk about them!

To help us deploy with Ansible, we're going to - of course - use Ansistrano! Open
up ansistrano.com in your browser. It has some cool deployment stats... but the
most important thing is the [ansistrano.deploy][ansistrano_deploy] link that goes
to the GitHub page and their docs.

Ansistrano is an Ansible role... which basically means it gives us free Ansible
tasks! That's like getting a free puppy... but without all that responsibility and
carpet peeing!

## Installing the Role

The docs show an `ansible-galaxy` command that will install the role. Don't do it!
There's a better way!

Open `ansible/requirements.yml`:

[[[ code('bfd1cdae8c') ]]]

You *can* use `ansible-galaxy` to install whatever random Ansible role you want.
*Or*, you can describe the roles you need in a YAML file and tell galaxy to install
everything you need at once. This is just a nicer way to keep track of what roles
we need.

Add another `src:` line. Then, go copy the role name - just the deploy role:

[[[ code('dccf46acfa') ]]]

***TIP
Due to the changes in Ansible Galaxy, Ansistrano is installed now via `ansistrano.deploy`
instead of the old `carlosbuenosvinos.ansistrano-deploy`.
***

We'll talk about rollback later. Paste that and add `version`. So... what's the latest
version of this role? Let's find out! On the GitHub page, scroll up and click "Releases".
But be careful! There are actually newer tags. Ok, so right now, the latest version
is 2.7.0. Add that to `requirements.yml`:

[[[ code('c58ef61f42') ]]]

Great! To make sure all of the roles are installed, run:

```terminal
ansible-galaxy install -r ansible/requirements.yml
```

We *already* have the Redis role installed that's used in the provision playbook.
And now it downloads `ansistrano-deploy` to some `/usr/local/etc` directory. Perfect!

## Configuring the Hosts

In our `deploy.yml`, start with the meaningless, but ceremonial three dashes. Then,
below that, add `hosts` set to `aws`:

[[[ code('74e809bda3') ]]]

This is important: if you're *only* using Ansible for deployment, then you don't
need *any* of these other files in the `ansible/` directory... except for `hosts.ini`.
You *do* need this file. It doesn't need to be as complex as mine. You just need
to have one host group with at least one IP address below it:

[[[ code('00806505b3') ]]]

In our case, we have a host group called `aws` with the IP address to one server
below it.

## Using the Role

Back in `deploy.yml`, let's import the role! Add `roles:`, copy the name of the role,
and then paste it here: `ansistrano.deploy`:

[[[ code('2d45e4e524') ]]]

If you went through our Ansible tutorial, then you know that a role magically
gives our playbook new tasks! Plus, a few other things, like variables and handlers.

So... what new tasks did this add? Let's find out! Run:

```terminal
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml --list-tasks
```

Thanks to the `--list-tasks` flag, this won't *execute* our playbook, it will just
tell us what tasks it *would* run. Try it!

Not all of this will make sense yet... but you can see things like "Ensure deployment
base path exists". And later, it creates something called a "current folder" and
performs some cleanup.

What does this all mean? It's time to learn *exactly* how Ansistrano works and
run our first deploy. That's next!


[ansistrano_deploy]: https://github.com/ansistrano/deploy
