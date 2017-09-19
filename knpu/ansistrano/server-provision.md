# Setup: Server Provisioning

Hey guys! Ok, here's my situation: I've built this *amazing*  new app: MooTube:
the latest *big* thing in cow fitness. There's just one problem: it only lives right
here on my computer. It's time to release it to the cow masses. Yep, it's time to
deploy!

But... how! There are probably 50 good ways to deploy! Bah! And these days, you
can even deploy via a Platform as a Service: something like Platform.sh or Heroku
that takes care of almost everything for you. Actually, those are both great ways
to deploy: you're a bit limited, but they are the *fastest* way to get your app to
production.

In this tutorial, we're going to talk about one, *really* nice deployment tool:
Ansistrano. It's built on top of Ansible... so if you watched our
[Ansible tutorial](https://knpuniversity.com/screencast/ansible), you're going to
*love* this. But if you haven't, I'll give you the details you need.

## Download the Project

As always, learning, like cow grazing, is best done in a group: so you should
*definitely* code along with me. Download the course code on this page and unzip
it. Inside, you'll find a `start/` directory, which will have the same code you
see here. See that README file? It holds all the secrets for getting the project
working. But actually... this is a tutorial about deployment! So... you don't *really*
need to get the project running *locally*... because we're going to get the project
running... in the **cloud**!

But, if you *do* want to get the project running, the last step will be to find your
terminal, sip some coffee, and run:

```terminal
bin/console server:run
```

to start the built-in PHP web server. Open the app in your browser at `http://localhost:8000`.
Ah yes, MooTube: our new bovine fitness app that is about to stampede through the
cow world. This is the *same* app we used in our Ansible tutorial, with just a few
small changes.

## Booting a new Server

Ok, let's get to work! So first... well... we need a server! You can use anything,
but I've already booted a new EC2 instance from AWS. I actually did this via Ansible.
In our Ansible tutorial, we created a small playbook - `aws.yml` - whose *only*
job was to boot an EC2 instance using an Ubuntu 14.04 image.

You're free to get a server from *anywhere*... but if you *do* want to use this
script to boot a new instance, you'll just need to do 2 things. First, edit the
ansible vault at `ansible/vars/aws_vault.yml`.

```terminal-silent
ansible-vault edit ansible/vars/aws_vault.yml
```

The password is `beefpass`. Make sure you edit `aws_vault.yml`

These access keys are mine... and as much fun as it would be to help you host your
servers... they won't work anymore. Sorry! Replace these with your own. Second,
in `aws.yml`, see that `key_name`? You'll need to create your own "Key Pair" in
the EC2 management console, and put your name here. That will be the private key
used to SSH onto the new server.

## Server Provisioning

Once you have a server... deploying is really *two* steps. Step 1: provisioning:
the fancy word that basically means: you need to install everything you need on
the server, like Nginx, PHP, PHP extension and whatever else you need. And *then*
step 2: actually deploy.

I don't care *how* you setup - or *provision* - your server. In the Ansible tutorial,
we used Ansible to do this, but that's not a requirement for using Ansistrano.

But since we already have a working provision playbook with Ansible, let's use it!
First, I'll find the public IP address to my new server. Open `ansible/hosts.ini`
and put this under the `aws` group. If you're still new to Ansible, we'll talk
about this file more, once we start to deploy.

Now, run Ansible:

```terminal
 ansible-playbook ansible/playbook.yml -I ansible/hosts.ini -l aws
```

Go Ansible go! See that `-l aws` at the end? Well, the provision playbook - `playbook.yml` -
is setup to provision both my `aws` servers, and also a local VirtualBox server.
The `-l` tells Ansible to only worry about provisioning our AWS server right now.

## Ansible Authentication

Behind the scenes, Ansible is SSH'ing onto the server and running commands. But...
how does authentication to the server work? In our case, it's with the private key
from the "Key Pair" that we used to boot the server. Open `ansible/group_vars/aws.yml`.
Because we're using the `aws` group, this file is automatically loaded. It sets
two important variables: `ansible_user` and `ansible_ssh_private_key_file`.

When you use Ansistrano to deploy, you'll need to make sure these two variables
are set... or `ansible_ssh_pass` if you're using a password. You don't need to set
them in a fancy group variable file like this. If you're still new to Ansible, I'll
show you how to create variables right in your deploy playbook later.

For now, just know that we *are* telling Ansible how to authentication: there's
no magic.

## Checking out the Server

While Ansible is working, open a third tab. Let's SSH onto the server: `ssh -i`,
the path to the private, then `ubuntu@` followed up the IP address:

```terminal-silent
ssh -i ~/.ssh/KnpU-Tutorial.pem ubuntu@54.XXX.XX.XXX
```

Perfect! Now, let's wait for provisioning to finish. Fast forward!!!!

Ding! Our server is ready! Check this out! We have PHP version 7.1, Nginx and even
an Nginx Virtual host setup.

```terminal-silent
cd /etc/nginx/sites-available
sudo vim mootube.example.com.conf
```

Our site will be `mootube.example.com`, and this is setup with a document root
at `/var/www/project/web`. Right now, there *is* a `/var/www/project` directory...
but it's empty. Putting code there? That's the topic of this tutorial!

Let's go!
