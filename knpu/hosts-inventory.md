# Hosts & the Inventory File

When we run `ansible`, we see a few warnings on top:

> Host file not found

with a path to a host file somewhere on your system. Then it says:

> provided hosts list is empty, only localhost is available.

It turns out, at first, we can *only* execute ansible against one host: `localhost`.
If you want to start running against any other server, you need to create a host
configuration file. You can either do this in a global hosts file - in the location
described in the warning - or you can create a file right inside your project. That's
the way I like to do it!

In your project, create a new directory called `ansible`. And inside, make a new
`hosts.ini` file.

The *smallest* thing you need to configure a host is... just the IP address: 127.0.0.1.
We'll keep running things against our local machine for a bit longer.

As *soon* as you have this, you can use *this* as your host: `ansible 127.0.0.1 -m ping`.
To tell Ansible about the new hosts file, add `-i ansible/hosts.ini`. It's `-i` because
the hosts file is known as your *inventory*. Try it!

```terminal
ansible 127.0.0.1 -m ping -i ansible/hosts.ini
```

## Setting Host Variables

Ah! It fails! I keep telling you that Ansible works by connecting over SSH and then
running commands. Well, technically, that's not 100% true: you can actually configure
Ansible to connect to your server in different ways, though you'll almost always
use SSH. The most common exception is when you're working on your *local* machine -
you don't need to connect via SSH at all!

To tell Ansible that this is a local connection, in your `hosts.ini` file, after
the IP address, add `ansible_connection=local`. There's also a `docker` connection
type if you're getting nerdy with Docker.

Try that ping again!

```terminal
ansible 127.0.0.1 -m ping -i ansible/hosts.ini
```

Got it!

This little change is actually *really* important. By saying `ansible_connection=local`,
we are setting a *variable* inside of Ansible. And as we build out more complex Ansible
configuration, this idea of setting and using variables will become more important.
As you'll see, you can set more variables for each host, which will let us change
behavior on a host-by-host basis.

In this case, `ansible_connection` is a built-in variable that Ansible uses when
it connects. We're simply changing it first.

## Host Groups

So right now, we have just *one* host. But eventually, you might have *many* - like
5 web server hosts, 2 database hosts and a Redis host. One common practice is to
*group* your hosts. Let me show you: at the top, add a group called `[local]`,
with our one host below it.

As soon as we do that, instead of using the IP address in the command, we can use
the group name:

```terminal
ansible local -m ping -i ansible/hosts.ini
```

That will run the module against *all* hosts inside of the `local` group... which
is just one right now. Boring! Let's add another! Below the first, add `localhost`
with `ansible_connection=local`.

This is a little silly, but it shows how this works. Run the command now!

```terminal
ansible local -m ping -i ansible/hosts.ini
```

Yes! It runs the ping module twice: once on each server. If you needed to setup 10
web servers... well, you can imagine how *awesome* this could be.

And actually, there's a special option - `--list-hosts` that can show you all of
the hosts in that group:

```terminal
ansible local --list-hosts -i ansible/hosts.ini
```

Ok, remove the `localhost` line. Time to start executing things against a *real*
server.
