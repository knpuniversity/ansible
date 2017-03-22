# Create your Playbook!

At this point, we can execute any module on our hosts... but we're still doing it
manually from the command line. If that's all Ansible could do, it would... kinda
suck.

Nope, what I *really* want is to be able to create a big config file that describes
*all* of the modules that we need to execute to get an entire server set up: like
installing PHP, installing MySQL, installing Nginx and then configuring everything.

This is done in something called a *playbook*: a YAML-formatted file that contains
these instructions. In the `ansible` directory, create our playbook: `playbook.yml`.
For now, just leave it empty.

To run the playbook, instead of using the `ansible` command, use `ansible-playbook`.
Point that at your playbook and keep passing `-i ansible/hosts.ini`. Try it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Error!

> Playbook must be a list of plays

## Your First Play and Task

Ah, ok, this is a preview of some Ansible terminology. Let's start filling in the
playbook. At the top, every YAML file in Ansible starts with three dashes (`---`).
It's not really important and doesn't meaning anything... it's just a YAML standard.
Below, add `host: all`. Then, indent two spaces, add `tasks`, indent again, and 
add `ping: ~`.

If you're new to YAML, you *do* need to be careful: the spacing and indentation
are important. Try it:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Sweet! It runs the `ping` module against *all* hosts: our local machine and the VM.

Ok, back to the terminology we saw in the error message. A playbook contains plays.
In this case we have just *one* play that will run on `all` hosts. Later, if we added
*another* `host` line with its own `tasks` below it, that would be a *second* play.
So, a playbook contains plays, a play contains tasks and each task executes a module.
In this case we're executing the `ping` module.

To run the play against only *one* group, there are two options. First, at the
command line, you can pass `-l vb`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -l vb
```

But a better way is to configure this *inside* of your playbook. This play will
be responsible for setting up an entire server... so we don't want to run it against
our local machine... ever. Change the `all` to `vb`. Now try the command, but without
the `-l` option:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Yes! Just one ping.

Ok, let's install stuff!
